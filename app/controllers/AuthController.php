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

                // --- 2FA check ---
                if (!empty($user['two_fa_enabled']) && !empty($user['two_fa_secret'])) {
                    // Store minimal pending data — NOT a full login session yet
                    $_SESSION['2fa_pending_id']   = $user['id'];
                    $_SESSION['2fa_pending_name'] = $user['name'];
                    $_SESSION['2fa_pending_role'] = $user['role'];
                    header('Location: /diabetrack/public/auth/verify2fa');
                    exit;
                }

                // No 2FA — log straight in
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                $this->redirectToDashboard($user['role']);

            } else {
                $error = 'Invalid email or password.';
                $this->view('auth/login_view', ['error' => $error]);
            }
        } else {
            $this->view('auth/login_view');
        }
    }

    // Show & handle the 2FA verification page
    public function verify2fa() {
        // Must have a pending 2FA session
        if (empty($_SESSION['2fa_pending_id'])) {
            header('Location: /diabetrack/public/auth/login');
            exit;
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['code'] ?? '');

            require_once __DIR__ . '/../../vendor/autoload.php';
            require_once __DIR__ . '/../../config/database.php';

            $db   = (new Database())->connect();
            $stmt = $db->prepare("SELECT two_fa_secret FROM users WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['2fa_pending_id']]);
            $secret = $stmt->fetchColumn();

            $google2fa = new \PragmaRX\Google2FA\Google2FA();

            if ($secret && $google2fa->verifyKey($secret, $code)) {
                // Code is valid — promote to full session
                $_SESSION['user_id']   = $_SESSION['2fa_pending_id'];
                $_SESSION['user_name'] = $_SESSION['2fa_pending_name'];
                $_SESSION['user_role'] = $_SESSION['2fa_pending_role'];

                // Clean up pending keys
                unset($_SESSION['2fa_pending_id'], $_SESSION['2fa_pending_name'], $_SESSION['2fa_pending_role']);

                $this->redirectToDashboard($_SESSION['user_role']);
            } else {
                $error = 'Invalid code. Please try again.';
            }
        }

        $this->view('auth/verify2fa_view', ['error' => $error]);
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

    // Helper
    private function redirectToDashboard($role) {
        // Check if user has completed onboarding
        require_once __DIR__ . '/../../config/Database.php';
        $db   = (new Database())->connect();
        $stmt = $db->prepare("SELECT onboarding_complete FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $done = (bool) $stmt->fetchColumn();

        if (!$done && $role !== 'admin') {
            header('Location: /diabetrack/public/onboarding/index');
            exit;
        }

        if ($role === 'patient') {
            header('Location: /diabetrack/public/patient/dashboard');
        } elseif ($role === 'caregiver') {
            header('Location: /diabetrack/public/caregiver/dashboard');
        } elseif ($role === 'admin') {
            header('Location: /diabetrack/public/admin/dashboard');
        }
        exit;
    }
}