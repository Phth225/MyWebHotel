<?php
require_once __DIR__ . '/../core/BaseController.php';

/**
 * Permission Controller
 */
class PermissionController extends BaseController {
    
    public function index() {
        if (!$this->checkPermission('employee.set_permission')) {
            $this->redirect('index.php?page=403');
            return;
        }
        
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        
        // Use old permission-manager.php for now
        include __DIR__ . '/../pages/permission-manager.php';
    }
}

