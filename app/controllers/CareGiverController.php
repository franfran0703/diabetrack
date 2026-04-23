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

private function generatePDF($patient, $bloodSugar, $medications, $meals, $activities, $stats, $dateFrom, $dateTo) {
    require_once __DIR__ . '/../../vendor/autoload.php';

    $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('DiabeTrack');
    $pdf->SetAuthor('DiabeTrack');
    $pdf->SetTitle('Health Report — ' . $patient['name']);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    // Colors
    $coral  = [249, 116, 71];
    $dark   = [28,  15,  10];
    $muted  = [160, 113, 79];
    $light  = [253, 232, 220];

    // ── HEADER ────────────────────────────────────────────
    $pdf->SetFillColor(...$coral);
    $pdf->Rect(0, 0, 210, 36, 'F');

    $pdf->SetFont('helvetica', 'B', 22);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY(15, 8);
    $pdf->Cell(0, 10, 'DiabeTrack Health Report', 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(255, 220, 190);
    $pdf->SetXY(15, 20);
    $pdf->Cell(0, 6, 'Generated on ' . date('F j, Y') . '  |  Period: ' . date('M d', strtotime($dateFrom)) . ' – ' . date('M d, Y', strtotime($dateTo)), 0, 1, 'L');

    // ── PATIENT INFO ───────────────────────────────────────
    $pdf->SetY(44);
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->SetTextColor(...$dark);
    $pdf->Cell(0, 8, 'Patient Information', 0, 1, 'L');

    $pdf->SetFillColor(...$light);
    $pdf->RoundedRect(15, $pdf->GetY(), 180, 22, 4, '1111', 'F');

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(...$dark);
    $pdf->SetXY(20, $pdf->GetY() + 4);
    $pdf->Cell(40, 6, 'Patient Name:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(80, 6, $patient['name'], 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(20, 6, 'Email:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, $patient['email'], 0, 1, 'L');

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(20, $pdf->GetY());
    $pdf->Cell(40, 6, 'Report Period:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, date('F j, Y', strtotime($dateFrom)) . ' to ' . date('F j, Y', strtotime($dateTo)), 0, 1, 'L');

    $pdf->SetY($pdf->GetY() + 8);

    // ── SUMMARY STATS ─────────────────────────────────────
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->SetTextColor(...$dark);
    $pdf->Cell(0, 8, 'Health Summary', 0, 1, 'L');

    // 4-column stats grid
    $statCols = [
        ['label' => 'Avg Blood Sugar', 'value' => $stats['avg_sugar'] . ' mg/dL', 'sub' => $stats['total_readings'] . ' readings'],
        ['label' => 'Medication Rate', 'value' => $stats['total_doses'] > 0 ? round($stats['taken_doses'] / $stats['total_doses'] * 100) . '%' : '—', 'sub' => $stats['taken_doses'] . ' of ' . $stats['total_doses'] . ' taken'],
        ['label' => 'Avg Daily Carbs',  'value' => $stats['avg_carbs'] . 'g',      'sub' => $stats['total_meals'] . ' meals logged'],
        ['label' => 'Activity',         'value' => $stats['total_minutes'] . ' min', 'sub' => $stats['total_activities'] . ' sessions'],
    ];

    $x = 15;
    $y = $pdf->GetY();
    $w = 43;
    foreach ($statCols as $i => $sc) {
        $pdf->SetFillColor(...$light);
        $pdf->RoundedRect($x + $i * ($w + 2), $y, $w, 24, 4, '1111', 'F');

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(...$coral);
        $pdf->SetXY($x + $i * ($w + 2), $y + 4);
        $pdf->Cell($w, 8, $sc['value'], 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(...$dark);
        $pdf->SetXY($x + $i * ($w + 2), $y + 12);
        $pdf->Cell($w, 5, $sc['label'], 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(...$muted);
        $pdf->SetXY($x + $i * ($w + 2), $y + 17);
        $pdf->Cell($w, 5, $sc['sub'], 0, 1, 'C');
    }

    $pdf->SetY($y + 32);

    // Helper: section header
    $sectionHeader = function($title) use ($pdf, $coral, $dark) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(...$dark);
        $pdf->SetFillColor(...$coral);
        $pdf->Cell(4, 7, '', 0, 0, 'L', true);
        $pdf->SetTextColor(...$dark);
        $pdf->Cell(0, 7, '  ' . $title, 0, 1, 'L');
        $pdf->SetY($pdf->GetY() + 2);
    };

    // Helper: table header
    $tableHeader = function($cols) use ($pdf, $light, $dark) {
        $pdf->SetFillColor(...$light);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(...$dark);
        foreach ($cols as [$w, $label]) {
            $pdf->Cell($w, 7, $label, 0, 0, 'L', true);
        }
        $pdf->Ln();
    };

    // ── BLOOD SUGAR ────────────────────────────────────────
    $sectionHeader('Blood Sugar Logs');
    if (empty($bloodSugar)) {
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(...$muted);
        $pdf->Cell(0, 6, 'No blood sugar logs in this period.', 0, 1);
    } else {
        $tableHeader([[35,'Date & Time'],[30,'Reading'],[30,'Type'],[25,'Status'],[60,'Notes']]);
        $pdf->SetFont('helvetica', '', 8);
        foreach ($bloodSugar as $r) {
            $pdf->SetTextColor(...$dark);
            $pdf->SetFillColor(255,255,255);
            if ($r['status'] === 'High')   $pdf->SetTextColor(192, 74, 32);
            if ($r['status'] === 'Low')    $pdf->SetTextColor(180, 100, 0);
            $pdf->Cell(35, 6, date('M d, Y h:i A', strtotime($r['logged_at'])), 0, 0, 'L');
            $pdf->Cell(30, 6, $r['reading'] . ' mg/dL', 0, 0, 'L');
            $pdf->SetTextColor(...$dark);
            $pdf->Cell(30, 6, $r['reading_type'], 0, 0, 'L');
            $statusColor = $r['status'] === 'High' ? [192,74,32] : ($r['status'] === 'Low' ? [180,100,0] : [22,120,60]);
            $pdf->SetTextColor(...$statusColor);
            $pdf->Cell(25, 6, $r['status'], 0, 0, 'L');
            $pdf->SetTextColor(...$muted);
            $pdf->Cell(60, 6, $r['notes'] ?? '—', 0, 1, 'L');
        }
    }

    $pdf->SetY($pdf->GetY() + 6);

    // ── MEDICATION ─────────────────────────────────────────
    if ($pdf->GetY() > 240) $pdf->AddPage();
    $sectionHeader('Medication Logs');
    if (empty($medications)) {
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(...$muted);
        $pdf->Cell(0, 6, 'No medication logs in this period.', 0, 1);
    } else {
        $tableHeader([[35,'Date & Time'],[50,'Medication'],[30,'Dosage'],[30,'Frequency'],[35,'Status']]);
        $pdf->SetFont('helvetica', '', 8);
        foreach ($medications as $m) {
            $pdf->SetTextColor(...$dark);
            $pdf->Cell(35, 6, date('M d, Y h:i A', strtotime($m['logged_at'])), 0, 0, 'L');
            $pdf->Cell(50, 6, $m['name'], 0, 0, 'L');
            $pdf->Cell(30, 6, $m['dosage'], 0, 0, 'L');
            $pdf->Cell(30, 6, $m['frequency'], 0, 0, 'L');
            $statusColor = $m['status'] === 'Taken' ? [22,120,60] : [192,74,32];
            $pdf->SetTextColor(...$statusColor);
            $pdf->Cell(35, 6, $m['status'], 0, 1, 'L');
        }
    }

    $pdf->SetY($pdf->GetY() + 6);

    // ── MEALS ──────────────────────────────────────────────
    if ($pdf->GetY() > 240) $pdf->AddPage();
    $sectionHeader('Meal Logs');
    if (empty($meals)) {
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(...$muted);
        $pdf->Cell(0, 6, 'No meal logs in this period.', 0, 1);
    } else {
        $tableHeader([[35,'Date & Time'],[50,'Meal'],[25,'Type'],[20,'Carbs'],[20,'Cal'],[30,'Notes']]);
        $pdf->SetFont('helvetica', '', 8);
        foreach ($meals as $m) {
            $pdf->SetTextColor(...$dark);
            $pdf->Cell(35, 6, date('M d, Y h:i A', strtotime($m['logged_at'])), 0, 0, 'L');
            $pdf->Cell(50, 6, $m['meal_name'], 0, 0, 'L');
            $pdf->Cell(25, 6, $m['meal_type'], 0, 0, 'L');
            $pdf->Cell(20, 6, $m['carbs'] . 'g', 0, 0, 'L');
            $pdf->Cell(20, 6, $m['calories'] ? $m['calories'] . ' kcal' : '—', 0, 0, 'L');
            $pdf->SetTextColor(...$muted);
            $pdf->Cell(30, 6, $m['notes'] ?? '—', 0, 1, 'L');
        }
    }

    $pdf->SetY($pdf->GetY() + 6);

    // ── ACTIVITY ───────────────────────────────────────────
    if ($pdf->GetY() > 240) $pdf->AddPage();
    $sectionHeader('Activity Logs');
    if (empty($activities)) {
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(...$muted);
        $pdf->Cell(0, 6, 'No activity logs in this period.', 0, 1);
    } else {
        $tableHeader([[35,'Date & Time'],[55,'Activity'],[30,'Duration'],[30,'Intensity'],[30,'Notes']]);
        $pdf->SetFont('helvetica', '', 8);
        foreach ($activities as $a) {
            $pdf->SetTextColor(...$dark);
            $pdf->Cell(35, 6, date('M d, Y h:i A', strtotime($a['logged_at'])), 0, 0, 'L');
            $pdf->Cell(55, 6, $a['activity_name'], 0, 0, 'L');
            $pdf->Cell(30, 6, $a['duration_minutes'] . ' min', 0, 0, 'L');
            $pdf->Cell(30, 6, $a['intensity'], 0, 0, 'L');
            $pdf->SetTextColor(...$muted);
            $pdf->Cell(30, 6, $a['notes'] ?? '—', 0, 1, 'L');
        }
    }

    // ── FOOTER ─────────────────────────────────────────────
    $pdf->SetY(-20);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(...$muted);
    $pdf->Cell(0, 6, 'Generated by DiabeTrack  |  ' . date('F j, Y \a\t h:i A') . '  |  Confidential Health Document', 0, 0, 'C');

    $filename = 'DiabeTrack_Report_' . str_replace(' ', '_', $patient['name']) . '_' . date('Y-m-d') . '.pdf';
    $pdf->Output($filename, 'D');
}
}