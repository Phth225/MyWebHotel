<?php
/**
 * Router Class
 * Xử lý routing cho admin panel
 */
class Router {
    private $routes = [];
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->loadRoutes();
    }
    
    /**
     * Load routes từ config
     */
    private function loadRoutes() {
        $this->routes = [
            'home' => 'HomeController@index',
            'room-manager' => 'RoomController@index',
            'services-manager' => 'ServiceController@index',
            'invoices-manager' => 'InvoiceController@index',
            'booking-manager' => 'BookingController@index',
            'calendar-booking' => 'CalendarController@index',
            'customers-manager' => 'CustomerController@index',
            'staff-manager' => 'StaffController@index',
            'task-manager' => 'TaskController@index',
            'permission-manager' => 'PermissionController@index',
            'reports-manager' => 'ReportController@index',
            'blogs-manager' => 'BlogController@index',
            'voucher-manager' => 'VoucherController@index',
            'profile' => 'ProfileController@index',
            'logout' => 'AuthController@logout',
            'my-tasks' => 'TaskController@myTasks',
        ];
    }
    
    /**
     * Dispatch request
     */
    public function dispatch() {
        $page = isset($_GET['page']) ? trim($_GET['page']) : 'home';

        // TẠM THỜI: với trang room-manager dùng lại cấu trúc page cũ
        // để các xử lý upload/phức tạp trong pages/room-manager.php hoạt động đúng
        if ($page === 'room-manager') {
            $this->fallbackToOldPages($page);
            return;
        }
        
        if (!isset($this->routes[$page])) {
            $this->handle404();
            return;
        }
        
        // Check permission (skip for 'home' as HomeController handles its own logic)
        if ($page !== 'home' && function_exists('canAccessSection') && !canAccessSection($page)) {
            $this->handle403();
            return;
        }
        
        // Parse controller@method
        $route = $this->routes[$page];
        list($controllerName, $method) = explode('@', $route);
        
        // Load controller
        $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';
        if (!file_exists($controllerFile)) {
            // Fallback to old pages if controller doesn't exist
            $this->fallbackToOldPages($page);
            return;
        }
        
        require_once $controllerFile;
        
        // Check if class exists
        if (!class_exists($controllerName)) {
            $this->fallbackToOldPages($page);
            return;
        }
        
        $controller = new $controllerName($this->mysqli);
        
        // Check if method exists
        if (!method_exists($controller, $method)) {
            $this->fallbackToOldPages($page);
            return;
        }
        
        // Call controller method
        try {
            $controller->$method();
        } catch (Exception $e) {
            error_log("Controller Error: " . $e->getMessage());
            $this->fallbackToOldPages($page);
        }
    }
    
    /**
     * Fallback to old pages structure
     */
    private function fallbackToOldPages($page) {
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        
        $allowed = [
            'home' => 'pages/home.php',
            'room-manager' => 'pages/room-manager.php',
            'services-manager' => 'pages/services-manager.php',
            'invoices-manager' => 'pages/invoices-manager.php',
            'booking-manager' => 'pages/booking-manager.php',
            'calendar-booking' => 'pages/calendar-booking.php',
            'customers-manager' => 'pages/customers-manager.php',
            'staff-manager' => 'pages/staff-manager.php',
            'task-manager' => 'pages/task-manager.php',
            'permission-manager' => 'pages/permission-manager.php',
            'reports-manager' => 'pages/reports-manager.php',
            'blogs-manager' => 'pages/blogs-manager.php',
            'voucher-manager' => 'pages/voucher-manager.php',
            'profile' => 'pages/profile.php',
            'logout' => 'pages/logout.php',
            'my-tasks' => 'pages/my-tasks.php',
        ];
        
        if (isset($allowed[$page])) {
            include __DIR__ . '/../' . $allowed[$page];
        } else {
            $this->handle404();
        }
    }
    
    /**
     * Handle 404
     */
    private function handle404() {
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        http_response_code(404);
        $errorFile = __DIR__ . '/../pages/404.php';
        if (file_exists($errorFile)) {
            include $errorFile;
        } else {
            echo '<div class="main-content"><div class="container-fluid"><div class="text-center py-5"><h1 class="display-1">404</h1><h2 class="mb-4">Trang không tìm thấy</h2><p class="text-muted mb-4">Trang bạn đang tìm kiếm không tồn tại hoặc đã bị xóa.</p><a href="index.php?page=home" class="btn btn-primary">Về Trang Chủ</a></div></div></div>';
        }
    }
    
    /**
     * Handle 403
     */
    private function handle403() {
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        http_response_code(403);
        $errorFile = __DIR__ . '/../pages/403.php';
        if (file_exists($errorFile)) {
            include $errorFile;
        } else {
            echo '<div class="main-content"><div class="container-fluid"><div class="alert alert-danger m-4">Bạn không có quyền truy cập trang này.</div><a href="index.php?page=home" class="btn btn-primary">Về Trang Chủ</a></div></div>';
        }
    }
}

