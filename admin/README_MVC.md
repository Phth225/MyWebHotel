# Hướng Dẫn Sử Dụng Mô Hình MVC

## Cấu Trúc Thư Mục

```
admin/
├── core/
│   ├── BaseController.php    # Base class cho tất cả controllers
│   ├── BaseModel.php          # Base class cho tất cả models
│   └── Router.php            # Router xử lý routing
├── controllers/
│   ├── HomeController.php
│   ├── BookingController.php
│   ├── CustomerController.php
│   ├── InvoiceController.php
│   ├── RoomController.php
│   ├── ServiceController.php
│   ├── StaffController.php
│   ├── TaskController.php
│   ├── ReportController.php
│   ├── BlogController.php
│   ├── PermissionController.php
│   ├── AuthController.php
│   └── ProfileController.php
├── models/
│   ├── BookingModel.php
│   ├── BookingServiceModel.php
│   ├── InvoiceModel.php
│   ├── CustomerModel.php
│   ├── StaffModel.php
│   ├── ServiceModel.php
│   ├── RoomModel.php
│   ├── RoomTypeModel.php
│   ├── TaskModel.php
│   └── ReviewModel.php
└── views/
    ├── home/
    │   └── index.php
    ├── customers/
    │   └── index.php
    └── booking/
        └── room-panel.php
```

## Cách Hoạt Động

### 1. Router (`admin/core/Router.php`)
- Router nhận request từ `index.php`
- Kiểm tra route trong `$routes` array
- Load controller tương ứng và gọi method
- Nếu controller không tồn tại, fallback về pages cũ

### 2. Controller
- Kế thừa từ `BaseController`
- Xử lý logic, gọi Model để lấy dữ liệu
- Render View hoặc redirect

### 3. Model
- Kế thừa từ `BaseModel`
- Xử lý database operations (CRUD)
- Có thể có các method custom cho business logic

### 4. View
- Chứa HTML/PHP để hiển thị
- Nhận data từ Controller qua `extract()`
- Nằm trong thư mục `views/`

## Cách Sử Dụng

### Bật/Tắt MVC Mode

Trong `admin/index.php`, có biến `$useMVC`:

```php
$useMVC = true; // Bật MVC
$useMVC = false; // Tắt MVC, dùng pages cũ
```

### Tạo Controller Mới

1. Tạo file trong `admin/controllers/YourController.php`:

```php
<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/YourModel.php';

class YourController extends BaseController {
    
    public function index() {
        // Check permission
        if (!$this->checkPermission('your.permission')) {
            $this->redirect('index.php?page=403');
            return;
        }
        
        $model = new YourModel($this->mysqli);
        
        // Handle POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle form submission
        }
        
        // Get data
        $data = [
            'items' => $model->getAll(),
            'canCreate' => $this->checkPermission('your.create')
        ];
        
        $this->render('your/index', $data);
    }
}
```

2. Thêm route vào `Router.php`:

```php
$this->routes = [
    // ...
    'your-page' => 'YourController@index',
];
```

### Tạo Model Mới

1. Tạo file trong `admin/models/YourModel.php`:

```php
<?php
require_once __DIR__ . '/../core/BaseModel.php';

class YourModel extends BaseModel {
    protected $table = 'your_table';
    protected $primaryKey = 'id';
    
    // Custom methods
    public function getCustomData() {
        // Your custom query
    }
}
```

### Tạo View Mới

1. Tạo file trong `admin/views/your/index.php`:

```php
<?php
// Access data from controller via extract()
// $items, $canCreate, etc.
?>

<div class="main-content">
    <h1>Your Page</h1>
    <!-- Your HTML here -->
</div>
```

## Migration Strategy

Hiện tại hệ thống hỗ trợ cả 2 cách:

1. **MVC Mode**: Sử dụng Controllers, Models, Views
2. **Legacy Mode**: Sử dụng pages cũ trong `admin/pages/`

Có thể migrate từng phần:
- Giữ pages cũ cho các module chưa migrate
- Tạo Controller/Model/View mới cho module mới
- Router sẽ tự động fallback nếu controller không tồn tại

## Lợi Ích MVC

1. **Separation of Concerns**: Logic, Data, Presentation tách biệt
2. **Reusability**: Model có thể dùng lại ở nhiều Controller
3. **Maintainability**: Dễ bảo trì và mở rộng
4. **Testability**: Dễ test từng phần riêng biệt
5. **Code Organization**: Code được tổ chức rõ ràng hơn

## Notes

- Views có thể truy cập các helper functions từ `connect.php` (formatCurrency, formatDate, etc.)
- Controllers có thể sử dụng `$this->mysqli` để truy cập database
- Permission checking được tích hợp sẵn trong BaseController









