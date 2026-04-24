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
    $latestBloodSugar       = $latest['reading']      ?? null;
    $latestBloodSugarStatus = $latest['status']        ?? null;

    // Medications logged today
    $todayStats = $medModel->getTodayStats($pid);
    $medsToday  = $todayStats['total'] ?? null;

    // Carbs today
    $todayTotals = $mealModel->getTodayTotals($pid);
    $carbsToday  = ($todayTotals['total_meals'] > 0)  ? $todayTotals['total_carbs']   : null;

    // Activity minutes today
    $activityTotals = $activityModel->getTodayTotals($pid);
    $activityToday  = ($activityTotals['total_activities'] > 0)  ? $activityTotals['total_minutes']   : null;

    $this->view('patient/dashboard_view', [
        'name'                   => $_SESSION['user_name'],
        'latest'                 => $latest,
        'latestBloodSugar'       => $latestBloodSugar,
        'latestBloodSugarStatus' => $latestBloodSugarStatus,
        'medsToday'              => $medsToday,
        'carbsToday'             => $carbsToday,
        'activityToday'          => $activityToday,
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
}