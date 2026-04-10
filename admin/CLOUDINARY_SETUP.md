# Hướng Dẫn Cấu Hình Cloudinary

## Bước 1: Cài đặt Cloudinary PHP SDK

Chạy lệnh sau trong thư mục gốc của project:

```bash
composer require cloudinary/cloudinary_php
```

## Bước 2: Lấy Cloudinary Credentials

1. Đăng ký tài khoản tại: https://cloudinary.com/
2. Vào Dashboard và lấy các thông tin sau:
   - Cloud Name
   - API Key
   - API Secret

## Bước 3: Cấu hình Cloudinary

Mở file `admin/includes/cloudinary_helper.php` và uncomment phần code trong hàm `init()`:

```php
private static function init() {
    if (self::$cloudinary === null) {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => 'your_cloud_name',      // Thay bằng Cloud Name của bạn
                'api_key' => 'your_api_key',            // Thay bằng API Key của bạn
                'api_secret' => 'your_api_secret'        // Thay bằng API Secret của bạn
            ],
            'url' => [
                'secure' => true
            ]
        ]);
        
        self::$cloudinary = new Cloudinary();
    }
    return self::$cloudinary;
}
```

**Lưu ý bảo mật:** Nên lưu credentials trong file config riêng hoặc biến môi trường, không hardcode trực tiếp trong code.

### Cách 1: Sử dụng file config

Tạo file `admin/includes/cloudinary_config.php`:

```php
<?php
return [
    'cloud_name' => 'your_cloud_name',
    'api_key' => 'your_api_key',
    'api_secret' => 'your_api_secret'
];
```

Sau đó trong `cloudinary_helper.php`:

```php
$config = require __DIR__ . '/cloudinary_config.php';
Configuration::instance([
    'cloud' => [
        'cloud_name' => $config['cloud_name'],
        'api_key' => $config['api_key'],
        'api_secret' => $config['api_secret']
    ],
    'url' => [
        'secure' => true
    ]
]);
```

### Cách 2: Sử dụng biến môi trường

Thêm vào file `.env`:

```
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
```

Sau đó trong `cloudinary_helper.php`:

```php
Configuration::instance([
    'cloud' => [
        'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'],
        'api_key' => $_ENV['CLOUDINARY_API_KEY'],
        'api_secret' => $_ENV['CLOUDINARY_API_SECRET']
    ],
    'url' => [
        'secure' => true
    ]
]);
```

## Bước 4: Uncomment code trong các hàm

Sau khi cấu hình xong, uncomment các phần code trong các hàm:
- `upload()`
- `delete()`
- `transform()`

## Bước 5: Test upload

Sau khi cấu hình xong, thử upload ảnh để kiểm tra xem đã hoạt động chưa.

## Các folder trên Cloudinary

Hệ thống sẽ tự động tạo các folder sau trên Cloudinary:
- `staff` - Ảnh nhân viên
- `voucher` - Ảnh voucher
- `blog` - Thumbnail blog
- `room` - Ảnh phòng (có thể nhiều ảnh/phòng)
- `room-type` - Ảnh loại phòng
- `service` - Ảnh dịch vụ

## Lưu ý

- File `cloudinary_helper.php` đã được tích hợp vào tất cả các hàm upload
- Khi upload ảnh mới, ảnh cũ sẽ tự động bị xóa trên Cloudinary
- Ảnh phòng có thể có tối đa 5 ảnh, được lưu trong bảng `room_images`
- Ảnh đầu tiên của phòng sẽ được đánh dấu là `is_primary = 1`

