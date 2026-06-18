<?php
// Include PHPMailer files
require_once '../phpmailer/Exception.php';
require_once '../phpmailer/PHPMailer.php';
require_once '../phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class OrderNotification {
    private $mysqli;
    private $gmail_email = "vijaycafeteria1@gmail.com";
    private $gmail_password = "qniz snqz bkvo pbno"; // You'll change this
    private $from_name = "NNRG CAFETERIA";
    
    public function __construct($db_connection) {
        $this->mysqli = $db_connection;
    }
    
    // 🔧 FIXED: This matches your admin_order_update.php expectations
    public function sendReadyNotification($transaction_id) {
        try {
            // Get order details
            $order_data = $this->getOrderDetails($transaction_id);
            
            if (!$order_data || empty($order_data['email'])) {
                return ['success' => false, 'message' => 'No customer email found'];
            }
            
            // Send email using PHPMailer
            $email_sent = $this->sendPHPMailerEmail($order_data);
            
            return ['success' => $email_sent, 'message' => $email_sent ? 'Email sent successfully' : 'Failed to send email'];
            
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Email system error: ' . $e->getMessage()];
        }
    }
    
    // 🔧 FIXED: Using correct table structure from your code
    private function getOrderDetails($tid) {
        // Get main transaction details
        $query = "SELECT t.tid, t.name, t.email, t.rollno, t.year, t.branch_section, 
                         t.order_cost, t.pickup_time, t.pickup_notes, t.created_at, t.order_type
                  FROM transaction t 
                  WHERE t.tid = ?";
        
        $stmt = $this->mysqli->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("s", $tid);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        
        if ($order) {
            // Get food items using correct table structure
            $order['items'] = $this->getOrderItems($tid);
        }
        
        return $order;
    }
    
    // 🔧 FIXED: Using correct table names from your code
    private function getOrderItems($tid) {
        $query = "SELECT ti.quantity, f.f_name, f.f_price
                  FROM transaction_items ti 
                  INNER JOIN food f ON ti.f_id = f.f_id 
                  WHERE ti.tid = ?
                  ORDER BY f.f_name";
        
        $stmt = $this->mysqli->prepare($query);
        if (!$stmt) return [];
        
        $stmt->bind_param("s", $tid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'name' => $row['f_name'],
                'quantity' => $row['quantity'],
                'price' => $row['f_price']
            ];
        }
        return $items;
    }
    
    private function sendPHPMailerEmail($order_data) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $this->gmail_email;
            $mail->Password = $this->gmail_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Recipients
            $mail->setFrom($this->gmail_email, $this->from_name);
            $mail->addAddress($order_data['email'], $order_data['name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Order is Ready for Pickup!️';
            $mail->Body = $this->createEmailTemplate($order_data);
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    private function createEmailTemplate($order) {
        $items_html = '';
        $total_amount = 0;
        
        foreach ($order['items'] as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $total_amount += $item_total;
            
            $items_html .= "
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$item['name']}</td>
                <td style='padding: 8px; border: 1px solid #ddd; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 8px; border: 1px solid #ddd; text-align: right;'>₹{$item['price']}</td>
                <td style='padding: 8px; border: 1px solid #ddd; text-align: right;'>₹{$item_total}</td>
            </tr>";
        }
        
        // Order type display
        $order_type_display = ucfirst($order['order_type'] ?? 'takeaway');
        $pickup_icon = ($order['order_type'] == 'dine-in') ? '🏠' : '🛍️';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { text-align: center; color: #2c5530; margin-bottom: 20px; }
                .status { background: #28a745; color: white; padding: 15px; text-align: center; border-radius: 5px; font-size: 18px; font-weight: bold; }
                .order-info { margin: 20px 0; }
                .order-info h3 { color: #2c5530; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th { background: #2c5530; color: white; padding: 10px; text-align: left; }
                .total { font-weight: bold; font-size: 16px; color: #2c5530; }
                .footer { text-align: center; margin-top: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>NNRG CAFETERIAn</h1>
                </div>
                
                <div class='status'>
                    ✅ YOUR ORDER IS READY FOR PICKUP!
                </div>
                
                <div class='order-info'>
                    <h3>Order Details:</h3>
                    <p><strong>Order ID:</strong> {$order['tid']}</p>
                    <p><strong>Customer:</strong> {$order['name']}</p>
                    <p><strong>Roll Number:</strong> {$order['rollno']}</p>
                    <p><strong>Academic:</strong> {$order['year']} - {$order['branch_section']}</p>
                    <p><strong>Order Type:</strong> {$pickup_icon} {$order_type_display}</p>
                </div>
                
                <table>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                    {$items_html}
                    <tr class='total'>
                        <td colspan='3' style='padding: 10px; text-align: right; border: 1px solid #ddd;'><strong>Grand Total:</strong></td>
                        <td style='padding: 10px; text-align: right; border: 1px solid #ddd;'><strong>₹{$order['order_cost']}</strong></td>
                    </tr>
                </table>
                
                <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h4 style='margin: 0 0 10px 0; color: #856404;'>📍 Pickup Instructions:</h4>
                    <p style='margin: 0; color: #856404;'>Please come to the canteen counter with this email or your order ID. Your food is ready and waiting!</p>
                    " . ($order['pickup_time'] ? "<p style='margin: 5px 0 0 0; color: #856404;'><strong>Preferred Pickup Time:</strong> {$order['pickup_time']}</p>" : "") . "
                    " . ($order['pickup_notes'] ? "<p style='margin: 5px 0 0 0; color: #856404;'><strong>Notes:</strong> {$order['pickup_notes']}</p>" : "") . "
                </div>
                
                <div class='footer'>
                    <p>Thank you for choosing NNRG CAFETERIA!</p>
                    <p style='font-size: 12px;'>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>