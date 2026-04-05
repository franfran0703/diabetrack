<?php

require_once __DIR__ . '/Controller.php';

class AuthController extends Controller {

    private $userModel;

    public function __construct() {
        $this->userModel = $this->model('UserModel');
    }

    // Show login page
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim($_POST['email']);
            $password = trim($_POST['password']);

            $user = $this->userModel->findByEmail($email);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'patient') {
                    header('Location: /diabetrack/public/patient/dashboard');
                } elseif ($user['role'] === 'caregiver') {
                    header('Location: /diabetrack/public/caregiver/dashboard');
                } elseif ($user['role'] === 'admin') {
                    header('Location: /diabetrack/public/admin/dashboard');
                }
                exit;
            } else {
                $error = 'Invalid email or password.';
                $this->view('auth/login_view', ['error' => $error]);
            }
        } else {
            $this->view('auth/login_view');
        }
    }

    // Show register page
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name     = trim($_POST['name']);
            $email    = trim($_POST['email']);
            $password = trim($_POST['password']);
            $role     = trim($_POST['role']);

            // Check if email already exists
            $existing = $this->userModel->findByEmail($email);

            if ($existing) {
                $error = 'Email already registered.';
                $this->view('auth/register_view', ['error' => $error]);
            } else {
                $this->userModel->register($name, $email, $password, $role);
                header('Location: /diabetrack/public/auth/login');
                exit;
            }
        } else {
            $this->view('auth/register_view');
        }
    }

    // Logout
    public function logout() {
        session_destroy();
        header('Location: /diabetrack/public/auth/login');
        exit;
    }
}