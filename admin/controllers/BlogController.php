<?php
require_once __DIR__ . '/../core/BaseController.php';

/**
 * Blog Controller
 */
class BlogController extends BaseController {
    
    public function index() {
        // Check permission using canAccessSection (which uses permission_map)
        if (!$this->checkAccessSection('blogs-manager')) {
            $this->redirect('index.php?page=403');
            return;
        }
        
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        
        // Use old blogs-manager.php which handles panel routing internally
        include __DIR__ . '/../pages/blogs-manager.php';
    }
}

