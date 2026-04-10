<?php
require_once __DIR__ . '/../core/BaseController.php';

/**
 * Auth Controller
 */
class AuthController extends BaseController {
    
    public function logout() {
        session_start();
        session_destroy();
        header("Location: pages/logIn.php");
        exit;
    }
}









