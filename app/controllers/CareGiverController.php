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
                $rel = trim($_POST['relationship_to_patient'] ?? '');
                $link = $this->db->prepare("
                    INSERT INTO caregiver_links (caregiver_id, patient_id, relationship_to_patient)
                    VALUES (:cid, :pid, :rel)
                ");
                $link->execute([
                    'cid' => $_SESSION['user_id'],
                    'pid' => $patient['id'],
                    'rel' => $rel ?: null,
                ]);
                $success = 'Request sent to ' . $patient['name'] . '. Waiting for them to accept.';
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

    // Get all patients with their link status
    $stmt = $this->db->prepare("
        SELECT u.id, u.name, u.email,
               cl.status, cl.linked_at, cl.requested_at,
               cl.relationship_to_patient
        FROM users u
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
        // Get all accepted patients
        $stmt = $this->db->prepare("
            SELECT u.* FROM users u
            JOIN caregiver_links cl ON cl.patient_id = u.id
            WHERE cl.caregiver_id = :id AND cl.status = 'accepted'
            ORDER BY cl.linked_at ASC
        ");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $patients = $stmt->fetchAll();

        if (empty($patients)) return null;

        // If active patient is set in session and still valid, use it
        if (isset($_SESSION['active_patient_id'])) {
            foreach ($patients as $p) {
                if ($p['id'] == $_SESSION['active_patient_id']) {
                    return $p;
                }
            }
        }

        // Default to first and save in session
        $_SESSION['active_patient_id'] = $patients[0]['id'];
        return $patients[0];
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

            // Last 7 blood sugar readings for sparkline
            $sp = $this->db->prepare("SELECT reading, status, logged_at FROM blood_sugar_logs WHERE patient_id = :pid ORDER BY logged_at DESC LIMIT 7");
            $sp->execute(['pid' => $pid]);
            $sparkline = array_reverse($sp->fetchAll());

            // Medication compliance this week
            $mc = $this->db->prepare("SELECT COUNT(*) FROM medication_logs WHERE patient_id = :pid AND status = 'Taken' AND logged_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $mc->execute(['pid' => $pid]);
            $medTaken = $mc->fetchColumn();

            $mm = $this->db->prepare("SELECT COUNT(*) FROM medication_logs WHERE patient_id = :pid AND status = 'Missed' AND logged_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $mm->execute(['pid' => $pid]);
            $medMissed = $mm->fetchColumn();

            $medTotal = $medTaken + $medMissed;
            $medRate  = $medTotal > 0 ? round($medTaken / $medTotal * 100) : 0;

            // Recent activity logs
            $act = $this->db->prepare("SELECT * FROM activity_logs WHERE patient_id = :pid ORDER BY logged_at DESC LIMIT 3");
            $act->execute(['pid' => $pid]);
            $recentActivity = $act->fetchAll();

            // Recent meals
            $ml = $this->db->prepare("SELECT * FROM meal_logs WHERE patient_id = :pid ORDER BY logged_at DESC LIMIT 3");
            $ml->execute(['pid' => $pid]);
            $recentMeals = $ml->fetchAll();
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
            'sparkline'        => $sparkline        ?? [],
            'medRate'          => $medRate          ?? 0,
            'medTaken'         => $medTaken         ?? 0,
            'medMissed'        => $medMissed        ?? 0,
            'recentActivity'   => $recentActivity   ?? [],
            'recentMeals'      => $recentMeals      ?? [],
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
    $last7Days   = [];
    $todayTotals = [
        'total_carbs'    => 0, 'total_calories' => 0,
        'total_sugar'    => 0, 'total_protein'  => 0,
        'total_fat'      => 0, 'total_fiber'    => 0,
        'total_sodium'   => 0, 'total_meals'    => 0,
    ];
    $defaultLimits = [
        'carbs' => 130, 'calories' => 1800, 'sugar' => 50,
        'protein' => 60, 'fat' => 65, 'fiber' => 25, 'sodium' => 2300,
    ];
    $limits = $defaultLimits;

    if ($patient) {
        $pid = $patient['id'];
        $cid = $_SESSION['user_id'];

        // Ensure nutrition_limits table exists
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `nutrition_limits` (
                `id`           int(11)      NOT NULL AUTO_INCREMENT,
                `caregiver_id` int(11)      NOT NULL,
                `patient_id`   int(11)      NOT NULL,
                `carbs`        decimal(7,2) NOT NULL DEFAULT 130.00,
                `calories`     decimal(7,2) NOT NULL DEFAULT 1800.00,
                `sugar`        decimal(6,2) NOT NULL DEFAULT 50.00,
                `protein`      decimal(6,2) NOT NULL DEFAULT 60.00,
                `fat`          decimal(6,2) NOT NULL DEFAULT 65.00,
                `fiber`        decimal(6,2) NOT NULL DEFAULT 25.00,
                `sodium`       decimal(7,2) NOT NULL DEFAULT 2300.00,
                `updated_at`   timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_cg_patient` (`caregiver_id`,`patient_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Fetch custom limits for this caregiver-patient pair
        $lStmt = $this->db->prepare("
            SELECT * FROM nutrition_limits
            WHERE caregiver_id = :cid AND patient_id = :pid
        ");
        $lStmt->execute(['cid' => $cid, 'pid' => $pid]);
        $savedLimits = $lStmt->fetch();
        if ($savedLimits) {
            $limits = array_merge($defaultLimits, array_intersect_key($savedLimits, $defaultLimits));
        }

        // Today's meals
        $stmt = $this->db->prepare("
            SELECT * FROM meal_logs
            WHERE patient_id = :pid AND DATE(logged_at) = CURDATE()
            ORDER BY logged_at ASC
        ");
        $stmt->execute(['pid' => $pid]);
        $todayLogs = $stmt->fetchAll();

        // All logs (last 60)
        $stmt = $this->db->prepare("
            SELECT * FROM meal_logs
            WHERE patient_id = :pid
            ORDER BY logged_at DESC LIMIT 60
        ");
        $stmt->execute(['pid' => $pid]);
        $allLogs = $stmt->fetchAll();

        // Last 7 days daily totals for the summary
        $stmt = $this->db->prepare("
            SELECT DATE(logged_at) as day,
                   COALESCE(SUM(calories), 0) as cals,
                   COALESCE(SUM(carbs), 0)    as carbs,
                   COUNT(*) as meals
            FROM meal_logs
            WHERE patient_id = :pid
              AND logged_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(logged_at)
            ORDER BY day ASC
        ");
        $stmt->execute(['pid' => $pid]);
        $rawLast7 = $stmt->fetchAll();

        // Build full 7-day array (fill gaps with zeros)
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $last7Days[$d] = ['day' => $d, 'cals' => 0, 'carbs' => 0, 'meals' => 0];
        }
        foreach ($rawLast7 as $r) {
            if (isset($last7Days[$r['day']])) $last7Days[$r['day']] = $r;
        }

        // Today totals
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(carbs),    0) as total_carbs,
                   COALESCE(SUM(calories), 0) as total_calories,
                   COALESCE(SUM(sugar),    0) as total_sugar,
                   COALESCE(SUM(protein),  0) as total_protein,
                   COALESCE(SUM(fat),      0) as total_fat,
                   COALESCE(SUM(fiber),    0) as total_fiber,
                   COALESCE(SUM(sodium),   0) as total_sodium,
                   COUNT(*) as total_meals
            FROM meal_logs
            WHERE patient_id = :pid AND DATE(logged_at) = CURDATE()
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
        'limits'      => $limits,
        'last7Days'   => $last7Days,
    ]);
}

public function saveNutritionLimits() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /diabetrack/public/caregiver/meals');
        exit;
    }
    $patient = $this->getLinkedPatient();
    if (!$patient) {
        header('Location: /diabetrack/public/caregiver/meals');
        exit;
    }

    $cid    = $_SESSION['user_id'];
    $pid    = $patient['id'];
    $fields = ['carbs', 'calories', 'sugar', 'protein', 'fat', 'fiber', 'sodium'];
    $data   = ['cid' => $cid, 'pid' => $pid];
    foreach ($fields as $f) {
        $data[$f] = max(0, (float)($_POST[$f] ?? 0));
    }

    // Ensure table exists
    $this->db->exec("
        CREATE TABLE IF NOT EXISTS `nutrition_limits` (
            `id`           int(11)      NOT NULL AUTO_INCREMENT,
            `caregiver_id` int(11)      NOT NULL,
            `patient_id`   int(11)      NOT NULL,
            `carbs`        decimal(7,2) NOT NULL DEFAULT 130.00,
            `calories`     decimal(7,2) NOT NULL DEFAULT 1800.00,
            `sugar`        decimal(6,2) NOT NULL DEFAULT 50.00,
            `protein`      decimal(6,2) NOT NULL DEFAULT 60.00,
            `fat`          decimal(6,2) NOT NULL DEFAULT 65.00,
            `fiber`        decimal(6,2) NOT NULL DEFAULT 25.00,
            `sodium`       decimal(7,2) NOT NULL DEFAULT 2300.00,
            `updated_at`   timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_cg_patient` (`caregiver_id`,`patient_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $stmt = $this->db->prepare("
        INSERT INTO nutrition_limits
            (caregiver_id, patient_id, carbs, calories, sugar, protein, fat, fiber, sodium)
        VALUES
            (:cid, :pid, :carbs, :calories, :sugar, :protein, :fat, :fiber, :sodium)
        ON DUPLICATE KEY UPDATE
            carbs    = VALUES(carbs),
            calories = VALUES(calories),
            sugar    = VALUES(sugar),
            protein  = VALUES(protein),
            fat      = VALUES(fat),
            fiber    = VALUES(fiber),
            sodium   = VALUES(sodium)
    ");
    $stmt->execute($data);

    header('Location: /diabetrack/public/caregiver/meals?saved=1');
    exit;
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
public function reports() {
    $patient = $this->getLinkedPatient();

    $range      = $_GET['range'] ?? '7';
    $dateFrom   = $_GET['date_from'] ?? date('Y-m-d', strtotime("-{$range} days"));
    $dateTo     = $_GET['date_to']   ?? date('Y-m-d');

    $bloodSugar  = [];
    $medications = [];
    $meals       = [];
    $activities  = [];
    $stats       = [];

    if ($patient) {
        $pid = $patient['id'];

        // Blood sugar logs
        $stmt = $this->db->prepare("
            SELECT * FROM blood_sugar_logs
            WHERE patient_id = :pid
              AND DATE(logged_at) BETWEEN :from AND :to
            ORDER BY logged_at ASC
        ");
        $stmt->execute(['pid' => $pid, 'from' => $dateFrom, 'to' => $dateTo]);
        $bloodSugar = $stmt->fetchAll();

        // Medication logs
        $stmt = $this->db->prepare("
            SELECT ml.*, m.name, m.dosage, m.frequency
            FROM medication_logs ml
            JOIN medications m ON m.id = ml.medication_id
            WHERE ml.patient_id = :pid
              AND DATE(ml.logged_at) BETWEEN :from AND :to
            ORDER BY ml.logged_at ASC
        ");
        $stmt->execute(['pid' => $pid, 'from' => $dateFrom, 'to' => $dateTo]);
        $medications = $stmt->fetchAll();

        // Meal logs
        $stmt = $this->db->prepare("
            SELECT * FROM meal_logs
            WHERE patient_id = :pid
              AND DATE(logged_at) BETWEEN :from AND :to
            ORDER BY logged_at ASC
        ");
        $stmt->execute(['pid' => $pid, 'from' => $dateFrom, 'to' => $dateTo]);
        $meals = $stmt->fetchAll();

        // Activity logs
        $stmt = $this->db->prepare("
            SELECT * FROM activity_logs
            WHERE patient_id = :pid
              AND DATE(logged_at) BETWEEN :from AND :to
            ORDER BY logged_at ASC
        ");
        $stmt->execute(['pid' => $pid, 'from' => $dateFrom, 'to' => $dateTo]);
        $activities = $stmt->fetchAll();

        // Compute stats
        $stats = [
            'total_readings'   => count($bloodSugar),
            'high_readings'    => count(array_filter($bloodSugar, fn($r) => $r['status'] === 'High')),
            'low_readings'     => count(array_filter($bloodSugar, fn($r) => $r['status'] === 'Low')),
            'normal_readings'  => count(array_filter($bloodSugar, fn($r) => $r['status'] === 'Normal')),
            'avg_sugar'        => count($bloodSugar) > 0
                ? round(array_sum(array_column($bloodSugar, 'reading')) / count($bloodSugar), 1)
                : 0,
            'total_doses'      => count($medications),
            'taken_doses'      => count(array_filter($medications, fn($m) => $m['status'] === 'Taken')),
            'missed_doses'     => count(array_filter($medications, fn($m) => $m['status'] === 'Missed')),
            'total_meals'      => count($meals),
            'avg_carbs'        => count($meals) > 0
                ? round(array_sum(array_column($meals, 'carbs')) / count($meals), 1)
                : 0,
            'total_activities' => count($activities),
            'total_minutes'    => array_sum(array_column($activities, 'duration_minutes')),
        ];
    }

    // Handle PDF download
    if (isset($_GET['pdf']) && $patient) {
        $this->generatePDF($patient, $bloodSugar, $medications, $meals, $activities, $stats, $dateFrom, $dateTo);
        exit;
    }

    $this->view('caregiver/reports_view', [
        'name'        => $_SESSION['user_name'],
        'patient'     => $patient,
        'bloodSugar'  => $bloodSugar,
        'medications' => $medications,
        'meals'       => $meals,
        'activities'  => $activities,
        'stats'       => $stats,
        'range'       => $range,
        'dateFrom'    => $dateFrom,
        'dateTo'      => $dateTo,
    ]);
}
public function switchPatient() {
    $pid = $_GET['pid'] ?? null;
    if ($pid) {
        $check = $this->db->prepare("
            SELECT 1 FROM caregiver_links
            WHERE caregiver_id = :cid AND patient_id = :pid AND status = 'accepted'
        ");
        $check->execute([
            'cid' => $_SESSION['user_id'],
            'pid' => $pid
        ]);
        if ($check->fetch()) {
            $_SESSION['active_patient_id'] = $pid;
        }
    }
    $redirect = $_GET['redirect'] ?? '/diabetrack/public/caregiver/dashboard';
    // Whitelist: only allow relative paths within this app
    if (!str_starts_with($redirect, '/diabetrack/')) {
        $redirect = '/diabetrack/public/caregiver/dashboard';
    }
    header('Location: ' . $redirect);
    exit;
}

public function profile() {
    $cid       = $_SESSION['user_id'];
    $userModel = $this->model('UserModel');
    $user      = $userModel->findById($cid);
    $profile   = $userModel->findCaregiverProfile($cid);

    $twoFa = $this->db->prepare("SELECT two_fa_enabled FROM users WHERE id = :id");
    $twoFa->execute(['id' => $cid]);
    $twoFaEnabled = (bool) $twoFa->fetchColumn();

    $ap = $this->db->prepare("SELECT COUNT(*) FROM caregiver_links WHERE caregiver_id = :id AND status = 'accepted'");
    $ap->execute(['id' => $cid]);

    $tp = $this->db->prepare("SELECT COUNT(*) FROM caregiver_links WHERE caregiver_id = :id");
    $tp->execute(['id' => $cid]);

    // alerts.patient_id links to patients this caregiver monitors
    $al = $this->db->prepare("
        SELECT COUNT(*) FROM alerts
        WHERE patient_id IN (
            SELECT patient_id FROM caregiver_links
            WHERE caregiver_id = :id AND status = 'accepted'
        )
    ");
    $al->execute(['id' => $cid]);

    $stats = [
        'active_patients' => $ap->fetchColumn(),
        'total_patients'  => $tp->fetchColumn(),
        'alerts_sent'     => $al->fetchColumn(),
        'reports_created' => 0,
    ];

    $ps = $this->db->prepare("
        SELECT u.*, cl.linked_at FROM users u
        JOIN caregiver_links cl ON cl.patient_id = u.id
        WHERE cl.caregiver_id = :id AND cl.status = 'accepted'
        ORDER BY cl.linked_at DESC
    ");
    $ps->execute(['id' => $cid]);
    $patients = $ps->fetchAll();

    $this->view('caregiver/profile_view', [
        'name'         => $_SESSION['user_name'],
        'user'         => $user,
        'profile'      => $profile,
        'stats'        => $stats,
        'patients'     => $patients,
        'twoFaEnabled' => $twoFaEnabled,
    ]);
}

public function updateProfile() {
    $action = $_POST['action'] ?? '';

    if ($action === 'info') {
        $name  = trim($_POST['name']);
        $email = trim($_POST['email']);

        // Check email not taken by someone else
        $check = $this->db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $check->execute(['email' => $email, 'id' => $_SESSION['user_id']]);
        if ($check->fetch()) {
            header('Location: /diabetrack/public/caregiver/profile?error=' . urlencode('Email already in use.'));
            exit;
        }

        $stmt = $this->db->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
        $stmt->execute(['name' => $name, 'email' => $email, 'id' => $_SESSION['user_id']]);
        $_SESSION['user_name'] = $name;
        header('Location: /diabetrack/public/caregiver/profile?success=' . urlencode('Profile updated successfully.'));
        exit;
    }

    if ($action === 'password') {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!password_verify($_POST['current_password'], $user['password'])) {
            header('Location: /diabetrack/public/caregiver/profile?error=' . urlencode('Current password is incorrect.'));
            exit;
        }
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            header('Location: /diabetrack/public/caregiver/profile?error=' . urlencode('New passwords do not match.'));
            exit;
        }
        if (strlen($_POST['new_password']) < 8) {
            header('Location: /diabetrack/public/caregiver/profile?error=' . urlencode('Password must be at least 8 characters.'));
            exit;
        }

        $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = :pw WHERE id = :id");
        $stmt->execute(['pw' => $hash, 'id' => $_SESSION['user_id']]);
        header('Location: /diabetrack/public/caregiver/profile?success=' . urlencode('Password updated successfully.'));
        exit;
    }

    if ($action === 'caregiver_profile') {
        $userModel = $this->model('UserModel');
        $userModel->updateCaregiverProfile($_SESSION['user_id'], [
            'relationship_to_patient' => $_POST['relationship_to_patient'] ?? null,
            'contact_number'          => $_POST['contact_number']          ?? null,
            'address'                 => $_POST['address']                 ?? null,
        ]);
        header('Location: /diabetrack/public/caregiver/profile?success=' . urlencode('Profile updated.'));
        exit;
    }

    header('Location: /diabetrack/public/caregiver/profile');
    exit;
}
public function setup2fa() {
    require_once __DIR__ . '/../../vendor/autoload.php';

    $db  = $this->db;
    $uid = $_SESSION['user_id'];

    // Handle enable POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enable') {
        $code   = trim($_POST['code'] ?? '');
        $secret = trim($_POST['secret'] ?? '');

        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $valid     = $google2fa->verifyKey($secret, $code);

        if ($valid) {
            $db->prepare("UPDATE users SET two_fa_secret = :s, two_fa_enabled = 1 WHERE id = :id")
               ->execute(['s' => $secret, 'id' => $uid]);
            header('Location: /diabetrack/public/caregiver/profile?success=' . urlencode('2FA enabled successfully!'));
            exit;
        } else {
            // Wrong code — regenerate and show error
            header('Location: /diabetrack/public/caregiver/setup2fa?error=1');
            exit;
        }
    }

    // Generate new secret
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

    $this->view('caregiver/setup2fa_view', [
        'name'   => $_SESSION['user_name'],
        'secret' => $secret,
        'qrSvg'  => $qrSvg,
        'error'  => $error,
    ]);
}

public function disable2fa() {
    $this->db->prepare("UPDATE users SET two_fa_enabled = 0, two_fa_secret = NULL WHERE id = :id")
            ->execute(['id' => $_SESSION['user_id']]);
    header('Location: /diabetrack/public/caregiver/profile?success=' . urlencode('2FA has been disabled.'));
    exit;
}
}
