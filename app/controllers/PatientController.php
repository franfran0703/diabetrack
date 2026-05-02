<?php

require_once __DIR__ . '/Controller.php';

class PatientController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
            header('Location: /diabetrack/public/auth/login');
            exit;
        }
    }

      public function dashboard() {
    $bloodSugarModel = $this->model('BloodSugarModel');
    $medModel        = $this->model('MedicationModel');
    $mealModel       = $this->model('MealModel');
    $activityModel   = $this->model('ActivityModel');

    $pid    = $_SESSION['user_id'];
    $latest = $bloodSugarModel->getLatest($pid);

    // Blood sugar
    $latestBloodSugar       = $latest['reading']  ?? ($latest['blood_sugar_level'] ?? null);
    $latestBloodSugarStatus = $latest['status']   ?? null;
    $last7                  = $bloodSugarModel->getLast7($pid);

    // Medications
    $medications = $medModel->getMedications($pid);
    $todayLogs   = $medModel->getTodayLogs($pid);
    $todayStats  = $medModel->getTodayStats($pid);
    $loggedToday = [];
    foreach ($medications as $med) {
        $loggedToday[$med['id']] = $medModel->alreadyLoggedToday($med['id'], $pid);
    }

    // Meals
    $todayTotals = $mealModel->getTodayTotals($pid);

    // Activity
    $activityTotals = $activityModel->getTodayTotals($pid);
    $last7Days      = $activityModel->getLast7Days($pid);
    $activityToday  = ($activityTotals['total_activities'] ?? 0) > 0
                        ? $activityTotals['total_minutes']
                        : null;
    // Pending caregiver requests count
    require_once __DIR__ . '/../../config/Database.php';
    $__db = (new Database())->connect();
    $__stmt = $__db->prepare("
        SELECT COUNT(*) FROM caregiver_links
        WHERE patient_id = :pid AND status = 'pending'
    ");
    $__stmt->execute(['pid' => $pid]);
    $pendingCaregiverRequests = $__stmt->fetchColumn();

    $this->view('patient/dashboard_view', [
        'name'                   => $_SESSION['user_name'],
        'latest'                 => $latest,
        'latestBloodSugar'       => $latestBloodSugar,
        'latestBloodSugarStatus' => $latestBloodSugarStatus,
        'last7'                  => $last7,
        'medications'            => $medications,
        'todayLogs'              => $todayLogs,
        'todayStats'             => $todayStats,
        'loggedToday'            => $loggedToday,
        'todayTotals'            => $todayTotals,
        'activityToday'          => $activityToday,
        'last7Days'              => $last7Days,
        'pendingCaregiverRequests' => $pendingCaregiverRequests,
    ]);
}

    public function bloodsugar() {
        $bloodSugarModel = $this->model('BloodSugarModel');

        // Handle form submit
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reading      = trim($_POST['reading']);
            $reading_type = trim($_POST['reading_type']);
            $notes        = trim($_POST['notes'] ?? '');

            $bloodSugarModel->addReading(
                $_SESSION['user_id'],
                $reading,
                $reading_type,
                $notes
            );

            header('Location: /diabetrack/public/patient/bloodsugar');
            exit;
        }

        // Handle delete
        if (isset($_GET['delete'])) {
            $bloodSugarModel->deleteLog($_GET['delete'], $_SESSION['user_id']);
            header('Location: /diabetrack/public/patient/bloodsugar');
            exit;
        }

        $logs   = $bloodSugarModel->getLogs($_SESSION['user_id']);
        $last7  = $bloodSugarModel->getLast7($_SESSION['user_id']);
        $latest = $bloodSugarModel->getLatest($_SESSION['user_id']);

    $this->view('patient/bloodsugar_view', [
        'name'   => $_SESSION['user_name'],
        'logs'   => $logs,
        'last7'  => $last7,
        'latest' => $latest
    ]);
}

public function medication() {
    $medModel = $this->model('MedicationModel');
    $pid      = $_SESSION['user_id'];
    $error    = null;
    $success  = null;

    // ── ADD medication
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

        if ($_POST['action'] === 'add') {
            $medModel->addMedication(
                $pid,
                trim($_POST['name']),
                trim($_POST['dosage']),
                trim($_POST['schedule_time']),
                trim($_POST['frequency'])
            );
            $success = 'Medication added successfully!';

        } elseif ($_POST['action'] === 'edit') {
            $medModel->updateMedication(
                $_POST['med_id'], $pid,
                trim($_POST['name']),
                trim($_POST['dosage']),
                trim($_POST['schedule_time']),
                trim($_POST['frequency'])
            );
            $success = 'Medication updated!';

        } elseif ($_POST['action'] === 'log') {
            $mid = $_POST['med_id'];
            if ($medModel->alreadyLoggedToday($mid, $pid)) {
                $error = 'You already logged this medication today.';
            } else {
                $medModel->logDose($mid, $pid, $_POST['status']);
                $success = 'Dose logged as ' . $_POST['status'] . '!';
            }
        }
    }

    // ── DELETE medication
    if (isset($_GET['delete'])) {
        $medModel->deleteMedication($_GET['delete'], $pid);
        header('Location: /diabetrack/public/patient/medication');
        exit;
    }

    $medications = $medModel->getMedications($pid);
    $todayLogs   = $medModel->getTodayLogs($pid);
    $allLogs     = $medModel->getAllLogs($pid);
    $todayStats  = $medModel->getTodayStats($pid);

    // Mark which meds are already logged today
    $loggedToday = [];
    foreach ($medications as $med) {
        $loggedToday[$med['id']] = $medModel->alreadyLoggedToday($med['id'], $pid);
    }

    $this->view('patient/medication_view', [
        'name'        => $_SESSION['user_name'],
        'medications' => $medications,
        'todayLogs'   => $todayLogs,
        'allLogs'     => $allLogs,
        'todayStats'  => $todayStats,
        'loggedToday' => $loggedToday,
        'error'       => $error,
        'success'     => $success,
    ]);
}

public function meals() {
    $mealModel = $this->model('MealModel');
    $pid       = $_SESSION['user_id'];
    $error     = null;
    $success   = null;

    // Handle add
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $mealModel->addMeal($pid, [
            'meal_name'      => trim($_POST['meal_name']),
            'meal_type'      => trim($_POST['meal_type']),
            'carbs'          => trim($_POST['carbs']),
            'calories'       => trim($_POST['calories'] ?? ''),
            'sugar'          => trim($_POST['sugar'] ?? ''),
            'fiber'          => trim($_POST['fiber'] ?? ''),
            'protein'        => trim($_POST['protein'] ?? ''),
            'fat'            => trim($_POST['fat'] ?? ''),
            'sodium'         => trim($_POST['sodium'] ?? ''),
            'glycemic_index' => trim($_POST['glycemic_index'] ?? ''),
            'notes'          => trim($_POST['notes'] ?? ''),
        ]);
        $success = 'Meal logged successfully!';
    }

    // Handle delete
    if (isset($_GET['delete'])) {
        $mealModel->deleteLog($_GET['delete'], $pid);
        header('Location: /diabetrack/public/patient/meals');
        exit;
    }

    $logs        = $mealModel->getLogs($pid);
    $todayLogs   = $mealModel->getTodayLogs($pid);
    $todayTotals = $mealModel->getTodayTotals($pid);

    $this->view('patient/meals_view', [
        'name'        => $_SESSION['user_name'],
        'logs'        => $logs,
        'todayLogs'   => $todayLogs,
        'todayTotals' => $todayTotals,
        'error'       => $error,
        'success'     => $success,
    ]);
}

public function appointments() {
    $apptModel = $this->model('AppointmentModel');
    $pid       = $_SESSION['user_id'];
    $error     = null;
    $success   = null;

    // Handle add
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $apptModel->addAppointment($pid, [
                'doctor_name'      => trim($_POST['doctor_name']),
                'appointment_date' => trim($_POST['appointment_date']),
                'notes'            => trim($_POST['notes'] ?? ''),
            ]);
            $success = 'Appointment added successfully!';

        } elseif ($_POST['action'] === 'status') {
            $apptModel->updateStatus($_POST['appt_id'], $pid, $_POST['status']);
            $success = 'Appointment status updated!';
        }
    }

    // Handle delete
    if (isset($_GET['delete'])) {
        $apptModel->delete($_GET['delete'], $pid);
        header('Location: /diabetrack/public/patient/appointments');
        exit;
    }

    $all      = $apptModel->getAll($pid);
    $upcoming = $apptModel->getUpcoming($pid);
    $next     = $apptModel->getNext($pid);
    $counts   = [
        'upcoming'  => $apptModel->countByStatus($pid, 'Upcoming'),
        'completed' => $apptModel->countByStatus($pid, 'Completed'),
        'cancelled' => $apptModel->countByStatus($pid, 'Cancelled'),
    ];

    $this->view('patient/appointments_view', [
        'name'     => $_SESSION['user_name'],
        'all'      => $all,
        'upcoming' => $upcoming,
        'next'     => $next,
        'counts'   => $counts,
        'error'    => $error,
        'success'  => $success,
    ]);
}

public function education() {
    $this->view('patient/education_view', [
        'name' => $_SESSION['user_name'],
    ]);
}

public function nearby() {
    $this->view('patient/nearby_view', [
        'name' => $_SESSION['user_name'],
    ]);
}

public function reports() {
    $bloodSugarModel = $this->model('BloodSugarModel');
    $medModel        = $this->model('MedicationModel');
    $mealModel       = $this->model('MealModel');
    $activityModel   = $this->model('ActivityModel');
    $apptModel       = $this->model('AppointmentModel');
    $pid             = $_SESSION['user_id'];

    $logs       = $bloodSugarModel->getLogs($pid);
    $latestBS   = $bloodSugarModel->getLatest($pid);

    // 7-day average
    $last7 = $bloodSugarModel->getLast7($pid);
    $bsAvg = null;
    if (!empty($last7)) {
        $bsAvg = round(array_sum(array_column($last7, 'reading')) / count($last7));
    }

    $medications = $medModel->getMedications($pid);
    $todayStats  = $medModel->getTodayStats($pid);
    $allLogs     = $medModel->getAllLogs($pid);

    $todayTotals    = $mealModel->getTodayTotals($pid);
    $actTodayTotals = $activityModel->getTodayTotals($pid);
    $weekTotals     = $activityModel->getWeekTotals($pid);

    $appts = $apptModel->getAll($pid);

    $this->view('patient/reports_view', [
        'name'          => $_SESSION['user_name'],
        'logs'          => $logs,
        'bsLogs'        => $logs,
        'latestBS'      => $latestBS,
        'bsAvg'         => $bsAvg,
        'bsCount'       => count($logs),
        'medications'   => $medications,
        'todayStats'    => $todayStats,
        'allLogs'       => $allLogs,
        'todayTotals'   => $todayTotals,
        'weekTotals'    => $weekTotals,
        'activityTodayMins' => $actTodayTotals['total_minutes'] ?? 0,
        'appts'         => $appts,
    ]);
}

public function activity() {
    $activityModel = $this->model('ActivityModel');
    $pid           = $_SESSION['user_id'];
    $error         = null;
    $success       = null;

    // Handle add
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $activityModel->addActivity($pid, [
            'activity_name'    => trim($_POST['activity_name']),
            'duration_minutes' => (int) $_POST['duration_minutes'],
            'intensity'        => trim($_POST['intensity']),
            'notes'            => trim($_POST['notes'] ?? ''),
        ]);
        $success = 'Activity logged successfully!';
    }

    // Handle delete
    if (isset($_GET['delete'])) {
        $activityModel->deleteLog($_GET['delete'], $pid);
        header('Location: /diabetrack/public/patient/activity');
        exit;
    }

    $logs        = $activityModel->getLogs($pid);
    $todayLogs   = $activityModel->getTodayLogs($pid);
    $todayTotals = $activityModel->getTodayTotals($pid);
    $weekTotals  = $activityModel->getWeekTotals($pid);
    $last7Days   = $activityModel->getLast7Days($pid);

    $this->view('patient/activity_view', [
        'name'        => $_SESSION['user_name'],
        'logs'        => $logs,
        'todayLogs'   => $todayLogs,
        'todayTotals' => $todayTotals,
        'weekTotals'  => $weekTotals,
        'last7Days'   => $last7Days,
        'error'       => $error,
        'success'     => $success,
    ]);
}
public function caregiverRequests() {
    require_once __DIR__ . '/../../config/Database.php';
    $db = (new Database())->connect();
    $pid = $_SESSION['user_id'];

    // Handle accept
    if (isset($_GET['accept'])) {
        $stmt = $db->prepare("
            UPDATE caregiver_links 
            SET status = 'accepted', linked_at = NOW()
            WHERE caregiver_id = :cid AND patient_id = :pid AND status = 'pending'
        ");
        $stmt->execute(['cid' => $_GET['accept'], 'pid' => $pid]);
        header('Location: /diabetrack/public/patient/caregiverRequests');
        exit;
    }

    // Handle decline
    if (isset($_GET['decline'])) {
        $stmt = $db->prepare("
            UPDATE caregiver_links 
            SET status = 'declined'
            WHERE caregiver_id = :cid AND patient_id = :pid AND status = 'pending'
        ");
        $stmt->execute(['cid' => $_GET['decline'], 'pid' => $pid]);
        header('Location: /diabetrack/public/patient/caregiverRequests');
        exit;
    }

    // Handle remove (patient removes an accepted caregiver)
    if (isset($_GET['remove'])) {
        $stmt = $db->prepare("
            DELETE FROM caregiver_links
            WHERE caregiver_id = :cid AND patient_id = :pid
        ");
        $stmt->execute(['cid' => $_GET['remove'], 'pid' => $pid]);
        header('Location: /diabetrack/public/patient/caregiverRequests');
        exit;
    }

    // Get pending requests
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.email, cl.requested_at FROM users u
        JOIN caregiver_links cl ON cl.caregiver_id = u.id
        WHERE cl.patient_id = :pid AND cl.status = 'pending'
        ORDER BY cl.requested_at DESC
    ");
    $stmt->execute(['pid' => $pid]);
    $pendingRequests = $stmt->fetchAll();

    // Get accepted caregivers
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.email, cl.linked_at FROM users u
        JOIN caregiver_links cl ON cl.caregiver_id = u.id
        WHERE cl.patient_id = :pid AND cl.status = 'accepted'
        ORDER BY cl.linked_at DESC
    ");
    $stmt->execute(['pid' => $pid]);
    $activeCaregivers = $stmt->fetchAll();

    $this->view('patient/caregiver_request_view', [
        'name'             => $_SESSION['user_name'],
        'pendingRequests'  => $pendingRequests,
        'activeCaregivers' => $activeCaregivers,
    ]);
}

    // ── Profile page ────────────────────────────────────────
    public function profile() {
        $userModel = $this->model('UserModel');
        $pid  = $_SESSION['user_id'];
        $user = $userModel->findById($pid);

        require_once __DIR__ . '/../../config/Database.php';
        $db = (new Database())->connect();

        // Stats
        $s = $db->prepare("SELECT COUNT(*) FROM blood_sugar_logs WHERE patient_id = :id");
        $s->execute(['id' => $pid]);
        $bsCount = (int)$s->fetchColumn();

        $s = $db->prepare("SELECT COUNT(*) FROM medications WHERE patient_id = :id");
        $s->execute(['id' => $pid]);
        $medCount = (int)$s->fetchColumn();

        $s = $db->prepare("SELECT COUNT(*) FROM meal_logs WHERE patient_id = :id");
        $s->execute(['id' => $pid]);
        $mealCount = (int)$s->fetchColumn();

        $s = $db->prepare("SELECT COUNT(*) FROM caregiver_links WHERE patient_id = :id AND status = 'accepted'");
        $s->execute(['id' => $pid]);
        $cgCount = (int)$s->fetchColumn();

        // Linked caregivers with status
        $s = $db->prepare("
            SELECT u.id, u.name, u.email, cl.linked_at, cl.status
            FROM users u
            JOIN caregiver_links cl ON cl.caregiver_id = u.id
            WHERE cl.patient_id = :id
            ORDER BY cl.linked_at DESC
        ");
        $s->execute(['id' => $pid]);
        $caregivers = $s->fetchAll();

        $this->view('patient/profile_view', [
            'user'       => $user,
            'stats'      => [
                'blood_sugar_logs' => $bsCount,
                'medications'      => $medCount,
                'meal_logs'        => $mealCount,
                'caregivers'       => $cgCount,
            ],
            'caregivers' => $caregivers,
        ]);
    }

    // ── Handle profile form POST ─────────────────────────────
    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /diabetrack/public/patient/profile');
            exit;
        }

        $userModel = $this->model('UserModel');
        $pid    = $_SESSION['user_id'];
        $action = $_POST['action'] ?? '';

        if ($action === 'info') {
            $name  = trim($_POST['name']  ?? '');
            $email = trim($_POST['email'] ?? '');

            if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->redirectProfile('patient', 'Invalid name or email.');
                return;
            }

            // Check email uniqueness (excluding self)
            $existing = $userModel->findByEmail($email);
            if ($existing && (int)$existing['id'] !== $pid) {
                $this->redirectProfile('patient', 'That email is already in use.');
                return;
            }

            $userModel->updateInfo($pid, $name, $email);
            $_SESSION['user_name'] = $name;
            $this->redirectProfile('patient', null, 'Profile updated successfully!');

        } elseif ($action === 'password') {
            $current = $_POST['current_password'] ?? '';
            $new     = $_POST['new_password']     ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            $user = $userModel->findById($pid);

            if (!password_verify($current, $user['password'])) {
                $this->redirectProfile('patient', 'Current password is incorrect.');
                return;
            }
            if (strlen($new) < 8) {
                $this->redirectProfile('patient', 'New password must be at least 8 characters.');
                return;
            }
            if ($new !== $confirm) {
                $this->redirectProfile('patient', 'New passwords do not match.');
                return;
            }

            $userModel->updatePassword($pid, $new);
            $this->redirectProfile('patient', null, 'Password changed successfully!');
        }
    }

    private function redirectProfile($role, $error = null, $success = null) {
        $param = $error
            ? '?error=' . urlencode($error)
            : '?success=' . urlencode($success);
        header("Location: /diabetrack/public/{$role}/profile{$param}");
        exit;
    }
}