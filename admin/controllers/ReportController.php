<?php
require_once __DIR__ . '/../core/BaseController.php';

/**
 * Report Controller
 */
class ReportController extends BaseController {
    
    public function index() {
        if (!$this->checkPermission('report.view')) {
            $this->redirect('index.php?page=403');
            return;
        }
        
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        
        // Use old page for now (reports-manager.php)
        include __DIR__ . '/../pages/reports-manager.php';
    }
}

