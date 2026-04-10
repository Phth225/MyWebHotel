<?php
/**
 * Base Controller
 * Tất cả controllers sẽ kế thừa từ class này
 */
class BaseController {
    protected $mysqli;
    protected $viewPath;
    protected $data = [];
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->viewPath = __DIR__ . '/../views/';
    }
    
    /**
     * Render view
     */
    protected function render($view, $data = []) {
        $this->data = array_merge($this->data, $data);
        extract($this->data);
        
        $viewFile = $this->viewPath . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("View not found: {$view}");
        }
    }
    
    /**
     * Render JSON response
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Redirect
     */
    protected function redirect($url) {
        // Check if headers have been sent
        if (headers_sent()) {
            // Use JavaScript redirect if headers already sent
            echo "<script>window.location.href = '" . htmlspecialchars($url, ENT_QUOTES) . "';</script>";
            exit;
        } else {
            // Use header redirect if headers not sent yet
            header("Location: {$url}");
            exit;
        }
    }
    
    /**
     * Check permission
     */
    protected function checkPermission($permission) {
        if (!function_exists('checkPermission')) {
            require_once __DIR__ . '/../includes/auth.php';
        }
        return checkPermission($permission);
    }
    
    /**
     * Check access section
     */
    protected function checkAccessSection($sectionKey) {
        if (!function_exists('canAccessSection')) {
            require_once __DIR__ . '/../includes/auth.php';
        }
        return canAccessSection($sectionKey);
    }
    
    /**
     * Get current staff ID
     */
    protected function getCurrentStaffId() {
        return $_SESSION['id_nhan_vien'] ?? null;
    }
    
    /**
     * Get current staff role
     */
    protected function getCurrentStaffRole() {
        return $_SESSION['chuc_vu'] ?? null;
    }
}

