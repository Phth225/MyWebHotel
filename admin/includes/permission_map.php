<?php
/**
 * Định nghĩa mapping giữa route/page trong admin và quyền cần thiết.
 * Mỗi phần tử là một mảng các rule, chỉ cần thoả 1 rule là vào được.
 * Rule hỗ trợ:
 *  - type = exact  : so khớp chính xác tên quyền
 *  - type = prefix : so khớp quyền bắt đầu bằng chuỗi cho trước (không phân biệt hoa thường)
 */
return [
    'room-manager' => [
        ['type' => 'prefix', 'value' => 'Quản Lý Phòng'],
        ['type' => 'prefix', 'value' => 'Quản lý phòng'],
        ['type' => 'prefix', 'value' => 'room.'],
        ['type' => 'prefix', 'value' => 'roomType.'],
    ],
    'services-manager' => [
        ['type' => 'prefix', 'value' => 'Quản Lý Dịch Vụ'],
        ['type' => 'prefix', 'value' => 'Quản lý dịch vụ'],
        ['type' => 'prefix', 'value' => 'service.'],
    ],
    'invoices-manager' => [
        ['type' => 'prefix', 'value' => 'Quản Lý Hóa Đơn'],
        ['type' => 'prefix', 'value' => 'Quản lý hóa đơn'],
        ['type' => 'prefix', 'value' => 'Hóa Đơn'],
        ['type' => 'prefix', 'value' => 'invoice.'],
    ],
    'booking-manager' => [
        ['type' => 'prefix', 'value' => 'Quản Lý Booking'],
        ['type' => 'prefix', 'value' => 'Quản lý booking'],
        ['type' => 'prefix', 'value' => 'booking.'],
    ],
    'customers-manager' => [
        ['type' => 'prefix', 'value' => 'Quản Lý Khách Hàng'],
        ['type' => 'prefix', 'value' => 'Quản lý khách hàng'],
        ['type' => 'prefix', 'value' => 'customer.'],
    ],
    'staff-manager' => [
        ['type' => 'prefix', 'value' => 'Quản Lý Nhân Viên'],
        ['type' => 'prefix', 'value' => 'Quản lý nhân viên'],
        ['type' => 'prefix', 'value' => 'employee.'],
    ],
    'permission-manager' => [
        ['type' => 'exact', 'value' => 'employee.set_permission'],
    ],
    'task-manager' => [
        ['type' => 'exact', 'value' => 'task.view'],
        ['type' => 'prefix', 'value' => 'Task'],
        ['type' => 'prefix', 'value' => 'task.'],
    ],
    'reports-manager' => [
        ['type' => 'prefix', 'value' => 'Thống Kê'],
        ['type' => 'prefix', 'value' => 'Thống kê'],
        ['type' => 'prefix', 'value' => 'report.'],
    ],
    'blogs-manager' => [
        ['type' => 'prefix', 'value' => 'Blog'],
        ['type' => 'prefix', 'value' => 'Review'],
        ['type' => 'prefix', 'value' => 'blog.'],
        ['type' => 'prefix', 'value' => 'content.'],
        ['type' => 'prefix', 'value' => 'review.'],
    ],
    'my-tasks' => [
        // Tất cả nhân viên đều có thể xem nhiệm vụ của mình
        ['type' => 'exact', 'value' => 'task.view'],
        ['type' => 'prefix', 'value' => 'task.'],
    ],
    'voucher-manager' => [
        ['type' => 'prefix', 'value' => 'Quản Lý Voucher'],
        ['type' => 'prefix', 'value' => 'Quản lý voucher'],
        ['type' => 'prefix', 'value' => 'voucher.'],
    ],
    'calendar-booking' => [
        ['type' => 'exact', 'value' => 'service.calendar.view'],
        ['type' => 'prefix', 'value' => 'service.calendar.'],
        ['type' => 'prefix', 'value' => 'calendar.'],
        ['type' => 'prefix', 'value' => 'Lịch Đặt Phòng'],
        ['type' => 'prefix', 'value' => 'Lịch đặt phòng'],
        ['type' => 'prefix', 'value' => 'Quản Lý Booking'], // Fallback
        ['type' => 'prefix', 'value' => 'Quản lý booking'], // Fallback
        ['type' => 'prefix', 'value' => 'booking.'], // Fallback
    ],
    'calendar-booking-panel' => [
        ['type' => 'exact', 'value' => 'service.calendar.view'],
        ['type' => 'prefix', 'value' => 'service.calendar.'],
        ['type' => 'prefix', 'value' => 'calendar.'],
        ['type' => 'prefix', 'value' => 'Lịch Đặt Phòng'],
        ['type' => 'prefix', 'value' => 'Lịch đặt phòng'],
        ['type' => 'prefix', 'value' => 'Quản Lý Booking'], // Fallback
        ['type' => 'prefix', 'value' => 'Quản lý booking'], // Fallback
        ['type' => 'prefix', 'value' => 'booking.'], // Fallback
    ],
];

