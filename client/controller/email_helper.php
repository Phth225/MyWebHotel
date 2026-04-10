<?php
/**
 * Email Helper Class
 * Sử dụng PHPMailer để gửi email
 * Cấu hình từ .env file
 */

// Require PHPMailer trước khi use
require_once __DIR__ . '/../../PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper
{
    private static $mailer = null;

    /**
     * Khởi tạo PHPMailer với cấu hình từ .env
     */
    private static function init()
    {
        if (self::$mailer === null) {
            // Load .env file
            $envFile = __DIR__ . '/../../.env';
            $env = [];
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || strpos($line, '#') === 0) {
                        continue;
                    }
                    if (strpos($line, '=') !== false) {
                        list($key, $value) = explode('=', $line, 2);
                        $key = trim($key);
                        $value = trim($value);
                        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                            $value = substr($value, 1, -1);
                        }
                        if (!empty($key)) {
                            $env[$key] = $value;
                        }
                    }
                }
            }

            // Lấy cấu hình email từ .env
            $smtp_host = $env['SMTP_HOST'] ?? 'smtp.gmail.com';
            $smtp_port = intval($env['SMTP_PORT'] ?? 465);
            $smtp_user = $env['EMAIL_USERNAME'] ?? '';
            $smtp_pass = $env['EMAIL_PASSWORD'] ?? '';
            $smtp_from_email = $env['SMTP_FROM_EMAIL'] ?? $smtp_user;
            $smtp_from_name = $env['SMTP_FROM_NAME'] ?? 'OceanPearl Hotel';
            $smtp_secure = $env['SMTP_SECURE'] ?? 'ssl'; // ssl hoặc tls

            // Kiểm tra cấu hình
            if (empty($smtp_user) || empty($smtp_pass)) {
                error_log("EmailHelper: SMTP_USER hoặc SMTP_PASS chưa được cấu hình trong .env");
                return false;
            }

            try {
                self::$mailer = new PHPMailer(true);

                // Server settings
                self::$mailer->SMTPDebug = 0; // Đặt 2 để debug (0 = off, 1 = client, 2 = client + server)
                self::$mailer->isSMTP();
                self::$mailer->Host = $smtp_host;
                self::$mailer->SMTPAuth = true;
                self::$mailer->Username = $smtp_user;
                self::$mailer->Password = $smtp_pass;
                self::$mailer->SMTPSecure = ($smtp_secure === 'tls') ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
                self::$mailer->Port = $smtp_port;
                self::$mailer->CharSet = 'UTF-8';
                self::$mailer->Encoding = 'base64';
                self::$mailer->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                // Default from
                self::$mailer->setFrom($smtp_from_email, $smtp_from_name);
            } catch (Exception $e) {
                error_log("EmailHelper init error: " . $e->getMessage());
                error_log("EmailHelper init stack trace: " . $e->getTraceAsString());
                return false;
            }
        }
        return self::$mailer;
    }

    /**
     * Gửi email
     * 
     * @param string $to Email người nhận
     * @param string $toName Tên người nhận
     * @param string $subject Tiêu đề email
     * @param string $body Nội dung email (HTML)
     * @param string $altBody Nội dung text (optional)
     * @return bool True nếu gửi thành công
     */
    public static function send($to, $toName, $subject, $body, $altBody = '')
    {
        $mail = self::init();
        if (!$mail) {
            error_log("EmailHelper send: Không thể khởi tạo PHPMailer");
            return false;
        }

        try {
            $mail->clearAddresses();
            $mail->clearAttachments();
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            if (!empty($altBody)) {
                $mail->AltBody = $altBody;
            }

            $result = $mail->send();
            if ($result) {
                error_log("Email sent successfully to: $to");
            } else {
                error_log("Email send failed. ErrorInfo: " . $mail->ErrorInfo);
            }
            return $result;
        } catch (Exception $e) {
            error_log("Email send exception: " . $e->getMessage());
            error_log("Email ErrorInfo: " . ($mail ? $mail->ErrorInfo : 'N/A'));
            return false;
        }
    }

    /**
     * Gửi mã reset password
     * 
     * @param string $to Email người nhận
     * @param string $toName Tên người nhận
     * @param string $token Mã reset (6 chữ số)
     * @return bool
     */
    public static function sendPasswordResetCode($to, $toName, $token)
    {
        $subject = 'Mã xác nhận đặt lại mật khẩu - OceanPearl Hotel';
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #deb666; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                .code-box { background: white; border: 2px dashed #deb666; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px; }
                .code { font-size: 32px; font-weight: bold; color: #deb666; letter-spacing: 5px; font-family: 'Courier New', monospace; user-select: all; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>OceanPearl Hotel</h2>
                </div>
                <div class='content'>
                    <p>Xin chào <strong>{$toName}</strong>,</p>
                    <p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản của mình.</p>
                    <p>Mã xác nhận của bạn là:</p>
                    <div class='code-box'>
                        <div class='code' style='font-family: Courier New, monospace; letter-spacing: 8px;'>{$token}</div>
                        <p style='font-size: 14px; color: #333; margin-top: 15px; font-weight: bold;'>Hoặc copy mã này: <span style='background: #f0f0f0; padding: 5px 10px; border-radius: 4px; font-family: monospace;'>{$token}</span></p>
                        <p style='font-size: 12px; color: #999; margin-top: 10px;'>Mã gồm 6 chữ số, không có khoảng trắng</p>
                    </div>
                    <p>Mã này có hiệu lực trong <strong>2 phút</strong>.</p>
                    <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " OceanPearl Hotel. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";

        $altBody = "Mã xác nhận đặt lại mật khẩu của bạn là: {$token}\n\nMã này có hiệu lực trong 15 phút.";

        return self::send($to, $toName, $subject, $body, $altBody);
    }

    /**
     * Gửi email xác nhận booking phòng
     * 
     * @param string $to Email người nhận
     * @param string $toName Tên người nhận
     * @param array $bookingData Thông tin booking
     * @return bool
     */
    public static function sendRoomBookingConfirmation($to, $toName, $bookingData)
    {
        $subject = 'Xác nhận đặt phòng thành công - OceanPearl Hotel';
        
        $checkIn = date('d/m/Y', strtotime($bookingData['check_in_date']));
        $checkOut = date('d/m/Y', strtotime($bookingData['check_out_date']));
        $bookingDate = date('d/m/Y H:i', strtotime($bookingData['booking_date']));
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #deb666; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                .info-box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #deb666; }
                .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
                .info-label { font-weight: bold; color: #666; }
                .info-value { color: #333; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Đặt phòng thành công!</h2>
                </div>
                <div class='content'>
                    <p>Xin chào <strong>{$toName}</strong>,</p>
                    <p>Cảm ơn bạn đã đặt phòng tại OceanPearl Hotel. Thông tin đặt phòng của bạn:</p>
                    
                    <div class='info-box'>
                        <div class='info-row'>
                            <span class='info-label'>Mã đặt phòng:</span>
                            <span class='info-value'>#{$bookingData['booking_id']}</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Số phòng:</span>
                            <span class='info-value'>{$bookingData['room_number']}</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Ngày nhận phòng:</span>
                            <span class='info-value'>{$checkIn}</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Ngày trả phòng:</span>
                            <span class='info-value'>{$checkOut}</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Số lượng khách:</span>
                            <span class='info-value'>{$bookingData['quantity']}</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Ngày đặt:</span>
                            <span class='info-value'>{$bookingDate}</span>
                        </div>
                    </div>
                    
                    <p>Chúng tôi rất mong được phục vụ bạn!</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " OceanPearl Hotel. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";

        $altBody = "Đặt phòng thành công!\n\nMã đặt phòng: #{$bookingData['booking_id']}\nSố phòng: {$bookingData['room_number']}\nNgày nhận: {$checkIn}\nNgày trả: {$checkOut}";

        return self::send($to, $toName, $subject, $body, $altBody);
    }

    /**
     * Gửi email xác nhận booking dịch vụ
     * 
     * @param string $to Email người nhận
     * @param string $toName Tên người nhận
     * @param array $bookingData Thông tin booking
     * @return bool
     */
    public static function sendServiceBookingConfirmation($to, $toName, $bookingData)
    {
        $subject = 'Xác nhận đặt dịch vụ thành công - OceanPearl Hotel';
        
        $bookingDate = date('d/m/Y H:i', strtotime($bookingData['booking_date']));
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #deb666; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                .info-box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #deb666; }
                .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
                .info-label { font-weight: bold; color: #666; }
                .info-value { color: #333; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Đặt dịch vụ thành công!</h2>
                </div>
                <div class='content'>
                    <p>Xin chào <strong>{$toName}</strong>,</p>
                    <p>Cảm ơn bạn đã đặt dịch vụ tại OceanPearl Hotel. Thông tin đặt dịch vụ của bạn:</p>
                    
                    <div class='info-box'>
                        <div class='info-row'>
                            <span class='info-label'>Mã đặt dịch vụ:</span>
                            <span class='info-value'>#{$bookingData['booking_service_id']}</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Tên dịch vụ:</span>
                            <span class='info-value'>{$bookingData['service_name']}</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Số lượng:</span>
                            <span class='info-value'>{$bookingData['quantity']}</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Giá:</span>
                            <span class='info-value'>" . number_format($bookingData['unit_price'], 0, ',', '.') . " VNĐ</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Ngày đặt:</span>
                            <span class='info-value'>{$bookingDate}</span>
                        </div>
                    </div>
                    
                    <p>Chúng tôi rất mong được phục vụ bạn!</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " OceanPearl Hotel. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";

        $altBody = "Đặt dịch vụ thành công!\n\nMã đặt: #{$bookingData['booking_service_id']}\nDịch vụ: {$bookingData['service_name']}\nSố lượng: {$bookingData['quantity']}";

        return self::send($to, $toName, $subject, $body, $altBody);
    }
}