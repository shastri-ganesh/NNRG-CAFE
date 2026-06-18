<?php
// includes/send_otp.php - OTP Generation and Email Sending

// Use __DIR__ to get absolute path - works from anywhere!
require_once __DIR__ . '/../phpmailer/Exception.php';
require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendOTP($recipient_email, $recipient_name = "User") {
    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Set OTP expiry time (5 minutes from now)
    $expiry_time = time() + (5 * 60);
    
    // Store in session
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = $expiry_time;
    $_SESSION['otp_email'] = $recipient_email;
    $_SESSION['otp_verified'] = false;
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'vijaycafeteria1@gmail.com';  // Your Gmail
        $mail->Password   = 'qniz snqz bkvo pbno';        // Your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('vijaycafeteria1@gmail.com', ' NNRG-CAFE Security');
        $mail->addAddress($recipient_email, $recipient_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Verification Code -  NNRG-CÁFE';
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                .header { text-align: center; color: #198754; margin-bottom: 20px; }
                .otp-box { background-color: #f8f9fa; border: 2px dashed #198754; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
                .otp-code { font-size: 32px; font-weight: bold; color: #198754; letter-spacing: 8px; }
                .info { color: #666; font-size: 14px; line-height: 1.6; }
                .warning { color: #dc3545; font-size: 13px; margin-top: 15px; }
                .footer { text-align: center; color: #999; font-size: 12px; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🔐  NNRG-CÁFE Security Verification</h2>
                </div>
                <p class='info'>Hello <strong>{$recipient_name}</strong>,</p>
                <p class='info'>You have requested to verify your identity for a secure action on FOODCAVE.</p>
                
                <div class='otp-box'>
                    <p style='margin: 0; color: #666; font-size: 14px;'>Your OTP Code:</p>
                    <p class='otp-code'>{$otp}</p>
                </div>
                
                <p class='info'>Please enter this code to complete your verification. This code is valid for <strong>5 minutes</strong>.</p>
                
                <p class='warning'>⚠️ If you didn't request this code, please ignore this email or contact support immediately.</p>
                
                <div class='footer'>
                    <p>©️ " . date('Y') . " NNRG-CÁFE | Automated Security Message</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Your FOODCAVE OTP verification code is: {$otp}\n\nThis code is valid for 5 minutes.\n\nIf you didn't request this code, please ignore this email.";
        
        $mail->send();
        return array('success' => true, 'message' => 'OTP sent successfully!');
        
    } catch (Exception $e) {
        return array('success' => false, 'message' => "Failed to send OTP. Error: {$mail->ErrorInfo}");
    }
}
?>