<?php
require_once __DIR__ . '/../core/BaseController.php';

/**
 * Calendar Controller
 * Xử lý trang lịch đặt phòng
 */
class CalendarController extends BaseController {
    
    public function index() {
        // Check permission
        if (!$this->checkAccessSection('calendar-booking')) {
            $this->redirect('index.php?page=403');
            return;
        }
        
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        include __DIR__ . '/../pages/calendar-booking.php';
    }
}


