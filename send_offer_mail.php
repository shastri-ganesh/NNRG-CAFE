<?php
require 'vendor/autoload.php'; // PHPMailer
include 'conn_db.php';

use PHPMailer\PHPMailer\PHPMailer;

$offer_points = 10;
$remark = "Welcome Offer";

// 1️⃣ Add points first
$mysqli->query("
INSERT INTO customer_points
(c_id, points_earned, points_used, points_balance, earned_date, status, remark)
SELECT c_id, $offer_points, 0, $offer_points, NOW(), 'active', '$remark'
FROM customers
");

// 2️⃣ Get all customer emails
$result = $mysqli->query("SELECT email, name FROM customers");

while($row = $result->fetch_assoc()) {

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'yourmail@gmail.com';
        $mail->Password = 'your-app-password';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('yourmail@gmail.com', 'Canteen Offers');
        $mail->addAddress($row['email'], $row['name']);

        $mail->isHTML(true);
        $mail->Subject = "🎉 Special Offer Just for You!";
        $mail->Body = "
        Hi {$row['name']},<br><br>
        We added <b>₹$offer_points points</b> to your account!<br>
        Use them on your next order 😄<br><br>
        Regards,<br>
        College Canteen
        ";

        $mail->send();

    } catch(Exception $e) {
        echo "Mail error: {$row['email']}<br>";
    }
}

echo "Offer sent successfully!";
