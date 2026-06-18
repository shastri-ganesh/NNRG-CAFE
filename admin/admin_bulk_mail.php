<?php
session_start();
include("../conn_db.php");

// Only allow SUPERADMIN or ADMIN
if ($_SESSION["utype"] != "SUPERADMIN" && $_SESSION["utype"] != "ADMIN") {
    header("location: ../restricted.php");
    exit(1);
}

// Include PHPMailer classes
require_once '../phpmailer/Exception.php';
require_once '../phpmailer/PHPMailer.php';
require_once '../phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$message = "";
$message_type = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_bulk_mail'])) {

    // Server execution limits might need extension for many emails. 
    // Sending via BCC is extremely fast, but we set time limit to 0 just to be safe.
    set_time_limit(0);

    $subject = trim($_POST['subject']);
    $email_body = $_POST['email_body'];

    if (empty($subject) || empty($email_body)) {
        $message = "Please provide both Subject and Message.";
        $message_type = "danger";
    }
    else {
        // Fetch all active students' emails
        $query = "SELECT c_email, c_firstname FROM customer WHERE c_type = 'STD' AND c_email IS NOT NULL AND c_email != ''";
        $result = $mysqli->query($query);

        if ($result && $result->num_rows > 0) {

            $mail = new PHPMailer(true);

            try {
                // PHPMailer Configuration (Same as email_notification.php)
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'vijaycafeteria1@gmail.com';
                $mail->Password = 'qniz snqz bkvo pbno';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('vijaycafeteria1@gmail.com', 'NNRG CAFETERIA');

                // Collect all valid emails first
                $all_emails = [];
                while ($row = $result->fetch_assoc()) {
                    if (filter_var($row['c_email'], FILTER_VALIDATE_EMAIL)) {
                        $all_emails[] = [
                            'email' => $row['c_email'],
                            'name' => $row['c_firstname']
                        ];
                    }
                }
                
                $total_emails = count($all_emails);
                
                if ($total_emails > 0) {
                    // Gmail SMTP limit is strictly ~100 recipients per message. 
                    // We split the emails into safe chunks of 90.
                    $email_chunks = array_chunk($all_emails, 90);
                    $successful_sends = 0;
                    
                    foreach ($email_chunks as $chunk) {
                        $mail->clearAllRecipients(); // Clear from previous chunk
                        
                        foreach ($chunk as $recipient) {
                            $mail->addBCC($recipient['email'], $recipient['name']);
                            $successful_sends++;
                        }
                        
                        $mail->isHTML(true);
                        $mail->Subject = $subject;
                        
                        // Put the message inside a nice HTML template wrapper
                        $template = "
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                                .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                                .header { text-align: center; color: #2c5530; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #28a745; }
                                .content { line-height: 1.6; color: #333; font-size: 16px; margin: 20px 0; }
                                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px; }
                                .btn { display: inline-block; padding: 10px 20px; background: #28a745; color: white !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 15px; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h1>NNRG CAFETERIA</h1>
                                </div>
                                <div class='content'>
                                    " . nl2br($email_body) . "
                                </div>
                                <div class='footer'>
                                    <p>Thank you for choosing NNRG CAFETERIA!</p>
                                    <p>This email was sent to all our registered members.</p>
                                </div>
                            </div>
                        </body>
                        </html>";
                        
                        $mail->Body = $template;
                        
                        // Send this chunk!
                        $mail->send();
                    }
                    
                    $message = "Success! Marketing email successfully sent to <strong>{$successful_sends}</strong> members in " . count($email_chunks) . " batches.";
                    $message_type = "success";
                }
                else {
                    $message = "No valid emails found in the database to send to.";
                    $message_type = "warning";
                }

            }
            catch (Exception $e) {
                $message = "Mailer Error: " . $mail->ErrorInfo;
                $message_type = "danger";
            }

        }
        else {
            $message = "No student accounts found in the database.";
            $message_type = "warning";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../img/Color Icon with background.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <title>Bulk Email Marketing | NNRG-CAFÉ</title>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .main-content {
            margin-top: 80px;
            margin-bottom: 50px;
        }

        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }

        .btn-send {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-send:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
    </style>
</head>

<body>
    <?php include('nav_header_admin.php'); ?>

    <div class="container main-content">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <h2 class="mb-4"><i class="bi bi-envelope-paper-fill me-2" style="color: #667eea;"></i> Bulk Email
                    Marketing</h2>

                <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $message_type?> alert-dismissible fade show shadow-sm" role="alert">
                    <?= $message?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php
endif; ?>

                <div class="card card-custom">
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-megaphone-fill me-2"></i> Send Promotional Offer to All Members
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted mb-4">
                            Use this form to send coupons, daily specials, or important updates. <br>
                            <em>All student members will receive this email instantly. They will be added as "BCC", so
                                their email addresses remain private.</em>
                        </p>

                        <form method="POST" action="admin_bulk_mail.php" onsubmit="return confirmEmptySubmit();">
                            <div class="mb-4">
                                <label for="subject" class="form-label fw-bold required">Email Subject Line</label>
                                <input type="text" class="form-control form-control-lg" id="subject" name="subject"
                                    placeholder="e.g. 🎉 Special 20% OFF today only!" required>
                            </div>

                            <div class="mb-4">
                                <label for="email_body" class="form-label fw-bold required">Message Body (Supports HTML
                                    formatting)</label>
                                <textarea class="form-control" id="email_body" name="email_body" rows="8"
                                    placeholder="Type your message here...&#10;&#10;Hello,&#10;Use the coupon code SAVE20 at checkout for 20% off your next meal!&#10;&#10;Regards,&#10;NNRG Cafeteria Team"
                                    required></textarea>
                                <div class="form-text mt-2"><i class="bi bi-info-circle"></i> Line breaks will be
                                    preserved perfectly. You can also include basic HTML tags like
                                    <code>&lt;b&gt;bold text&lt;/b&gt;</code>.</div>
                            </div>

                            <div class="text-end mt-4 pt-2 border-top">
                                <button type="submit" name="send_bulk_mail" class="btn btn-send" id="sendBtn">
                                    <i class="bi bi-send-fill me-2"></i> Send to All Members
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmEmptySubmit() {
            if (confirm("Are you sure you want to send this email to ALL members now? This action cannot be undone.")) {
                document.getElementById('sendBtn').innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Sending...';
                document.getElementById('sendBtn').classList.add('disabled');
                return true;
            }
            return false;
        }
    </script>
</body>

</html>