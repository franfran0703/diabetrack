<?php

require_once __DIR__ . '/Controller.php';

class OnboardingController extends Controller {

    private $db;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /diabetrack/public/auth/login');
            exit;
        }
        require_once __DIR__ . '/../../config/Database.php';
        $this->db = (new Database())->connect();
    }

    public function index() {
        // If already onboarded, kick to dashboard
        $stmt = $this->db->prepare("SELECT onboarding_complete, role FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user['onboarding_complete']) {
            $this->redirectToDashboard($user['role']);
        }

        $role = $_SESSION['user_role'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $step = (int)($_POST['step'] ?? 1);

            if ($role === 'patient') {
                $this->savePatientStep($step);
            } else {
                $this->saveCaregiverStep($step);
            }
            return;
        }

        if ($role === 'patient') {
            $this->view('onboarding/patient_onboarding_view', [
                'name' => $_SESSION['user_name'],
            ]);
        } else {
            $this->view('onboarding/caregiver_onboarding_view', [
                'name' => $_SESSION['user_name'],
            ]);
        }
    }

    private function savePatientStep($step) {
        $uid = $_SESSION['user_id'];

        if ($step === 1) {
            // Step 1: health details
            $stmt = $this->db->prepare("
                UPDATE patient_profiles SET
                    date_of_birth            = :dob,
                    diabetes_type            = :dtype,
                    emergency_contact_name   = :ecname,
                    emergency_contact_number = :ecnum
                WHERE user_id = :uid
            ");
            $stmt->execute([
                'dob'    => $_POST['date_of_birth']            ?: null,
                'dtype'  => $_POST['diabetes_type']            ?: null,
                'ecname' => $_POST['emergency_contact_name']   ?: null,
                'ecnum'  => $_POST['emergency_contact_number'] ?: null,
                'uid'    => $uid,
            ]);
            // Respond with next step indicator
            echo json_encode(['next' => 2]);
            exit;
        }

        if ($step === 2) {
            // Step 2: done — mark onboarding complete
            $this->db->prepare("UPDATE users SET onboarding_complete = 1 WHERE id = :id")
                     ->execute(['id' => $uid]);
            echo json_encode(['redirect' => '/diabetrack/public/patient/dashboard']);
            exit;
        }
    }

    private function saveCaregiverStep($step) {
        $uid = $_SESSION['user_id'];

        if ($step === 1) {
            $stmt = $this->db->prepare("
                UPDATE caregiver_profiles SET
                    contact_number = :phone,
                    address        = :addr
                WHERE user_id = :uid
            ");
            $stmt->execute([
                'phone' => $_POST['contact_number'] ?: null,
                'addr'  => $_POST['address']        ?: null,
                'uid'   => $uid,
            ]);
            echo json_encode(['next' => 2]);
            exit;
        }

        if ($step === 2) {
            $this->db->prepare("UPDATE users SET onboarding_complete = 1 WHERE id = :id")
                     ->execute(['id' => $uid]);
            echo json_encode(['redirect' => '/diabetrack/public/caregiver/dashboard']);
            exit;
        }
    }

    public function skip() {
        // Allow skipping optional fields but still mark complete
        $this->db->prepare("UPDATE users SET onboarding_complete = 1 WHERE id = :id")
                 ->execute(['id' => $_SESSION['user_id']]);
        $this->redirectToDashboard($_SESSION['user_role']);
    }

    private function redirectToDashboard($role) {
        if ($role === 'patient') {
            header('Location: /diabetrack/public/patient/dashboard');
        } elseif ($role === 'caregiver') {
            header('Location: /diabetrack/public/caregiver/dashboard');
        }
        exit;
    }
}