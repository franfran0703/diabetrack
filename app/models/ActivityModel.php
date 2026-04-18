<?php

require_once __DIR__ . '/Model.php';

class ActivityModel extends Model {

    // Add activity log
    public function addActivity($patient_id, $data) {
        $stmt = $this->db->prepare("
            INSERT INTO activity_logs
                (patient_id, activity_name, duration_minutes, intensity, notes)
            VALUES
                (:patient_id, :activity_name, :duration_minutes, :intensity, :notes)
        ");
        return $stmt->execute([
            'patient_id'       => $patient_id,
            'activity_name'    => $data['activity_name'],
            'duration_minutes' => $data['duration_minutes'],
            'intensity'        => $data['intensity'],
            'notes'            => $data['notes'] ?: null,
        ]);
    }

    // Get all logs
    public function getLogs($patient_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM activity_logs
            WHERE patient_id = :pid
            ORDER BY logged_at DESC
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetchAll();
    }

    // Get today's logs
    public function getTodayLogs($patient_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM activity_logs
            WHERE patient_id = :pid
              AND DATE(logged_at) = CURDATE()
            ORDER BY logged_at ASC
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetchAll();
    }

    // Get today's totals
    public function getTodayTotals($patient_id) {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(duration_minutes), 0) as total_minutes,
                COUNT(*) as total_activities
            FROM activity_logs
            WHERE patient_id = :pid
              AND DATE(logged_at) = CURDATE()
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetch();
    }

    // Get this week's totals
    public function getWeekTotals($patient_id) {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(duration_minutes), 0) as week_minutes,
                COUNT(*) as week_activities
            FROM activity_logs
            WHERE patient_id = :pid
              AND logged_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetch();
    }

    // Get last 7 days data for chart
    public function getLast7Days($patient_id) {
        $stmt = $this->db->prepare("
            SELECT
                DATE(logged_at) as log_date,
                SUM(duration_minutes) as total_minutes,
                COUNT(*) as activities
            FROM activity_logs
            WHERE patient_id = :pid
              AND logged_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(logged_at)
            ORDER BY log_date ASC
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetchAll();
    }

    // Delete a log
    public function deleteLog($id, $patient_id) {
        $stmt = $this->db->prepare("
            DELETE FROM activity_logs
            WHERE id = :id AND patient_id = :pid
        ");
        return $stmt->execute(['id' => $id, 'pid' => $patient_id]);
    }
}