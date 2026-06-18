<?php
session_start();
include("conn_db.php");
include_once("scratch_card_functions.php");

// Set your customer ID
$customer_id = 29; // Change this to your actual customer ID
$_SESSION['cid'] = $customer_id;

echo "<h2>Direct Points Usage Test</h2>";

if (isset($_POST['test_points'])) {
    $points_to_use = (int)$_POST['test_points'];
    $test_transaction_id = "TEST_" . time();
    
    echo "<h3>Testing Usage of $points_to_use Points</h3>";
    
    try {
        $scratchSystem = new ScratchCardSystem($mysqli);
        
        // Get current balance first
        $current_balance = $scratchSystem->getCustomerPointsBalance($customer_id);
        echo "Current Balance: $current_balance points<br>";
        
        if ($points_to_use > $current_balance) {
            echo "❌ Error: Requesting more points than available<br>";
        } else {
            echo "✅ Sufficient balance available<br>";
            
            // Attempt to use points
            echo "<h4>Attempting to use points...</h4>";
            $result = $scratchSystem->useCustomerPoints($customer_id, $points_to_use, $test_transaction_id);
            
            echo "<strong>Result:</strong><br>";
            echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "<br>";
            echo "Message: " . $result['message'] . "<br>";
            
            if ($result['success']) {
                echo "Points Used: " . $result['points_used'] . "<br>";
                echo "Records Affected: " . $result['records_affected'] . "<br>";
                
                // Check new balance
                $new_balance = $scratchSystem->getCustomerPointsBalance($customer_id);
                echo "New Balance: $new_balance points<br>";
                echo "Expected Balance: " . ($current_balance - $points_to_use) . " points<br>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
}

// Show current data
try {
    $scratchSystem = new ScratchCardSystem($mysqli);
    $balance = $scratchSystem->getCustomerPointsBalance($customer_id);
    echo "<h3>Current Status</h3>";
    echo "Customer ID: $customer_id<br>";
    echo "Available Points: $balance<br>";
} catch (Exception $e) {
    echo "Error getting balance: " . $e->getMessage() . "<br>";
}

// Show recent logs
echo "<h3>Recent Error Logs</h3>";
if (file_exists('payment_errors.log')) {
    $logs = file_get_contents('payment_errors.log');
    $recent_logs = array_slice(explode("\n", $logs), -20);
    echo "<pre>" . implode("\n", $recent_logs) . "</pre>";
} else {
    echo "No error log found<br>";
}

?>

<h3>Test Points Usage</h3>
<form method="post">
    <label>Points to use: </label>
    <input type="number" name="test_points" value="5" min="1" max="54">
    <button type="submit">Test Points Usage</button>
</form>

<p><a href="payment.php">← Back to Payment Page</a></p>