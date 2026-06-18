<?php
/**
 * Scratch Card System Functions - UPDATED FOR YOUR DATABASE STRUCTURE
 * Compatible with existing database structure
 */

class ScratchCardSystem {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->createRequiredTables();
    }
    
    private function createRequiredTables() {
        try {
            error_log("SYSTEM DEBUG: Using existing points_usage_history table structure");
        } catch (Exception $e) {
            error_log("Table creation error: " . $e->getMessage());
        }
    }
    
    /* ================= CREATE SCRATCH CARD ================= */
    public function createScratchCard($transaction_id, $customer_id) {
        try {
            $check_stmt = $this->mysqli->prepare(
                "SELECT sc_id FROM scratch_cards WHERE tid = ? AND c_id = ?"
            );
            $check_stmt->bind_param("si", $transaction_id, $customer_id);
            $check_stmt->execute();
            $existing = $check_stmt->get_result();

            if($existing->num_rows > 0){
                $check_stmt->close();
                return ['success'=>false,'message'=>'Scratch card exists'];
            }
            $check_stmt->close();

            $rand = mt_rand(1,100);
            if ($rand<=1) $scratch_amount=3;
            elseif ($rand<=5) $scratch_amount=5;
            else $scratch_amount=10;

            $expires_at=date('Y-m-d H:i:s',strtotime('+1 year'));

            $stmt=$this->mysqli->prepare("
                INSERT INTO scratch_cards
                (c_id, tid, scratch_amount, is_scratched, is_admin_approved, created_at, expires_at)
                VALUES (?, ?, ?, '0', '1', NOW(), ?)
            ");

            $stmt->bind_param("isis",$customer_id,$transaction_id,$scratch_amount,$expires_at);
            $stmt->execute();

            return ['success'=>true,'amount'=>$scratch_amount];

        } catch(Exception $e){
            return ['success'=>false,'message'=>$e->getMessage()];
        }
    }

    /* ================= GET SCRATCH CARDS ================= */
    public function getCustomerScratchCards($customer_id) {
        $stmt=$this->mysqli->prepare("
            SELECT sc.*, 
                   COALESCE(t.order_cost,0) order_cost,
                   COALESCE(t.order_status,'UNKNOWN') order_status
            FROM scratch_cards sc
            LEFT JOIN transaction t ON sc.tid=t.tid
            WHERE sc.c_id=?
            ORDER BY sc.created_at DESC
        ");
        $stmt->bind_param("i",$customer_id);
        $stmt->execute();

        $result=$stmt->get_result();
        $cards=[];
        while($row=$result->fetch_assoc()){
            $row['can_scratch']=$this->canScratchCard(
                $row['is_admin_approved'],
                $row['order_status'],
                $row['is_scratched'],
                $row['expires_at']
            );
            $cards[]=$row;
        }
        return $cards;
    }

    private function canScratchCard($approved,$status,$scratched,$expiry){
        if($scratched=='1') return false;
        if(strtotime($expiry)<time()) return false;
        if($approved!='1') return false;
        return in_array($status,['ACPT','PREP','RDPK','FNSH']);
    }

    public function getScratchBlockReason($card){
        if($card['is_scratched']=='1') return 'Already scratched';
        if(strtotime($card['expires_at'])<time()) return 'Expired';
        if($card['is_admin_approved']!='1') return 'Admin approval pending';
        return 'Order not approved yet';
    }

    /* ================= SCRATCH CARD ================= */
    public function scratchCard($sc_id,$customer_id){
        $this->mysqli->begin_transaction();

        $card=$this->mysqli->query("
            SELECT * FROM scratch_cards
            WHERE sc_id=$sc_id AND c_id=$customer_id
        ")->fetch_assoc();

        if(!$card) return ['success'=>false,'message'=>'Invalid card'];
        if($card['is_scratched']==1) return ['success'=>false,'message'=>'Already scratched'];

        $this->mysqli->query("
            UPDATE scratch_cards
            SET is_scratched=1,scratched_at=NOW()
            WHERE sc_id=$sc_id
        ");

        $this->addPointsToCustomer($customer_id,$card['scratch_amount'],$sc_id);
        $this->mysqli->commit();

        return ['success'=>true,'amount'=>$card['scratch_amount']];
    }

    /* ================= ADD POINTS ================= */
    private function addPointsToCustomer($customer_id,$points,$sc_id){
        $expires=date('Y-m-d H:i:s',strtotime('+1 year'));

        $stmt=$this->mysqli->prepare("
            INSERT INTO customer_points
            (c_id,points_earned,points_used,points_balance,
             source_scratch_card_id,earned_date,expires_at,status)
            VALUES (?, ?, 0, ?, ?, NOW(), ?, 'active')
        ");
        $stmt->bind_param("iiiis",$customer_id,$points,$points,$sc_id,$expires);
        $stmt->execute();
    }

    /* ==========================================================
       ⭐⭐⭐ ONLY FIXED FUNCTION ⭐⭐⭐
    ========================================================== */
    public function getCustomerPointsBalance($customer_id){
        try{
            $stmt=$this->mysqli->prepare("
                SELECT COALESCE(SUM(points_balance),0) total_points
                FROM customer_points
                WHERE c_id=?
                AND status='active'
            ");
            if(!$stmt) return 0;

            $stmt->bind_param("i",$customer_id);
            $stmt->execute();
            $row=$stmt->get_result()->fetch_assoc();

            return intval($row['total_points']);

        }catch(Exception $e){
            return 0;
        }
    }

    /* ================= USE POINTS ================= */
    public function useCustomerPoints($customer_id,$points,$tid){
        $balance=$this->getCustomerPointsBalance($customer_id);
        if($balance<$points) return ['success'=>false,'message'=>'Insufficient points'];

        $this->mysqli->begin_transaction();

        $this->mysqli->query("
            UPDATE customer_points
            SET points_used=points_used+$points,
                points_balance=points_balance-$points
            WHERE c_id=$customer_id LIMIT 1
        ");

        $this->mysqli->query("
            INSERT INTO points_usage_history
            (c_id,tid,points_used,used_date)
            VALUES ($customer_id,'$tid',$points,NOW())
        ");

        $this->mysqli->commit();
        return ['success'=>true];
    }

    /* ================= CUSTOMER STATS ================= */
    public function getCustomerStats($customer_id){
        return [
            'current_balance'=>$this->getCustomerPointsBalance($customer_id)
        ];
    }
}
?>
