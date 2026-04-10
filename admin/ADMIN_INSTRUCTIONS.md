# Hướng Dẫn Hệ Thống Quản Trị (Admin Panel)

## Mục Lục
1. [Tổng Quan](#tổng-quan)
2. [Cấu Trúc Thư Mục](#cấu-trúc-thư-mục)
3. [Xác Thực và Phân Quyền](#xác-thực-và-phân-quyền)
4. [Các Module Chính](#các-module-chính)
5. [Upload Ảnh với Cloudinary](#upload-ảnh-với-cloudinary)
6. [Database Schema](#database-schema)
7. [API Endpoints](#api-endpoints)
8. [Frontend Components](#frontend-components)
9. [Cấu Hình và Setup](#cấu-hình-và-setup)

---

## Tổng Quan

Hệ thống quản trị là một ứng dụng web PHP quản lý khách sạn, bao gồm:
- Quản lý phòng và loại phòng
- Quản lý đặt phòng (Booking)
- Quản lý hóa đơn
- Quản lý khách hàng
- Quản lý nhân viên và phân quyền
- Quản lý dịch vụ
- Quản lý voucher
- Quản lý blog và review
- Thống kê và báo cáo
- Quản lý nhiệm vụ

---

## Cấu Trúc Thư Mục

```
admin/
├── index.php                 # Entry point chính
├── core/                     # Core system files
│   ├── Router.php           # Routing system
│   └── BaseModel.php        # Base model class
├── controllers/             # MVC Controllers
│   ├── HomeController.php
│   ├── ProfileController.php
│   └── TaskController.php
├── pages/                    # View files (Pages)
│   ├── home.php             # Dashboard
│   ├── room-manager.php     # Quản lý phòng
│   ├── room-panel.php       # Panel danh sách phòng
│   ├── roomType-panel.php   # Panel loại phòng
│   ├── booking-manager.php  # Quản lý đặt phòng
│   ├── invoices-manager.php # Quản lý hóa đơn
│   ├── customers-manager.php # Quản lý khách hàng
│   ├── staff-panel.php      # Quản lý nhân viên
│   ├── services-manager.php # Quản lý dịch vụ
│   ├── voucher-manager.php  # Quản lý voucher
│   ├── blogs-manager.php    # Quản lý blog
│   ├── my-tasks.php         # Nhiệm vụ của tôi
│   └── profile.php          # Hồ sơ cá nhân
├── includes/                 # Shared includes
│   ├── connect.php          # Database connection
│   ├── auth.php             # Authentication
│   ├── header.php           # Header template
│   ├── sidebar.php          # Sidebar navigation
│   ├── footer.php           # Footer template
│   ├── cloudinary_helper.php # Cloudinary helper
│   └── permission_map.php  # Permission mapping
├── api/                      # API endpoints
│   ├── staff-api.php
│   ├── room-api.php
│   └── voucher-api.php
├── assets/                   # Static assets
│   ├── css/                 # Stylesheets
│   ├── js/                  # JavaScript files
│   └── images/              # Local images (deprecated - dùng Cloudinary)
└── ADMIN_INSTRUCTIONS.md    # File này

```

---

## Xác Thực và Phân Quyền

### Authentication (`includes/auth.php`)

**Chức năng:**
- Kiểm tra đăng nhập qua session
- Redirect về trang login nếu chưa đăng nhập
- Lưu thông tin nhân viên trong `$_SESSION`

**Session Variables:**
- `id_nhan_vien`: ID nhân viên
- `ho_ten`: Họ tên
- `email`: Email
- `chuc_vu`: Chức vụ
- `anh_dai_dien`: Avatar URL

### Authorization (`includes/permission_map.php`)

**Hệ thống phân quyền 3 cấp:**
1. **Quyền theo chức vụ** (`quyen_chuc_vu`): Quyền mặc định của chức vụ
2. **Quyền riêng nhân viên** (`quyen_nhan_vien`): Quyền bổ sung cho từng nhân viên
3. **Quyền module** (`permission_map.php`): Mapping quyền với các module

**Các quyền chính:**
- `room.view`, `room.create`, `room.edit`, `room.delete`
- `booking.view`, `booking.create`, `booking.edit`, `booking.delete`
- `invoice.view`, `invoice.create`, `invoice.edit`, `invoice.delete`
- `customer.view`, `customer.create`, `customer.edit`, `customer.delete`
- `staff.view`, `staff.create`, `staff.edit`, `staff.delete`
- `service.view`, `service.create`, `service.edit`, `service.delete`
- `voucher.view`, `voucher.create`, `voucher.edit`, `voucher.delete`
- `blog.view`, `blog.create`, `blog.edit`, `blog.delete`
- `task.view`, `task.create`, `task.edit`, `task.delete`

**Cách sử dụng:**
```php
// Kiểm tra quyền trong page
$canViewRoom = checkPermission('room.view');
$canCreateRoom = checkPermission('room.create');

// Kiểm tra quyền truy cập section
if (!canAccessSection('room-manager')) {
    include 'pages/403.php';
    return;
}
```

---

## Các Module Chính

### 1. Quản Lý Phòng (`room-manager.php`, `room-panel.php`)

**Chức năng:**
- Xem danh sách phòng với phân trang và tìm kiếm
- Thêm phòng mới
- Sửa thông tin phòng
- Xóa phòng (soft delete)
- Upload nhiều ảnh (tối đa 5 ảnh/phòng)

**Database:**
- Bảng `room`: Thông tin phòng
- Bảng `room_images`: Ảnh phòng (1 phòng có thể có nhiều ảnh)
- Bảng `room_type`: Loại phòng

**Luồng hoạt động:**
1. **Thêm phòng:**
   - Validate số phòng không trùng
   - INSERT vào `room`
   - Upload ảnh lên Cloudinary
   - Lưu URL ảnh vào `room_images` (ảnh đầu tiên là `is_primary = 1`)

2. **Sửa phòng:**
   - Validate số phòng không trùng (trừ phòng hiện tại)
   - UPDATE thông tin phòng
   - Xử lý ảnh:
     - Giữ lại ảnh cũ (nếu có trong `existing_images[]`)
     - Upload ảnh mới (thêm vào, không thay thế)
     - Xóa ảnh không được chọn

3. **Xem danh sách:**
   - Query với JOIN `room_type` để lấy thông tin loại phòng
   - JOIN `room_images` để lấy ảnh chính
   - Phân trang và filter theo status, type

### 2. Quản Lý Loại Phòng (`roomType-panel.php`)

**Chức năng:**
- Quản lý các loại phòng (Standard, Deluxe, Suite, etc.)
- Mỗi loại phòng có: tên, mô tả, giá cơ bản, sức chứa, diện tích, tiện nghi, ảnh

**Database:**
- Bảng `room_type`: Thông tin loại phòng

### 3. Quản Lý Đặt Phòng (`booking-manager.php`)

**Chức năng:**
- Tạo booking mới
- Xem danh sách booking
- Cập nhật trạng thái booking
- Tự động tạo hóa đơn khi check-in

**Database:**
- Bảng `booking`: Thông tin đặt phòng
- Bảng `invoice`: Hóa đơn (tự động tạo)

### 4. Quản Lý Hóa Đơn (`invoices-manager.php`)

**Chức năng:**
- Xem danh sách hóa đơn
- Tạo hóa đơn thủ công
- Cập nhật trạng thái thanh toán
- In hóa đơn

**Database:**
- Bảng `invoice`: Hóa đơn phòng
- Bảng `service_invoice`: Hóa đơn dịch vụ

### 5. Quản Lý Khách Hàng (`customers-manager.php`)

**Chức năng:**
- Thêm/sửa/xóa khách hàng
- Xem lịch sử booking của khách
- Tìm kiếm khách hàng

**Database:**
- Bảng `customer`: Thông tin khách hàng

### 6. Quản Lý Nhân Viên (`staff-panel.php`)

**Chức năng:**
- Thêm/sửa/xóa nhân viên
- Phân quyền cho nhân viên
- Upload avatar lên Cloudinary
- Xem nhiệm vụ của nhân viên

**Database:**
- Bảng `nhan_vien`: Thông tin nhân viên
- Bảng `quyen`: Danh sách quyền
- Bảng `quyen_chuc_vu`: Quyền theo chức vụ
- Bảng `quyen_nhan_vien`: Quyền riêng nhân viên

### 7. Quản Lý Dịch Vụ (`services-manager.php`)

**Chức năng:**
- Quản lý các dịch vụ khách sạn
- Upload ảnh dịch vụ lên Cloudinary
- Quản lý giá và đơn vị

**Database:**
- Bảng `service`: Thông tin dịch vụ

### 8. Quản Lý Voucher (`voucher-manager.php`)

**Chức năng:**
- Tạo voucher giảm giá
- Upload banner voucher lên Cloudinary
- Quản lý thời gian hiệu lực
- Theo dõi số lần sử dụng

**Database:**
- Bảng `voucher`: Thông tin voucher

### 9. Quản Lý Blog (`blogs-manager.php`, `blog-panel.php`)

**Chức năng:**
- Viết và quản lý bài blog
- Upload thumbnail lên Cloudinary
- Quản lý category và status
- Xem review và rating

**Database:**
- Bảng `blog`: Bài viết blog
- Bảng `review`: Đánh giá từ khách hàng

### 10. Nhiệm Vụ (`my-tasks.php`, `task-manager.php`)

**Chức năng:**
- Nhân viên xem nhiệm vụ được giao
- Cập nhật tiến độ nhiệm vụ
- Quản lý giao nhiệm vụ cho nhân viên

**Database:**
- Bảng `nhiem_vu`: Nhiệm vụ
- Bảng `lich_su_thay_doi`: Lịch sử thay đổi nhiệm vụ

**Luồng hoạt động:**
1. Quản lý giao nhiệm vụ → Lưu vào `nhiem_vu`
2. Nhân viên xem nhiệm vụ → Filter theo `id_nhan_vien_duoc_gan`
3. Cập nhật tiến độ → UPDATE `tien_do_hoan_thanh` và `trang_thai`
4. Ghi lịch sử → INSERT vào `lich_su_thay_doi`

### 11. Dashboard (`home.php`)

**Chức năng:**
- Hiển thị thống kê tổng quan
- Biểu đồ doanh thu
- Danh sách booking gần đây
- **Nhân viên**: Hiển thị trang "Nhiệm vụ của tôi"
- **Quản lý**: Hiển thị dashboard với thống kê

**Logic phân biệt:**
```php
$isManager = (stripos($chuc_vu, 'Quản lý') !== false || 
              stripos($chuc_vu, 'Manager') !== false || 
              stripos($chuc_vu, 'Admin') !== false);

if (!$isManager) {
    // Nhân viên: hiển thị my-tasks.php
    include 'my-tasks.php';
} else {
    // Quản lý: hiển thị dashboard
    // ... thống kê ...
}
```

---

## Upload Ảnh với Cloudinary

### Cấu Hình

**File `.env` (ở thư mục gốc):**
```
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
```

**Hoặc sử dụng CLOUDINARY_URL:**
```
CLOUDINARY_URL=cloudinary://api_key:api_secret@cloud_name
```

### Helper Class (`includes/cloudinary_helper.php`)

**Các phương thức chính:**

1. **`CloudinaryHelper::upload($filePath, $folder, $options)`**
   - Upload ảnh lên Cloudinary
   - `$filePath`: Đường dẫn file tạm (`$_FILES['image']['tmp_name']`)
   - `$folder`: Folder trên Cloudinary ('staff', 'room', 'blog', 'voucher', 'service', 'room-type')
   - Trả về: URL ảnh trên Cloudinary hoặc `false` nếu lỗi

2. **`CloudinaryHelper::uploadMultiple($files, $folder, $options)`**
   - Upload nhiều ảnh cùng lúc
   - Trả về: Mảng các URLs

3. **`CloudinaryHelper::deleteByUrl($url)`**
   - Xóa ảnh từ URL Cloudinary
   - Tự động parse `public_id` từ URL

4. **`CloudinaryHelper::getPublicIdFromUrl($url)`**
   - Lấy public_id từ URL Cloudinary

### Cách Sử Dụng

**Upload ảnh đơn:**
```php
require_once __DIR__ . '/../includes/cloudinary_helper.php';

if (isset($_FILES['avatar'])) {
    $uploadedUrl = CloudinaryHelper::upload(
        $_FILES['avatar']['tmp_name'], 
        'staff'
    );
    
    if ($uploadedUrl !== false) {
        // Lưu $uploadedUrl vào database
        $stmt = $mysqli->prepare("UPDATE nhan_vien SET anh_dai_dien = ? WHERE id_nhan_vien = ?");
        $stmt->bind_param("si", $uploadedUrl, $id);
        $stmt->execute();
    }
}
```

**Upload nhiều ảnh (phòng):**
```php
function uploadRoomImages($files, $oldImages = '', $roomId = null) {
    global $mysqli;
    require_once __DIR__ . '/../includes/cloudinary_helper.php';
    
    $uploadedImageUrls = [];
    
    if (is_array($files['name'])) {
        // Multiple files
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] == UPLOAD_ERR_OK) {
                $file = [
                    'tmp_name' => $files['tmp_name'][$i],
                    'type' => $files['type'][$i],
                    'size' => $files['size'][$i]
                ];
                
                $url = CloudinaryHelper::upload($file['tmp_name'], 'room');
                if ($url !== false) {
                    $uploadedImageUrls[] = $url;
                }
            }
        }
    }
    
    // Lưu vào room_images
    if ($roomId && !empty($uploadedImageUrls)) {
        foreach ($uploadedImageUrls as $index => $imageUrl) {
            $isPrimary = ($index === 0) ? 1 : 0;
            $stmt = $mysqli->prepare("INSERT INTO room_images (room_id, image_url, is_primary, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isii", $roomId, $imageUrl, $isPrimary, $index);
            $stmt->execute();
        }
    }
    
    return $uploadedImageUrls[0] ?? false;
}
```

**Xóa ảnh cũ:**
```php
if (!empty($oldImageUrl)) {
    CloudinaryHelper::deleteByUrl($oldImageUrl);
}
```

### Hiển Thị Ảnh

**Vì tất cả ảnh đã được upload lên Cloudinary, URL trong database đã là Cloudinary URL đầy đủ, nên có thể sử dụng trực tiếp:**

```php
<img src="<?php echo h($nhanVien['anh_dai_dien']); ?>" alt="Avatar">
```

**Không cần xử lý thêm vì URL từ database đã là:**
- `https://res.cloudinary.com/cloud_name/image/upload/v1234567890/folder/image.jpg`

---

## Database Schema

### Bảng Chính

**`room`**: Thông tin phòng
- `room_id` (PK)
- `room_number` (UNIQUE)
- `floor`
- `room_type_id` (FK)
- `status` (Available, Booked, Occupied, Maintenance, Cleaning)
- `deleted` (timestamp, soft delete)

**`room_images`**: Ảnh phòng (1 phòng có nhiều ảnh)
- `id` (PK)
- `room_id` (FK)
- `image_url` (URL Cloudinary)
- `is_primary` (1=ảnh chính, 0=ảnh phụ)
- `sort_order` (Thứ tự hiển thị)
- `deleted` (timestamp, soft delete)

**`room_type`**: Loại phòng
- `room_type_id` (PK)
- `room_type_name`
- `description`
- `base_price`
- `capacity`
- `area`
- `amenities`
- `image` (URL Cloudinary)
- `status`
- `deleted`

**`booking`**: Đặt phòng
- `booking_id` (PK)
- `customer_id` (FK)
- `room_id` (FK)
- `check_in_date`
- `check_out_date`
- `status`
- `deleted`

**`invoice`**: Hóa đơn
- `invoice_id` (PK)
- `booking_id` (FK)
- `total_amount`
- `status` (Paid, Unpaid, Pending)
- `created_at`
- `deleted`

**`customer`**: Khách hàng
- `customer_id` (PK)
- `full_name`
- `email`
- `phone`
- `address`
- `deleted`

**`nhan_vien`**: Nhân viên
- `id_nhan_vien` (PK)
- `ma_nhan_vien` (UNIQUE)
- `ho_ten`
- `email`
- `dien_thoai`
- `anh_dai_dien` (URL Cloudinary)
- `chuc_vu`
- `phong_ban`
- `trang_thai`
- `deleted`

**`nhiem_vu`**: Nhiệm vụ
- `id_nhiem_vu` (PK)
- `ten_nhiem_vu`
- `mo_ta_chi_tiet`
- `id_nhan_vien_duoc_gan` (FK - người được giao)
- `id_nhan_vien_gan_phien` (FK - người giao)
- `muc_do_uu_tien` (Thấp, Trung bình, Cao, Khẩn cấp)
- `ngay_bat_dau`
- `han_hoan_thanh`
- `trang_thai` (Chưa bắt đầu, Đang thực hiện, Hoàn thành, Hủy)
- `tien_do_hoan_thanh` (0-100%)
- `ghi_chu`
- `created_at`
- `deleted`

**`quyen`**: Danh sách quyền
- `id_quyen` (PK)
- `ten_quyen`
- `mo_ta`

**`quyen_chuc_vu`**: Quyền theo chức vụ
- `id` (PK)
- `chuc_vu`
- `id_quyen` (FK)
- `trang_thai`

**`quyen_nhan_vien`**: Quyền riêng nhân viên
- `id` (PK)
- `id_nhan_vien` (FK)
- `id_quyen` (FK)
- `trang_thai`

**`service`**: Dịch vụ
- `service_id` (PK)
- `service_name`
- `description`
- `price`
- `unit`
- `image` (URL Cloudinary)
- `status`
- `deleted`

**`voucher`**: Voucher
- `voucher_id` (PK)
- `voucher_code` (UNIQUE)
- `voucher_name`
- `discount_type` (percentage, fixed)
- `discount_value`
- `image` (URL Cloudinary - banner)
- `start_date`
- `end_date`
- `usage_limit`
- `used_count`
- `status`
- `deleted`

**`blog`**: Blog
- `blog_id` (PK)
- `title`
- `slug`
- `description`
- `content`
- `category`
- `thumbnail` (URL Cloudinary)
- `status`
- `created_at`
- `deleted`

**`review`**: Đánh giá
- `review_id` (PK)
- `blog_id` (FK)
- `customer_id` (FK)
- `rating` (1-5)
- `comment`
- `created_at`
- `deleted`

---

## API Endpoints

### Staff API (`api/staff-api.php`)

**GET `?action=view&id={id}`**
- Xem chi tiết nhân viên
- Trả về JSON với thông tin nhân viên

**GET `?action=permissions&id={id}`**
- Lấy danh sách quyền và quyền của nhân viên
- Trả về: `permissions`, `rolePermissions`, `personalPermissions`

**POST `?action=save_permissions`**
- Lưu quyền riêng cho nhân viên
- Body: `id_nhan_vien`, `permissions[]`

**GET `?action=tasks&id={id}`**
- Lấy danh sách nhiệm vụ của nhân viên

**POST `?action=assign_task`**
- Giao nhiệm vụ cho nhân viên
- Body: `id_nhan_vien_duoc_gan`, `ten_nhiem_vu`, `mo_ta_chi_tiet`, `muc_do_uu_tien`, `ngay_bat_dau`, `han_hoan_thanh`, `ghi_chu`

**GET `?action=view_task&id={id}`**
- Xem chi tiết nhiệm vụ

**POST `?action=update_task`**
- Cập nhật nhiệm vụ
- Body: `id_nhiem_vu`, `ten_nhiem_vu`, `mo_ta_chi_tiet`, `muc_do_uu_tien`, `ngay_bat_dau`, `han_hoan_thanh`, `trang_thai`, `tien_do_hoan_thanh`

**POST `?action=delete_task`**
- Xóa nhiệm vụ (soft delete)
- Body: `id_nhiem_vu`

---

## Frontend Components

### Routing System (`core/Router.php`)

**Cách hoạt động:**
1. Kiểm tra route trong `$routes` array
2. Nếu có Controller → Gọi Controller method
3. Nếu không → Fallback về old pages structure

**Route mapping:**
```php
$routes = [
    'home' => 'HomeController@index',
    'profile' => 'ProfileController@index',
    'my-tasks' => 'TaskController@myTasks',
    // ...
];
```

### Sidebar Navigation (`includes/sidebar.php`)

**Tính năng:**
- Hiển thị avatar nhân viên (từ Cloudinary hoặc UI Avatars)
- Menu động theo quyền (`canAccessSection()`)
- Active state theo page hiện tại
- Dark mode toggle
- Collapse/expand sidebar

### Header (`includes/header.php`)

**Tính năng:**
- Title động theo page
- Load CSS/JS theo page
- Meta tags

### Footer (`includes/footer.php`)

**Tính năng:**
- Load JavaScript theo page
- Copyright info

### JavaScript Files

**`assets/js/room-manager.js`**
- Preview multiple images
- Form validation
- Modal management
- Auto-reset form

**`assets/js/staff-manager.js`**
- Staff CRUD operations
- Permission management
- Avatar preview

**`assets/js/task-manager.js`**
- Task assignment
- Progress update
- Task filtering

---

## Cấu Hình và Setup

### 1. Database Connection (`includes/connect.php`)

**Cấu hình:**
```php
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'hotel_management';
```

**Helper Functions:**
- `h($string)`: HTML escape
- `formatCurrency($amount)`: Format tiền VNĐ
- `formatDate($date)`: Format ngày
- `getPagination($total, $perPage, $currentPage, $baseUrl)`: Phân trang

### 2. Cloudinary Setup

**Bước 1:** Cài đặt Composer package
```bash
composer require cloudinary/cloudinary_php
```

**Bước 2:** Tạo file `.env` ở thư mục gốc
```
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
```

**Bước 3:** Load `.env` tự động trong `cloudinary_helper.php`

### 3. Session Configuration

**Session được bắt đầu trong:**
- `index.php` (nếu chưa có)
- `api/*.php` files

**Session timeout:** Mặc định PHP (thường 24 phút)

### 4. File Upload Limits

**PHP Configuration (`php.ini`):**
```
upload_max_filesize = 5M
post_max_size = 10M
max_file_uploads = 20
```

**Code validation:**
- Max size: 5MB mỗi ảnh
- Allowed types: JPEG, JPG, PNG, GIF, WEBP
- Max images per room: 5

### 5. Security

**XSS Protection:**
- Sử dụng `h()` function cho tất cả output
- `htmlspecialchars()` với `ENT_QUOTES`

**SQL Injection Protection:**
- Prepared statements cho tất cả queries
- `bind_param()` với type checking

**CSRF Protection:**
- Có thể thêm token (chưa implement)

**File Upload Security:**
- Validate file type
- Validate file size
- Upload lên Cloudinary (không lưu local)

---

## Luồng Hoạt Động Tổng Quan

### 1. Request Flow

```
User Request
    ↓
index.php
    ↓
Router.php (nếu dùng MVC)
    ↓
Controller hoặc Page
    ↓
includes/auth.php (kiểm tra đăng nhập)
    ↓
includes/header.php
    ↓
includes/sidebar.php
    ↓
Page content
    ↓
includes/footer.php
```

### 2. Upload Ảnh Flow

```
User chọn ảnh
    ↓
Form submit với enctype="multipart/form-data"
    ↓
PHP nhận $_FILES
    ↓
Validate (type, size)
    ↓
CloudinaryHelper::upload()
    ↓
Upload lên Cloudinary
    ↓
Nhận URL về
    ↓
Lưu URL vào database
    ↓
Hiển thị ảnh từ Cloudinary URL
```

### 3. Phân Quyền Flow

```
User đăng nhập
    ↓
Lưu thông tin vào $_SESSION
    ↓
Mỗi page kiểm tra:
    - checkPermission('module.action')
    - canAccessSection('module-name')
    ↓
Nếu không có quyền → 403 Forbidden
    ↓
Nếu có quyền → Hiển thị page
```

### 4. CRUD Flow (Ví dụ: Phòng)

**Create:**
```
Form submit → Validate → Check duplicate → INSERT room → Upload images → INSERT room_images → Success message
```

**Read:**
```
Query với JOIN → Filter/Search → Pagination → Display
```

**Update:**
```
Load data → Form pre-fill → User edit → Validate → Check duplicate → UPDATE room → Handle images → Success message
```

**Delete:**
```
Confirm → Soft delete (UPDATE deleted = NOW()) → Success message
```

---

## Best Practices

### 1. Code Organization
- Tách logic vào functions
- Sử dụng prepared statements
- Validate input
- Escape output

### 2. Error Handling
- Try-catch cho Cloudinary operations
- Error logging với `error_log()`
- User-friendly error messages

### 3. Performance
- Index database columns thường query
- Limit kết quả query
- Pagination cho danh sách dài
- Lazy load images

### 4. Security
- Prepared statements
- Input validation
- Output escaping
- File upload validation
- Session security

### 5. Maintainability
- Comment code phức tạp
- Consistent naming
- DRY (Don't Repeat Yourself)
- Modular structure

---

## Troubleshooting

### Lỗi thường gặp:

1. **"Undefined variable $mysqli"**
   - Sử dụng `global $mysqli` trong functions
   - Hoặc `use ($mysqli)` trong closures

2. **"Cloudinary credentials not found"**
   - Kiểm tra file `.env` có đúng format
   - Kiểm tra đường dẫn file `.env`
   - Kiểm tra biến môi trường có được load

3. **"Duplicate entry for key 'unique_room'"**
   - Đã có validation, nhưng nếu vẫn lỗi → Kiểm tra logic check duplicate

4. **"Image not displaying"**
   - Kiểm tra URL có đúng format Cloudinary không (bắt đầu bằng https://res.cloudinary.com)
   - Kiểm tra URL trong database có đầy đủ không
   - Kiểm tra CORS nếu load từ Cloudinary

5. **"Permission denied"**
   - Kiểm tra `permission_map.php`
   - Kiểm tra quyền trong database
   - Kiểm tra `canAccessSection()` function

