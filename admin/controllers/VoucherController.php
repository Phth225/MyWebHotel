<?php
require_once __DIR__ . '/../core/BaseController.php';

class VoucherController extends BaseController {
    
    public function index() {
        // Check permission
        if (!$this->checkAccessSection('voucher-manager')) {
            $this->redirect('index.php?page=403');
            return;
        }
        
        // Make $mysqli available for included file
        $mysqli = $this->mysqli;
        
        // Include the voucher manager page
        require_once __DIR__ . '/../pages/voucher-manager.php';
    }
}









