<?php

require_once __DIR__ . '/Controller.php';

class HomeController extends Controller {
    public function index() {
        // Redirect logged in users straight to their dashboard
        if (isset($_SESSION['user_id'])) {
            $role = $_SESSION['user_role'];
            if ($role === 'patient') {
                header('Location: /diabetrack/public/patient/dashboard');
            } elseif ($role === 'caregiver') {
                header('Location: /diabetrack/public/caregiver/dashboard');
            } elseif ($role === 'admin') {
                header('Location: /diabetrack/public/admin/dashboard');
            }
            exit;
        }
        $this->view('home/home_view');
    }
}