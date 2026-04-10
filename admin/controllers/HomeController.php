<?php
require_once __DIR__ . '/../core/BaseController.php';

/**
 * Home Controller
 * Xử lý trang dashboard/home
 */
class HomeController extends BaseController {
    
    public function index() {
        // Redirect staff to my-tasks, managers to dashboard
        $role = $this->getCurrentStaffRole();
        if ($role !== 'Quản lý') {
            $this->redirect('index.php?page=my-tasks');
            return;
        }
        
        // Check permission for managers only
        if (!$this->checkAccessSection('home')) {
            $this->redirect('index.php?page=403');
            return;
        }
        
        // For now, use old page structure until views are fully migrated
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        include __DIR__ . '/../pages/home.php';
    }
}

