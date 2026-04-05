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
        $latest = $bloodSugarModel->getLatest($_SESSION['user_id']);

        $this->view('patient/dashboard_view', [
            'name'   => $_SESSION['user_name'],
            'latest' => $latest
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
}