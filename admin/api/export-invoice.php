<?php
session_start();
require_once __DIR__ . '/../includes/connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['id_nhan_vien'])) {
    http_response_code(401);
    die('Unauthorized');
}

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($invoice_id <= 0) {
    http_response_code(400);
    die('Invalid invoice ID');
}

// Lấy thông tin hóa đơn
$stmt = $mysqli->prepare("
    SELECT i.*, 
           COALESCE(c.full_name, w.full_name) as full_name,
           COALESCE(c.phone, w.phone) as phone,
           COALESCE(c.email, w.email) as email,
           b.booking_id, b.check_in_date, b.check_out_date, b.walk_in_guest_id,
           nv.ho_ten as staff_name, nv.ma_nhan_vien as staff_code
    FROM invoice i
    LEFT JOIN customer c ON i.customer_id = c.customer_id AND i.customer_id IS NOT NULL AND i.customer_id > 0
    LEFT JOIN booking b ON i.booking_id = b.booking_id
    LEFT JOIN walk_in_guest w ON b.walk_in_guest_id = w.id
    LEFT JOIN nhan_vien nv ON nv.id_nhan_vien = ?
    WHERE i.invoice_id = ? AND i.deleted IS NULL
    LIMIT 1
");
$staff_id = $_SESSION['id_nhan_vien'];
$stmt->bind_param("ii", $staff_id, $invoice_id);
$stmt->execute();
$result = $stmt->get_result();
$invoice = $result->fetch_assoc();
$stmt->close();

if (!$invoice) {
    http_response_code(404);
    die('Invoice not found');
}

// Nếu không có thông tin khách hàng từ booking và invoice không có customer_id,
// thử lấy từ booking_service (cho hóa đơn chỉ có dịch vụ)
if (empty($invoice['full_name']) && (empty($invoice['customer_id']) || $invoice['customer_id'] == 0)) {
    $walkInStmt = $mysqli->prepare("
        SELECT w.full_name, w.phone, w.email
        FROM invoice_service isv
        INNER JOIN booking_service bs ON isv.booking_service_id = bs.booking_service_id
        LEFT JOIN walk_in_guest w ON bs.walk_in_guest_id = w.id
        WHERE isv.invoice_id = ? AND bs.walk_in_guest_id IS NOT NULL
        LIMIT 1
    ");
    $walkInStmt->bind_param("i", $invoice_id);
    $walkInStmt->execute();
    $walkInResult = $walkInStmt->get_result();
    $walkInGuest = $walkInResult->fetch_assoc();
    $walkInStmt->close();
    
    if ($walkInGuest) {
        $invoice['full_name'] = $walkInGuest['full_name'];
        $invoice['phone'] = $walkInGuest['phone'];
        $invoice['email'] = $walkInGuest['email'];
    }
}

// Lấy danh sách dịch vụ
$servicesStmt = $mysqli->prepare("
    SELECT s.service_name, bs.quantity, bs.unit_price, (bs.quantity * bs.unit_price) as total
    FROM invoice_service isv
    INNER JOIN booking_service bs ON isv.booking_service_id = bs.booking_service_id
    INNER JOIN service s ON bs.service_id = s.service_id
    WHERE isv.invoice_id = ? AND bs.deleted IS NULL
");
$servicesStmt->bind_param("i", $invoice_id);
$servicesStmt->execute();
$servicesResult = $servicesStmt->get_result();
$services = $servicesResult->fetch_all(MYSQLI_ASSOC);
$servicesStmt->close();

// Sử dụng các hàm format có sẵn từ connect.php
// formatCurrency() thay vì formatMoney()
// formatDate(), formatDateTime() đã có sẵn

// Tạo HTML cho Word
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hóa Đơn #' . $invoice['invoice_id'] . '</title>
    <style>
        body { font-family: "Times New Roman", serif; font-size: 12pt; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { max-width: 150px; margin-bottom: 10px; }
        .company-name { font-size: 18pt; font-weight: bold; margin: 10px 0; }
        .slogan { font-style: italic; color: #666; margin-bottom: 5px; }
        .phone { margin-bottom: 20px; }
        .invoice-info { margin: 20px 0; }
        .invoice-title { font-size: 16pt; font-weight: bold; text-align: center; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { border: 1px solid #000; padding: 8px; text-align: left; }
        table th { background-color: #f0f0f0; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; }
        .footer { margin-top: 40px; }
        .signature { margin-top: 60px; }
        .signature-left { float: left; width: 45%; }
        .signature-right { float: right; width: 45%; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">OCEANPEARL HOTEL</div>
        <div class="slogan">Niềm vui của bạn là hạnh phúc của chúng tôi</div>
        <div class="phone">Điện thoại: 0123456789</div>
    </div>
    
    <div class="invoice-title">HÓA ĐƠN THANH TOÁN</div>
    
    <div class="invoice-info">
        <table>
            <tr>
                <td><strong>Mã Hóa Đơn:</strong></td>
                <td>#' . $invoice['invoice_id'] . '</td>
                <td><strong>Ngày Tạo:</strong></td>
                <td>' . formatDateTime($invoice['created_at']) . '</td>
            </tr>
            <tr>
                <td><strong>Booking ID:</strong></td>
                <td>' . ($invoice['booking_id'] ? '#' . $invoice['booking_id'] : '-') . '</td>
                <td><strong>Ngày Thanh Toán:</strong></td>
                <td>' . ($invoice['payment_time'] ? formatDateTime($invoice['payment_time']) : '-') . '</td>
            </tr>
        </table>
    </div>
    
    <div class="invoice-info">
        <h3>Thông Tin Khách Hàng</h3>
        <table>
            <tr>
                <td><strong>Họ Tên:</strong></td>
                <td>' . htmlspecialchars($invoice['full_name'] ?? '-') . '</td>
                <td><strong>Điện Thoại:</strong></td>
                <td>' . htmlspecialchars($invoice['phone'] ?? '-') . '</td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td colspan="3">' . htmlspecialchars($invoice['email'] ?? '-') . '</td>
            </tr>
        </table>
    </div>';

if ($invoice['booking_id']) {
    $html .= '
    <div class="invoice-info">
        <h3>Thông Tin Đặt Phòng</h3>
        <table>
            <tr>
                <td><strong>Ngày Nhận Phòng:</strong></td>
                <td>' . formatDate($invoice['check_in_date']) . '</td>
                <td><strong>Ngày Trả Phòng:</strong></td>
                <td>' . formatDate($invoice['check_out_date']) . '</td>
            </tr>
        </table>
    </div>';
}

$html .= '
    <div class="invoice-info">
        <h3>Chi Tiết Hóa Đơn</h3>
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mô Tả</th>
                    <th class="text-right">Số Lượng</th>
                    <th class="text-right">Đơn Giá</th>
                    <th class="text-right">Thành Tiền</th>
                </tr>
            </thead>
            <tbody>';

$stt = 1;
$total = 0;

// Phí phòng
if ($invoice['room_charge'] > 0) {
    $html .= '
                <tr>
                    <td>' . $stt++ . '</td>
                    <td>Phí Phòng</td>
                    <td class="text-right">1</td>
                    <td class="text-right">' . formatCurrency($invoice['room_charge']) . '</td>
                    <td class="text-right">' . formatCurrency($invoice['room_charge']) . '</td>
                </tr>';
    $total += $invoice['room_charge'];
}

// Dịch vụ
foreach ($services as $service) {
    $html .= '
                <tr>
                    <td>' . $stt++ . '</td>
                    <td>' . htmlspecialchars($service['service_name']) . '</td>
                    <td class="text-right">' . $service['quantity'] . '</td>
                    <td class="text-right">' . formatCurrency($service['unit_price']) . '</td>
                    <td class="text-right">' . formatCurrency($service['total']) . '</td>
                </tr>';
    $total += $service['total'];
}

// VAT
if ($invoice['vat'] > 0) {
    $html .= '
                <tr>
                    <td>' . $stt++ . '</td>
                    <td>VAT (10%)</td>
                    <td class="text-right">1</td>
                    <td class="text-right">' . formatCurrency($invoice['vat']) . '</td>
                    <td class="text-right">' . formatCurrency($invoice['vat']) . '</td>
                </tr>';
    $total += $invoice['vat'];
}

// Phí khác
if ($invoice['other_fees'] > 0) {
    $html .= '
                <tr>
                    <td>' . $stt++ . '</td>
                    <td>Phí Khác</td>
                    <td class="text-right">1</td>
                    <td class="text-right">' . formatCurrency($invoice['other_fees']) . '</td>
                    <td class="text-right">' . formatCurrency($invoice['other_fees']) . '</td>
                </tr>';
    $total += $invoice['other_fees'];
}

$html .= '
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>TỔNG CỘNG:</strong></td>
                    <td class="text-right"><strong>' . formatCurrency($invoice['total_amount']) . '</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="invoice-info">
        <table>
            <tr>
                <td><strong>Hình Thức Thanh Toán:</strong></td>
                <td>' . htmlspecialchars($invoice['payment_method'] ?? '-') . '</td>
                <td><strong>Trạng Thái:</strong></td>
                <td>' . ($invoice['status'] == 'Paid' ? 'Đã Thanh Toán' : ($invoice['status'] == 'Unpaid' ? 'Chưa Thanh Toán' : $invoice['status'])) . '</td>
            </tr>';

if ($invoice['note']) {
    $html .= '
            <tr>
                <td colspan="4"><strong>Ghi Chú:</strong> ' . nl2br(htmlspecialchars($invoice['note'])) . '</td>
            </tr>';
}

$html .= '
        </table>
    </div>
    
    <div class="footer">
        <div class="signature">
            <div class="signature-left">
                <p><strong>Người Xuất Hóa Đơn:</strong></p>
                <p>' . htmlspecialchars($invoice['staff_name'] ?? '-') . '</p>
                <p>(' . htmlspecialchars($invoice['staff_code'] ?? '-') . ')</p>
            </div>
            <div class="signature-right">
                <p><strong>Ngày Xuất:</strong></p>
                <p>' . date('d/m/Y H:i') . '</p>
            </div>
        </div>
    </div>
</body>
</html>';

// Set headers để download Word
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="HoaDon_' . $invoice['invoice_id'] . '_' . date('YmdHis') . '.doc"');
header('Cache-Control: max-age=0');

echo $html;
exit;
?>
