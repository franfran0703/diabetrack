<?php

require_once __DIR__ . '/Controller.php';

class CaregiverController extends Controller {

    private $db;

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'caregiver') {
            header('Location: /diabetrack/public/auth/login');
            exit;
        }
        require_once __DIR__ . '/../../config/Database.php';
        $database   = new Database();
        $this->db   = $database->connect();
    }

    public function patients() {
    $success = null;
    $error   = null;

    // Handle link request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['patient_email']);

        // Find patient by email
        $stmt = $this->db->prepare("
            SELECT * FROM users 
            WHERE email = :email AND role = 'patient' 
            LIMIT 1
        ");
        $stmt->execute(['email' => $email]);
        $patient = $stmt->fetch();

        if (!$patient) {
            $error = 'No patient found with that email address.';
        } else {
            // Check if already linked
            $check = $this->db->prepare("
                SELECT * FROM caregiver_links 
                WHERE caregiver_id = :cid AND patient_id = :pid
            ");
            $check->execute([
                'cid' => $_SESSION['user_id'],
                'pid' => $patient['id']
            ]);

            if ($check->fetch()) {
                $error = 'You are already linked to this patient.';
            } else {
                // Create the link
                $link = $this->db->prepare("
                    INSERT INTO caregiver_links (caregiver_id, patient_id)
                    VALUES (:cid, :pid)
                ");
                $link->execute([
                    'cid' => $_SESSION['user_id'],
                    'pid' => $patient['id']
                ]);
                $success = 'Successfully linked to ' . $patient['name'] . '!';
            }
        }
    }

    // Handle unlink
    if (isset($_GET['unlink'])) {
        $unlink = $this->db->prepare("
            DELETE FROM caregiver_links 
            WHERE caregiver_id = :cid AND patient_id = :pid
        ");
        $unlink->execute([
            'cid' => $_SESSION['user_id'],
            'pid' => $_GET['unlink']
        ]);
        header('Location: /diabetrack/public/caregiver/patients');
        exit;
    }

    // Get all linked patients
    $stmt = $this->db->prepare("
        SELECT u.*, cl.linked_at FROM users u
        JOIN caregiver_links cl ON cl.patient_id = u.id
        WHERE cl.caregiver_id = :cid
        ORDER BY cl.linked_at DESC
    ");
    $stmt->execute(['cid' => $_SESSION['user_id']]);
    $linkedPatients = $stmt->fetchAll();

    $this->view('caregiver/patients_view', [
        'name'           => $_SESSION['user_name'],
        'linkedPatients' => $linkedPatients,
        'success'        => $success,
        'error'          => $error,
    ]);
}

    // Get the linked patient for this caregiver
    private function getLinkedPatient() {
        $stmt = $this->db->prepare("
            SELECT u.* FROM users u
            JOIN caregiver_links cl ON cl.patient_id = u.id
            WHERE cl.caregiver_id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        return $stmt->fetch();
    }

    public function dashboard() {
        $patient      = $this->getLinkedPatient();
        $latestSugar  = null;
        $missedMeds   = 0;
        $totalLogs    = 0;
        $unreadAlerts = 0;
        $recentAlerts = [];
        
        if ($patient) {
            $pid = $patient['id'];
            
            // Abnormal readings this week
            $ab = $this->db->prepare("
            SELECT COUNT(*) FROM blood_sugar_logs
            WHERE patient_id = :pid
            AND status != 'Normal'
            AND logged_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
            $ab->execute(['pid' => $pid]);
            $abnormalReadings = $ab->fetchColumn();

            $s = $this->db->prepare("SELECT * FROM blood_sugar_logs WHERE patient_id = :pid ORDER BY logged_at DESC LIMIT 1");
            $s->execute(['pid' => $pid]);
            $latestSugar = $s->fetch();

            $m = $this->db->prepare("SELECT COUNT(*) FROM medication_logs WHERE patient_id = :pid AND status = 'Missed' AND DATE(logged_at) = CURDATE()");
            $m->execute(['pid' => $pid]);
            $missedMeds = $m->fetchColumn();

            $t = $this->db->prepare("SELECT COUNT(*) FROM blood_sugar_logs WHERE patient_id = :pid AND logged_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $t->execute(['pid' => $pid]);
            $totalLogs = $t->fetchColumn();

            $a = $this->db->prepare("SELECT COUNT(*) FROM alerts WHERE user_id = :pid AND is_read = 0");
            $a->execute(['pid' => $pid]);
            $unreadAlerts = $a->fetchColumn();

            $r = $this->db->prepare("SELECT * FROM alerts WHERE user_id = :pid ORDER BY created_at DESC LIMIT 5");
            $r->execute(['pid' => $pid]);
            $recentAlerts = $r->fetchAll();

            
        }

            $this->view('caregiver/dashboard_view', [
            'name'             => $_SESSION['user_name'],
            'patient'          => $patient,
            'latestSugar'      => $latestSugar,
            'missedMeds'       => $missedMeds,
            'totalLogs'        => $totalLogs,
            'unreadAlerts'     => $unreadAlerts,
            'recentAlerts'     => $recentAlerts,
            'abnormalReadings' => $abnormalReadings ?? 0,
]);
    }

    public function bloodsugar() {
        $patient = $this->getLinkedPatient();
        $logs    = [];
        $last7   = [];
        $latest  = null;
        $stats   = ['total' => 0, 'high' => 0, 'low' => 0, 'normal' => 0];

        if ($patient) {
            $pid = $patient['id'];

            // All logs
            $stmt = $this->db->prepare("
                SELECT * FROM blood_sugar_logs
                WHERE patient_id = :pid
                ORDER BY logged_at DESC
            ");
            $stmt->execute(['pid' => $pid]);
            $logs = $stmt->fetchAll();

            // Latest
            $latest = !empty($logs) ? $logs[0] : null;

            // Last 7 for chart
            $stmt2 = $this->db->prepare("
                SELECT reading, status, logged_at FROM blood_sugar_logs
                WHERE patient_id = :pid
                ORDER BY logged_at DESC LIMIT 7
            ");
            $stmt2->execute(['pid' => $pid]);
            $last7 = array_reverse($stmt2->fetchAll());

            // Stats
            $stats['total']  = count($logs);
            $stats['high']   = count(array_filter($logs, fn($l) => $l['status'] === 'High'));
            $stats['low']    = count(array_filter($logs, fn($l) => $l['status'] === 'Low'));
            $stats['normal'] = count(array_filter($logs, fn($l) => $l['status'] === 'Normal'));
        }

    $this->view('caregiver/bloodsugar_view', [
        'name'    => $_SESSION['user_name'],
        'patient' => $patient,
        'logs'    => $logs,
        'last7'   => $last7,
        'latest'  => $latest,
        'stats'   => $stats,
    ]);
}

public function medication() {
    $patient = $this->getLinkedPatient();

    $medications = [];
    $todayLogs   = [];
    $allLogs     = [];
    $todayStats  = ['taken' => 0, 'missed' => 0, 'total' => 0];
    $loggedToday = [];

    if ($patient) {
        $pid = $patient['id'];

        // Medications schedule
        $stmt = $this->db->prepare("
            SELECT * FROM medications
            WHERE patient_id = :pid
            ORDER BY schedule_time ASC
        ");
        $stmt->execute(['pid' => $pid]);
        $medications = $stmt->fetchAll();

        // Today's logs
        $stmt = $this->db->prepare("
            SELECT ml.*, m.name, m.dosage, m.schedule_time
            FROM medication_logs ml
            JOIN medications m ON m.id = ml.medication_id
            WHERE ml.patient_id = :pid
            AND DATE(ml.logged_at) = CURDATE()
            ORDER BY ml.logged_at DESC
        ");
        $stmt->execute(['pid' => $pid]);
        $todayLogs = $stmt->fetchAll();

        // All logs (last 50)
        $stmt = $this->db->prepare("
            SELECT ml.*, m.name, m.dosage, m.schedule_time
            FROM medication_logs ml
            JOIN medications m ON m.id = ml.medication_id
            WHERE ml.patient_id = :pid
            ORDER BY ml.logged_at DESC
            LIMIT 50
        ");
        $stmt->execute(['pid' => $pid]);
        $allLogs = $stmt->fetchAll();

        // Today stats
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total,
                SUM(status = 'Taken')  as taken,
                SUM(status = 'Missed') as missed
            FROM medication_logs
            WHERE patient_id = :pid
            AND DATE(logged_at) = CURDATE()
        ");
        $stmt->execute(['pid' => $pid]);
        $todayStats = $stmt->fetch();

        // Which meds logged today
        foreach ($medications as $med) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM medication_logs
                WHERE medication_id = :mid
                AND patient_id = :pid
                AND DATE(logged_at) = CURDATE()
            ");
            $stmt->execute(['mid' => $med['id'], 'pid' => $pid]);
            $loggedToday[$med['id']] = $stmt->fetchColumn() > 0;
        }
    }

    $this->view('caregiver/medication_view', [
        'name'        => $_SESSION['user_name'],
        'patient'     => $patient,
        'medications' => $medications,
        'todayLogs'   => $todayLogs,
        'allLogs'     => $allLogs,
        'todayStats'  => $todayStats,
        'loggedToday' => $loggedToday,
    ]);
    }
    public function meals() {
    $patient     = $this->getLinkedPatient();
    $todayLogs   = [];
    $allLogs     = [];
    $todayTotals = [
        'total_carbs'    => 0,
        'total_calories' => 0,
        'total_sugar'    => 0,
        'total_protein'  => 0,
        'total_fat'      => 0,
        'total_fiber'    => 0,
        'total_sodium'   => 0,
        'total_meals'    => 0,
    ];

    if ($patient) {
        $pid = $patient['id'];

        // Today's meals
        $stmt = $this->db->prepare("
            SELECT * FROM meal_logs
            WHERE patient_id = :pid
              AND DATE(logged_at) = CURDATE()
            ORDER BY logged_at ASC
        ");
        $stmt->execute(['pid' => $pid]);
        $todayLogs = $stmt->fetchAll();

        // All logs
        $stmt = $this->db->prepare("
            SELECT * FROM meal_logs
            WHERE patient_id = :pid
            ORDER BY logged_at DESC
            LIMIT 30
        ");
        $stmt->execute(['pid' => $pid]);
        $allLogs = $stmt->fetchAll();

        // Today totals
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(carbs), 0)    as total_carbs,
                COALESCE(SUM(calories), 0) as total_calories,
                COALESCE(SUM(sugar), 0)    as total_sugar,
                COALESCE(SUM(protein), 0)  as total_protein,
                COALESCE(SUM(fat), 0)      as total_fat,
                COALESCE(SUM(fiber), 0)    as total_fiber,
                COALESCE(SUM(sodium), 0)   as total_sodium,
                COUNT(*) as total_meals
            FROM meal_logs
            WHERE patient_id = :pid
              AND DATE(logged_at) = CURDATE()
        ");
        $stmt->execute(['pid' => $pid]);
        $todayTotals = $stmt->fetch();
    }

    $this->view('caregiver/meals_view', [
        'name'        => $_SESSION['user_name'],
        'patient'     => $patient,
        'todayLogs'   => $todayLogs,
        'allLogs'     => $allLogs,
        'todayTotals' => $todayTotals,
    ]);
}
public function alerts() {
    $patient      = $this->getLinkedPatient();
    $allAlerts    = [];
    $unreadCount  = 0;
    $stats        = ['total' => 0, 'high' => 0, 'missed' => 0, 'other' => 0];

    if ($patient) {
        $pid = $patient['id'];

        // Mark all as read when caregiver opens this page
        $this->db->prepare("
            UPDATE alerts SET is_read = 1
            WHERE user_id = :pid
        ")->execute(['pid' => $pid]);

        // Get all alerts
        $stmt = $this->db->prepare("
            SELECT * FROM alerts
            WHERE user_id = :pid
            ORDER BY created_at DESC
        ");
        $stmt->execute(['pid' => $pid]);
        $allAlerts = $stmt->fetchAll();

        // Stats
        $stats['total'] = count($allAlerts);
        foreach ($allAlerts as $a) {
            if (str_contains($a['type'], 'Sugar'))  $stats['high']++;
            elseif (str_contains($a['type'], 'Dose') ||
                    str_contains($a['type'], 'Missed')) $stats['missed']++;
            else $stats['other']++;
        }
    }

    $this->view('caregiver/alerts_view', [
        'name'       => $_SESSION['user_name'],
        'patient'    => $patient,
        'allAlerts'  => $allAlerts,
        'stats'      => $stats,
    ]);
}
}