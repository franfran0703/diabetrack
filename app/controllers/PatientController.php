<?php

require_once __DIR__ . '/Controller.php';

class PatientController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
            header('Location: ' . BASE_URL . '/auth/login');
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

        $latestBloodSugar       = $latest['reading']  ?? ($latest['blood_sugar_level'] ?? null);
        $latestBloodSugarStatus = $latest['status']   ?? null;
        $last7                  = $bloodSugarModel->getLast7($pid);

        $medications = $medModel->getMedications($pid);
        $todayLogs   = $medModel->getTodayLogs($pid);
        $todayStats  = $medModel->getTodayStats($pid);
        $loggedToday = [];
        foreach ($medications as $med) {
            $loggedToday[$med['id']] = $medModel->alreadyLoggedToday($med['id'], $pid);
        }

        $todayTotals = $mealModel->getTodayTotals($pid);

        $activityTotals = $activityModel->getTodayTotals($pid);
        $last7Days      = $activityModel->getLast7Days($pid);
        $activityToday  = ($activityTotals['total_activities'] ?? 0) > 0
                            ? $activityTotals['total_minutes']
                            : null;

        require_once __DIR__ . '/../../config/database.php';
        $__db = (new Database())->connect();
        $__stmt = $__db->prepare("SELECT COUNT(*) FROM caregiver_links WHERE patient_id = :pid AND status = 'pending'");
        $__stmt->execute(['pid' => $pid]);
        $pendingCaregiverRequests = $__stmt->fetchColumn();

        $this->view('patient/dashboard_view', [
            'name'                     => $_SESSION['user_name'],
            'latest'                   => $latest,
            'latestBloodSugar'         => $latestBloodSugar,
            'latestBloodSugarStatus'   => $latestBloodSugarStatus,
            'last7'                    => $last7,
            'medications'              => $medications,
            'todayLogs'                => $todayLogs,
            'todayStats'               => $todayStats,
            'loggedToday'              => $loggedToday,
            'todayTotals'              => $todayTotals,
            'activityToday'            => $activityToday,
            'last7Days'                => $last7Days,
            'pendingCaregiverRequests' => $pendingCaregiverRequests,
        ]);
    }

    public function bloodsugar() {
        $bloodSugarModel = $this->model('BloodSugarModel');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bloodSugarModel->addReading(
                $_SESSION['user_id'],
                trim($_POST['reading']),
                trim($_POST['reading_type']),
                trim($_POST['notes'] ?? '')
            );
            header('Location: ' . BASE_URL . '/patient/bloodsugar');
            exit;
        }

        if (isset($_GET['delete'])) {
            $bloodSugarModel->deleteLog($_GET['delete'], $_SESSION['user_id']);
            header('Location: ' . BASE_URL . '/patient/bloodsugar');
            exit;
        }

        $logs   = $bloodSugarModel->getLogs($_SESSION['user_id']);
        $last7  = $bloodSugarModel->getLast7($_SESSION['user_id']);
        $latest = $bloodSugarModel->getLatest($_SESSION['user_id']);

        $this->view('patient/bloodsugar_view', [
            'name'   => $_SESSION['user_name'],
            'logs'   => $logs,
            'last7'  => $last7,
            'latest' => $latest,
        ]);
    }

    public function medication() {
        $medModel = $this->model('MedicationModel');
        $pid      = $_SESSION['user_id'];
        $error    = null;
        $success  = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] === 'add') {
                $medModel->addMedication($pid, trim($_POST['name']), trim($_POST['dosage']), trim($_POST['schedule_time']), trim($_POST['frequency']));
                $success = 'Medication added successfully!';
            } elseif ($_POST['action'] === 'edit') {
                $medModel->updateMedication($_POST['med_id'], $pid, trim($_POST['name']), trim($_POST['dosage']), trim($_POST['schedule_time']), trim($_POST['frequency']));
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

        if (isset($_GET['delete'])) {
            $medModel->deleteMedication($_GET['delete'], $pid);
            header('Location: ' . BASE_URL . '/patient/medication');
            exit;
        }

        $medications = $medModel->getMedications($pid);
        $todayLogs   = $medModel->getTodayLogs($pid);
        $allLogs     = $medModel->getAllLogs($pid);
        $todayStats  = $medModel->getTodayStats($pid);
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

        require_once __DIR__ . '/../../config/database.php';
        $db = (new Database())->connect();

        // Handle add meal
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'save_preset') {
            $mealModel->addMeal($pid, [
    'meal_name'      => trim($_POST['meal_name']),
    'meal_type'      => trim($_POST['meal_type']),
    'carbs'          => trim($_POST['carbs']),
    'calories'       => trim($_POST['calories']       ?? ''),
    'sugar'          => trim($_POST['sugar']          ?? ''),
    'fiber'          => isset($_POST['fiber'])          ? trim($_POST['fiber'])          : '',
    'protein'        => trim($_POST['protein']        ?? ''),
    'fat'            => trim($_POST['fat']            ?? ''),
    'sodium'         => isset($_POST['sodium'])         ? trim($_POST['sodium'])         : '',
    'glycemic_index' => trim($_POST['glycemic_index'] ?? ''),
    'notes'          => trim($_POST['notes']          ?? ''),
]);
            $success = 'Meal logged successfully!';
        }

        // Handle save preset
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_preset') {
            $stmt = $db->prepare("
                INSERT INTO meal_presets (patient_id, meal_name, meal_type, carbs, calories, sugar, protein, fat, fiber, sodium)
                VALUES (:pid, :name, :type, :carbs, :cal, :sugar, :protein, :fat, :fiber, :sodium)
            ");
            $stmt->execute([
    'pid'     => $pid,
    'name'    => trim($_POST['meal_name']),
    'type'    => trim($_POST['meal_type']),
    'carbs'   => trim($_POST['carbs'] ?: 0),
    'cal'     => trim($_POST['calories'] ?? '') ?: null,
    'sugar'   => trim($_POST['sugar']    ?? '') ?: null,
    'protein' => trim($_POST['protein']  ?? '') ?: null,
    'fat'     => trim($_POST['fat']      ?? '') ?: null,
    'fiber'   => trim($_POST['fiber']    ?? '') ?: null,
    'sodium'  => trim($_POST['sodium']   ?? '') ?: null,
]);
            $success = 'Meal saved to quick-add!';
        }

        // Handle delete preset
        if (isset($_GET['delete_preset'])) {
            $db->prepare("DELETE FROM meal_presets WHERE id = :id AND patient_id = :pid")
               ->execute(['id' => $_GET['delete_preset'], 'pid' => $pid]);
            header('Location: ' . BASE_URL . '/patient/meals');
            exit;
        }

        // Handle delete log
        if (isset($_GET['delete'])) {
            $mealModel->deleteLog($_GET['delete'], $pid);
            header('Location: ' . BASE_URL . '/patient/meals');
            exit;
        }

        // Get user presets
        $presetStmt = $db->prepare("SELECT * FROM meal_presets WHERE patient_id = :pid ORDER BY created_at DESC");
        $presetStmt->execute(['pid' => $pid]);
        $userPresets = $presetStmt->fetchAll();

        // Built-in Filipino meal suggestions
        $defaultPresets = [
            ['meal_name'=>'Sinangag (Fried Rice)',  'meal_type'=>'Breakfast', 'carbs'=>45, 'calories'=>206, 'sugar'=>0,  'protein'=>4,  'fat'=>7,  'emoji'=>'🍚'],
            ['meal_name'=>'Pandesal',               'meal_type'=>'Breakfast', 'carbs'=>23, 'calories'=>120, 'sugar'=>3,  'protein'=>4,  'fat'=>2,  'emoji'=>'🍞'],
            ['meal_name'=>'Adobo (Chicken)',        'meal_type'=>'Lunch',     'carbs'=>5,  'calories'=>285, 'sugar'=>1,  'protein'=>27, 'fat'=>17, 'emoji'=>'🍗'],
            ['meal_name'=>'Sinigang na Baboy',      'meal_type'=>'Lunch',     'carbs'=>8,  'calories'=>195, 'sugar'=>3,  'protein'=>20, 'fat'=>10, 'emoji'=>'🍲'],
            ['meal_name'=>'Steamed Rice (1 cup)',   'meal_type'=>'Lunch',     'carbs'=>45, 'calories'=>206, 'sugar'=>0,  'protein'=>4,  'fat'=>0,  'emoji'=>'🍚'],
            ['meal_name'=>'Tinola (Chicken Soup)',  'meal_type'=>'Lunch',     'carbs'=>6,  'calories'=>160, 'sugar'=>2,  'protein'=>22, 'fat'=>6,  'emoji'=>'🍜'],
            ['meal_name'=>'Bangus Grilled',         'meal_type'=>'Dinner',    'carbs'=>0,  'calories'=>175, 'sugar'=>0,  'protein'=>26, 'fat'=>7,  'emoji'=>'🐟'],
            ['meal_name'=>'Pinakbet',               'meal_type'=>'Dinner',    'carbs'=>12, 'calories'=>130, 'sugar'=>5,  'protein'=>6,  'fat'=>7,  'emoji'=>'🥬'],
            ['meal_name'=>'Kamote (Sweet Potato)',  'meal_type'=>'Snack',     'carbs'=>27, 'calories'=>112, 'sugar'=>6,  'protein'=>2,  'fat'=>0,  'emoji'=>'🍠'],
            ['meal_name'=>'Banana (Lakatan)',       'meal_type'=>'Snack',     'carbs'=>23, 'calories'=>89,  'sugar'=>12, 'protein'=>1,  'fat'=>0,  'emoji'=>'🍌'],
            ['meal_name'=>'Hard Boiled Egg',        'meal_type'=>'Snack',     'carbs'=>1,  'calories'=>78,  'sugar'=>1,  'protein'=>6,  'fat'=>5,  'emoji'=>'🥚'],
            ['meal_name'=>'Lugaw (Rice Porridge)',  'meal_type'=>'Breakfast', 'carbs'=>28, 'calories'=>130, 'sugar'=>0,  'protein'=>3,  'fat'=>1,  'emoji'=>'🥣'],
        ];

        $logs        = $mealModel->getLogs($pid);
        $todayLogs   = $mealModel->getTodayLogs($pid);
        $todayTotals = $mealModel->getTodayTotals($pid);

        // Fetch nutrition limits set by this patient's caregiver (if any)
        $defaultLimits = [
            'carbs' => 130, 'calories' => 1800, 'sugar' => 25,
            'protein' => 50, 'fat' => 65, 'fiber' => 25, 'sodium' => 2300,
        ];
        $nutritionLimits = $defaultLimits;
        try {
            // Check if table exists first
            $tableCheck = $db->query("SHOW TABLES LIKE 'nutrition_limits'")->fetch();
            if ($tableCheck) {
                $limStmt = $db->prepare("
                    SELECT nl.* FROM nutrition_limits nl
                    JOIN caregiver_links cl ON cl.caregiver_id = nl.caregiver_id
                    WHERE cl.patient_id = :pid AND cl.status = 'accepted'
                    ORDER BY nl.updated_at DESC LIMIT 1
                ");
                $limStmt->execute(['pid' => $pid]);
                $saved = $limStmt->fetch();
                if ($saved) {
                    $nutritionLimits = array_merge(
                        $defaultLimits,
                        array_intersect_key($saved, $defaultLimits)
                    );
                }
            }
        } catch (Exception $e) {
            // Table doesn't exist yet — use defaults silently
        }

        $this->view('patient/meals_view', [
            'name'            => $_SESSION['user_name'],
            'logs'            => $logs,
            'todayLogs'       => $todayLogs,
            'todayTotals'     => $todayTotals,
            'userPresets'     => $userPresets,
            'defaultPresets'  => $defaultPresets,
            'nutritionLimits' => $nutritionLimits,
            'error'           => $error,
            'success'         => $success,
        ]);
    }

    public function appointments() {
        $apptModel = $this->model('AppointmentModel');
        $pid       = $_SESSION['user_id'];
        $error     = null;
        $success   = null;

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

        if (isset($_GET['delete'])) {
            $apptModel->delete($_GET['delete'], $pid);
            header('Location: ' . BASE_URL . '/patient/appointments');
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
        $this->view('patient/education_view', ['name' => $_SESSION['user_name']]);
    }

    public function nearby() {
        $this->view('patient/nearby_view', ['name' => $_SESSION['user_name']]);
    }

    public function reports() {
        $bloodSugarModel = $this->model('BloodSugarModel');
        $medModel        = $this->model('MedicationModel');
        $mealModel       = $this->model('MealModel');
        $activityModel   = $this->model('ActivityModel');
        $apptModel       = $this->model('AppointmentModel');
        $pid             = $_SESSION['user_id'];

        $logs     = $bloodSugarModel->getLogs($pid);
        $latestBS = $bloodSugarModel->getLatest($pid);
        $last7    = $bloodSugarModel->getLast7($pid);
        $bsAvg    = !empty($last7) ? round(array_sum(array_column($last7, 'reading')) / count($last7)) : null;

        $medications = $medModel->getMedications($pid);
        $todayStats  = $medModel->getTodayStats($pid);
        $allLogs     = $medModel->getAllLogs($pid);

        $todayTotals    = $mealModel->getTodayTotals($pid);
        $actTodayTotals = $activityModel->getTodayTotals($pid);
        $weekTotals     = $activityModel->getWeekTotals($pid);
        $appts          = $apptModel->getAll($pid);

        $this->view('patient/reports_view', [
            'name'              => $_SESSION['user_name'],
            'logs'              => $logs,
            'bsLogs'            => $logs,
            'latestBS'          => $latestBS,
            'bsAvg'             => $bsAvg,
            'bsCount'           => count($logs),
            'medications'       => $medications,
            'todayStats'        => $todayStats,
            'allLogs'           => $allLogs,
            'todayTotals'       => $todayTotals,
            'weekTotals'        => $weekTotals,
            'activityTodayMins' => $actTodayTotals['total_minutes'] ?? 0,
            'appts'             => $appts,
        ]);
    }

    public function activity() {
        $activityModel = $this->model('ActivityModel');
        $pid           = $_SESSION['user_id'];
        $error         = null;
        $success       = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $activityModel->addActivity($pid, [
                'activity_name'    => trim($_POST['activity_name']),
                'duration_minutes' => (int) $_POST['duration_minutes'],
                'intensity'        => trim($_POST['intensity']),
                'notes'            => trim($_POST['notes'] ?? ''),
            ]);
            $success = 'Activity logged successfully!';
        }

        if (isset($_GET['delete'])) {
            $activityModel->deleteLog($_GET['delete'], $pid);
            header('Location: ' . BASE_URL . '/patient/activity');
            exit;
        }

        // Load personalised weight + activity goal from patient_profiles
        require_once __DIR__ . '/../../config/database.php';
        $db       = (new Database())->connect();
        $profStmt = $db->prepare(
            "SELECT weight_kg, activity_goal_mins
               FROM patient_profiles
              WHERE user_id = :uid
              LIMIT 1"
        );
        $profStmt->execute(['uid' => $pid]);
        $profile = $profStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $patientWeightKg  = isset($profile['weight_kg']) && $profile['weight_kg'] > 0
                            ? (float) $profile['weight_kg']
                            : null;                          // null = unknown
        $activityGoalMins = isset($profile['activity_goal_mins']) && $profile['activity_goal_mins'] > 0
                            ? (int) $profile['activity_goal_mins']
                            : 30;                            // ADA default

        $logs        = $activityModel->getLogs($pid);
        $todayLogs   = $activityModel->getTodayLogs($pid);
        $todayTotals = $activityModel->getTodayTotals($pid);
        $weekTotals  = $activityModel->getWeekTotals($pid);
        $last7Days   = $activityModel->getLast7Days($pid);

        $this->view('patient/activity_view', [
            'name'             => $_SESSION['user_name'],
            'logs'             => $logs,
            'todayLogs'        => $todayLogs,
            'todayTotals'      => $todayTotals,
            'weekTotals'       => $weekTotals,
            'last7Days'        => $last7Days,
            'patientWeightKg'  => $patientWeightKg,   // NEW
            'activityGoalMins' => $activityGoalMins,  // NEW
            'error'            => $error,
            'success'          => $success,
        ]);
    }

    public function caregiverRequests() {
        require_once __DIR__ . '/../../config/database.php';
        $db  = (new Database())->connect();
        $pid = $_SESSION['user_id'];

        if (isset($_GET['accept'])) {
            $db->prepare("UPDATE caregiver_links SET status = 'accepted', linked_at = NOW() WHERE caregiver_id = :cid AND patient_id = :pid AND status = 'pending'")
               ->execute(['cid' => $_GET['accept'], 'pid' => $pid]);
            header('Location: ' . BASE_URL . '/patient/caregiverRequests'); exit;
        }
        if (isset($_GET['decline'])) {
            $db->prepare("UPDATE caregiver_links SET status = 'declined' WHERE caregiver_id = :cid AND patient_id = :pid AND status = 'pending'")
               ->execute(['cid' => $_GET['decline'], 'pid' => $pid]);
            header('Location: ' . BASE_URL . '/patient/caregiverRequests'); exit;
        }
        if (isset($_GET['remove'])) {
            $db->prepare("DELETE FROM caregiver_links WHERE caregiver_id = :cid AND patient_id = :pid")
               ->execute(['cid' => $_GET['remove'], 'pid' => $pid]);
            header('Location: ' . BASE_URL . '/patient/caregiverRequests'); exit;
        }

        $stmt = $db->prepare("SELECT u.id, u.name, u.email, cl.requested_at FROM users u JOIN caregiver_links cl ON cl.caregiver_id = u.id WHERE cl.patient_id = :pid AND cl.status = 'pending' ORDER BY cl.requested_at DESC");
        $stmt->execute(['pid' => $pid]);
        $pendingRequests = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT u.id, u.name, u.email, cl.linked_at FROM users u JOIN caregiver_links cl ON cl.caregiver_id = u.id WHERE cl.patient_id = :pid AND cl.status = 'accepted' ORDER BY cl.linked_at DESC");
        $stmt->execute(['pid' => $pid]);
        $activeCaregivers = $stmt->fetchAll();

        $this->view('patient/caregiver_request_view', [
            'name'             => $_SESSION['user_name'],
            'pendingRequests'  => $pendingRequests,
            'activeCaregivers' => $activeCaregivers,
        ]);
    }

    public function profile() {
        $userModel = $this->model('UserModel');
        $pid       = $_SESSION['user_id'];
        $user      = $userModel->findById($pid);

        require_once __DIR__ . '/../../config/database.php';
        $db = (new Database())->connect();

        $s = $db->prepare("SELECT COUNT(*) FROM blood_sugar_logs WHERE patient_id = :id"); $s->execute(['id'=>$pid]); $bsCount   = (int)$s->fetchColumn();
        $s = $db->prepare("SELECT COUNT(*) FROM medications WHERE patient_id = :id");      $s->execute(['id'=>$pid]); $medCount  = (int)$s->fetchColumn();
        $s = $db->prepare("SELECT COUNT(*) FROM meal_logs WHERE patient_id = :id");         $s->execute(['id'=>$pid]); $mealCount = (int)$s->fetchColumn();
        $s = $db->prepare("SELECT COUNT(*) FROM caregiver_links WHERE patient_id = :id AND status = 'accepted'"); $s->execute(['id'=>$pid]); $cgCount = (int)$s->fetchColumn();

        $s = $db->prepare("SELECT u.id, u.name, u.email, cl.linked_at, cl.status FROM users u JOIN caregiver_links cl ON cl.caregiver_id = u.id WHERE cl.patient_id = :id ORDER BY cl.linked_at DESC");
        $s->execute(['id' => $pid]);
        $caregivers = $s->fetchAll();

        // Load weight + activity goal for profile display
        $s = $db->prepare("SELECT * FROM patient_profiles WHERE user_id = :id LIMIT 1");
        $s->execute(['id' => $pid]);
        $patientProfile = $s->fetch(PDO::FETCH_ASSOC) ?: [];

        $this->view('patient/profile_view', [
            'user'           => $user,
            'stats'          => ['blood_sugar_logs'=>$bsCount,'medications'=>$medCount,'meal_logs'=>$mealCount,'caregivers'=>$cgCount],
            'caregivers'     => $caregivers,
            'patientProfile' => $patientProfile,  // NEW
        ]);
    }

    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/patient/profile'); exit;
        }

        $userModel = $this->model('UserModel');
        $pid       = $_SESSION['user_id'];
        $action    = $_POST['action'] ?? '';

        if ($action === 'info') {
            $name  = trim($_POST['name']  ?? '');
            $email = trim($_POST['email'] ?? '');
            if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->redirectProfile('patient', 'Invalid name or email.'); return;
            }
            $existing = $userModel->findByEmail($email);
            if ($existing && (int)$existing['id'] !== $pid) {
                $this->redirectProfile('patient', 'That email is already in use.'); return;
            }
            $userModel->updateInfo($pid, $name, $email);
            $_SESSION['user_name'] = $name;

            // Upsert weight and activity goal into patient_profiles
            $weightKg = isset($_POST['weight_kg']) && is_numeric($_POST['weight_kg'])
                        ? max(20, min(300, (float) $_POST['weight_kg']))
                        : null;
            $goalMins = isset($_POST['activity_goal_mins']) && is_numeric($_POST['activity_goal_mins'])
                        ? max(10, min(180, (int) $_POST['activity_goal_mins']))
                        : 30;

            require_once __DIR__ . '/../../config/database.php';
            $db = (new Database())->connect();
            $db->prepare("
                INSERT INTO patient_profiles (user_id, weight_kg, activity_goal_mins)
                VALUES (:uid, :wt, :goal)
                ON DUPLICATE KEY UPDATE
                    weight_kg          = VALUES(weight_kg),
                    activity_goal_mins = VALUES(activity_goal_mins)
            ")->execute(['uid' => $pid, 'wt' => $weightKg, 'goal' => $goalMins]);

            $this->redirectProfile('patient', null, 'Profile updated successfully!');

        } elseif ($action === 'password') {
            $current = $_POST['current_password'] ?? '';
            $new     = $_POST['new_password']     ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $user    = $userModel->findById($pid);
            if (!password_verify($current, $user['password'])) { $this->redirectProfile('patient', 'Current password is incorrect.'); return; }
            if (strlen($new) < 8) { $this->redirectProfile('patient', 'New password must be at least 8 characters.'); return; }
            if ($new !== $confirm) { $this->redirectProfile('patient', 'New passwords do not match.'); return; }
            $userModel->updatePassword($pid, $new);
            $this->redirectProfile('patient', null, 'Password changed successfully!');
        }
    }

    private function redirectProfile($role, $error = null, $success = null) {
        $param = $error ? '?error='.urlencode($error) : '?success='.urlencode($success);
        header("Location: " . BASE_URL . "/{$role}/profile{$param}");
        exit;
    }

    public function setup2fa() {
        require_once __DIR__ . '/../../config/database.php';
        $db  = (new Database())->connect();
        $uid = $_SESSION['user_id'];

        require_once __DIR__ . '/../../vendor/autoload.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enable') {
            $code   = trim($_POST['code'] ?? '');
            $secret = trim($_POST['secret'] ?? '');

            $google2fa = new \PragmaRX\Google2FA\Google2FA();
            if ($google2fa->verifyKey($secret, $code)) {
                $db->prepare("UPDATE users SET two_fa_secret = :s, two_fa_enabled = 1 WHERE id = :id")
                   ->execute(['s' => $secret, 'id' => $uid]);
                header('Location: ' . BASE_URL . '/patient/profile?success=' . urlencode('2FA enabled successfully!'));
                exit;
            } else {
                header('Location: ' . BASE_URL . '/patient/setup2fa?error=1');
                exit;
            }
        }

        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $secret    = $google2fa->generateSecretKey();

        $stmt = $db->prepare("SELECT name, email FROM users WHERE id = :id");
        $stmt->execute(['id' => $uid]);
        $user = $stmt->fetch();

        $qrUrl = $google2fa->getQRCodeUrl('DiabeTrack', $user['email'], $secret);

        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(280),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $qrSvg  = $writer->writeString($qrUrl);

        $error = isset($_GET['error']) ? 'Invalid code. Please scan again and try.' : null;

        $this->view('patient/setup2fa_view', [
            'name'   => $_SESSION['user_name'],
            'secret' => $secret,
            'qrSvg'  => $qrSvg,
            'error'  => $error,
        ]);
    }

    public function disable2fa() {
        require_once __DIR__ . '/../../config/database.php';
        $db = (new Database())->connect();
        $db->prepare("UPDATE users SET two_fa_enabled = 0, two_fa_secret = NULL WHERE id = :id")
           ->execute(['id' => $_SESSION['user_id']]);
        header('Location: ' . BASE_URL . '/patient/profile?success=' . urlencode('2FA has been disabled.'));
        exit;
    }

    private function ensureMessagesTable($db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `chat_messages` (
                `id`           int(11)   NOT NULL AUTO_INCREMENT,
                `caregiver_id` int(11)   NOT NULL,
                `patient_id`   int(11)   NOT NULL,
                `sender_id`    int(11)   NOT NULL,
                `sender_type`  enum('caregiver','patient') NOT NULL,
                `body`         text      NOT NULL,
                `reaction`     varchar(10) NULL DEFAULT NULL,
                `sent_at`      timestamp NOT NULL DEFAULT current_timestamp(),
                `read_at`      timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_thread` (`caregiver_id`,`patient_id`,`sent_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $db->exec("CREATE TABLE IF NOT EXISTS `chat_typing` (`caregiver_id` int(11) NOT NULL,`patient_id` int(11) NOT NULL,`typer_type` enum('caregiver','patient') NOT NULL,`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),PRIMARY KEY (`caregiver_id`,`patient_id`,`typer_type`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        try { $db->exec("ALTER TABLE `chat_messages` ADD COLUMN `reaction` varchar(10) NULL DEFAULT NULL"); } catch(Exception $e) {}
    }

    private function getLinkedCaregiver($db, int $pid): ?array {
        $stmt = $db->prepare("
            SELECT u.* FROM users u
            JOIN caregiver_links cl ON cl.caregiver_id = u.id
            WHERE cl.patient_id = :pid AND cl.status = 'accepted'
            ORDER BY cl.linked_at ASC LIMIT 1
        ");
        $stmt->execute(['pid' => $pid]);
        return $stmt->fetch() ?: null;
    }

    public function messages() {
        require_once __DIR__ . '/../../config/database.php';
        $db  = (new Database())->connect();
        $pid = $_SESSION['user_id'];
        $cg  = $this->getLinkedCaregiver($db, $pid);

        $messages    = [];
        $unreadCount = 0;

        if ($cg) {
            $cid = $cg['id'];
            $this->ensureMessagesTable($db);

            // Mark caregiver messages as read
            $db->prepare("
                UPDATE chat_messages SET read_at = NOW()
                WHERE caregiver_id = :cid AND patient_id = :pid
                  AND sender_type = 'caregiver' AND read_at IS NULL
            ")->execute(['cid' => $cid, 'pid' => $pid]);

            $stmt = $db->prepare("
                SELECT * FROM chat_messages
                WHERE caregiver_id = :cid AND patient_id = :pid
                ORDER BY sent_at ASC
            ");
            $stmt->execute(['cid' => $cid, 'pid' => $pid]);
            $messages = $stmt->fetchAll();

            $unreadCount = count(array_filter($messages, fn($m) =>
                $m['sender_type'] === 'caregiver' && !$m['read_at']));
        }

        $this->view('patient/messages_view', [
            'name'        => $_SESSION['user_name'],
            'caregiver'   => $cg,
            'messages'    => $messages,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function sendPatientMessage() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false]); exit;
        }
        require_once __DIR__ . '/../../config/database.php';
        $db  = (new Database())->connect();
        $pid = $_SESSION['user_id'];
        $cg  = $this->getLinkedCaregiver($db, $pid);

        if (!$cg) { echo json_encode(['ok' => false, 'error' => 'No caregiver linked']); exit; }

        $body = trim($_POST['message'] ?? '');
        if (!$body || mb_strlen($body) > 500) {
            echo json_encode(['ok' => false, 'error' => 'Invalid message']); exit;
        }

        $this->ensureMessagesTable($db);
        $cid = $cg['id'];

        $stmt = $db->prepare("
            INSERT INTO chat_messages (caregiver_id, patient_id, sender_id, sender_type, body)
            VALUES (:cid, :pid, :sid, 'patient', :body)
        ");
        $stmt->execute(['cid' => $cid, 'pid' => $pid, 'sid' => $pid, 'body' => $body]);
        $newId = $db->lastInsertId();

        echo json_encode(['ok' => true, 'id' => $newId, 'sent_at' => date('h:i A')]); exit;
    }

    public function getPatientMessages() {
        header('Content-Type: application/json');
        require_once __DIR__ . '/../../config/database.php';
        $db  = (new Database())->connect();
        $pid = $_SESSION['user_id'];
        $cg  = $this->getLinkedCaregiver($db, $pid);
        if (!$cg) { echo json_encode(['messages'=>[],'typing'=>false]); exit; }

        $this->ensureMessagesTable($db);
        $cid   = $cg['id'];
        $after = (int)($_GET['after'] ?? 0);

        // Mark new caregiver messages as read
        $db->prepare("
            UPDATE chat_messages SET read_at=NOW()
            WHERE caregiver_id=:cid AND patient_id=:pid AND sender_type='caregiver' AND read_at IS NULL
        ")->execute(['cid'=>$cid,'pid'=>$pid]);

        $stmt = $db->prepare("
            SELECT id, sender_id, sender_type, body, reaction, sent_at, read_at
            FROM chat_messages
            WHERE caregiver_id=:cid AND patient_id=:pid AND id > :after
            ORDER BY sent_at ASC LIMIT 50
        ");
        $stmt->execute(['cid'=>$cid,'pid'=>$pid,'after'=>$after]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) { $r['sent_at'] = date('h:i A', strtotime($r['sent_at'])); }

        // Is caregiver typing?
        $ts = $db->prepare("SELECT updated_at FROM chat_typing WHERE caregiver_id=:cid AND patient_id=:pid AND typer_type='caregiver'");
        $ts->execute(['cid'=>$cid,'pid'=>$pid]);
        $tRow = $ts->fetch(PDO::FETCH_ASSOC);
        $typing = $tRow && (time()-strtotime($tRow['updated_at'])) < 4;

        echo json_encode(['messages'=>$rows,'typing'=>$typing]); exit;
    }

    public function setPatientTyping() {
        header('Content-Type: application/json');
        require_once __DIR__ . '/../../config/database.php';
        $db  = (new Database())->connect();
        $pid = $_SESSION['user_id'];
        $cg  = $this->getLinkedCaregiver($db, $pid);
        if (!$cg) { echo json_encode(['ok'=>false]); exit; }
        $this->ensureMessagesTable($db);
        $db->prepare("
            INSERT INTO chat_typing (caregiver_id,patient_id,typer_type,updated_at)
            VALUES (:cid,:pid,'patient',NOW())
            ON DUPLICATE KEY UPDATE updated_at=NOW()
        ")->execute(['cid'=>$cg['id'],'pid'=>$pid]);
        echo json_encode(['ok'=>true]); exit;
    }

    public function reactPatientMessage() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false]); exit; }
        require_once __DIR__ . '/../../config/database.php';
        $db  = (new Database())->connect();
        $pid = $_SESSION['user_id'];
        $cg  = $this->getLinkedCaregiver($db, $pid);
        if (!$cg) { echo json_encode(['ok'=>false]); exit; }
        $this->ensureMessagesTable($db);
        $id       = (int)($_POST['id'] ?? 0);
        $reaction = in_array($_POST['reaction']??'',['👍','❤️','✅','😮','😢','']) ? ($_POST['reaction']??'') : '';
        $db->prepare("UPDATE chat_messages SET reaction=:r WHERE id=:id AND caregiver_id=:cid AND patient_id=:pid")
           ->execute(['r'=>$reaction?:null,'id'=>$id,'cid'=>$cg['id'],'pid'=>$pid]);
        echo json_encode(['ok'=>true]); exit;
    }
}