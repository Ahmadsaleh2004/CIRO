<?php
/**
 * helpers/mail_helper.php
 * دالة إرسال بريد تأكيد الطلب
 */

require_once __DIR__ . '/../config/db.php';

function sendOrderConfirmationEmail($orderId, $userEmail, $userName, $items, $totalAmount): bool {
    $mailConfig = require __DIR__ . '/../config/mail.php';
    
    // بناء محتوى الرسالة HTML
    $subject = "Cairo Store - Order Confirmation #" . $orderId;
    
    $itemsHtml = '';
    foreach ($items as $item) {
        $sub = (float)$item['price'] * (int)$item['quantity'];
        $itemsHtml .= "<tr>
            <td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . htmlspecialchars($item['name']) . "</td>
            <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: center;'>" . (int)$item['quantity'] . "</td>
            <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: right;'>$" . number_format((float)$item['price'], 2) . "</td>
            <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: right;'>$" . number_format($sub, 2) . "</td>
        </tr>";
    }
    
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
        <h2 style='color: #6366f1; text-align: center;'>Order Confirmed! 🎉</h2>
        <p>Dear " . htmlspecialchars($userName) . ",</p>
        <p>Thank you for shopping at Cairo Store. Your order <strong>#" . (int)$orderId . "</strong> has been successfully placed and is being processed.</p>
        
        <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
            <thead>
                <tr style='background: #f8f9fa;'>
                    <th style='padding: 8px; text-align: left; border-bottom: 2px solid #ddd;'>Product</th>
                    <th style='padding: 8px; text-align: center; border-bottom: 2px solid #ddd;'>Qty</th>
                    <th style='padding: 8px; text-align: right; border-bottom: 2px solid #ddd;'>Price</th>
                    <th style='padding: 8px; text-align: right; border-bottom: 2px solid #ddd;'>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                {$itemsHtml}
            </tbody>
        </table>
        
        <div style='margin-top: 20px; text-align: right;'>
            <h3>Total: <span style='color: #16a34a;'>$" . number_format((float)$totalAmount, 2) . "</span></h3>
        </div>
        
        <hr style='border: none; border-top: 1px solid #eee; margin-top: 30px;'>
        <p style='font-size: 0.85rem; color: #777; text-align: center;'>Cairo Store - Premium Electronics Store</p>
    </div>";
    
    // التحقق من وجود autoloader الخاص بـ Composer
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $mailConfig['smtp_host'];
            $mail->SMTPAuth   = $mailConfig['smtp_auth'];
            $mail->Username   = $mailConfig['smtp_username'];
            $mail->Password   = $mailConfig['smtp_password'];
            $mail->Port       = $mailConfig['smtp_port'];
            
            if ($mailConfig['smtp_secure'] === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($mailConfig['smtp_secure'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }
            
            $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mail->addAddress($userEmail, $userName);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->CharSet = 'UTF-8';
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
        }
    }
    
    // Fallback لـ mail() الافتراضية
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $mailConfig['from_name'] . " <" . $mailConfig['from_email'] . ">\r\n";
    
    return mail($userEmail, $subject, $body, $headers);
}
