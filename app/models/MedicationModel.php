<?php

require_once __DIR__ . '/Model.php';

class MedicationModel extends Model {

    // ── MEDICATIONS (schedule) ─────────────────────────

    public function getMedications($patient_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM medications
            WHERE patient_id = :pid
            ORDER BY schedule_time ASC
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetchAll();
    }

    public function addMedication($patient_id, $name, $dosage, $schedule_time, $frequency) {
        $stmt = $this->db->prepare("
            INSERT INTO medications (patient_id, name, dosage, schedule_time, frequency)
            VALUES (:pid, :name, :dosage, :schedule_time, :frequency)
        ");
        return $stmt->execute([
            'pid'           => $patient_id,
            'name'          => $name,
            'dosage'        => $dosage,
            'schedule_time' => $schedule_time,
            'frequency'     => $frequency
        ]);
    }

    public function getMedicationById($id, $patient_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM medications
            WHERE id = :id AND patient_id = :pid
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'pid' => $patient_id]);
        return $stmt->fetch();
    }

    public function updateMedication($id, $patient_id, $name, $dosage, $schedule_time, $frequency) {
        $stmt = $this->db->prepare("
            UPDATE medications
            SET name = :name, dosage = :dosage,
                schedule_time = :schedule_time, frequency = :frequency
            WHERE id = :id AND patient_id = :pid
        ");
        return $stmt->execute([
            'id'            => $id,
            'pid'           => $patient_id,
            'name'          => $name,
            'dosage'        => $dosage,
            'schedule_time' => $schedule_time,
            'frequency'     => $frequency
        ]);
    }

    public function deleteMedication($id, $patient_id) {
        $stmt = $this->db->prepare("
            DELETE FROM medications
            WHERE id = :id AND patient_id = :pid
        ");
        return $stmt->execute(['id' => $id, 'pid' => $patient_id]);
    }

    // ── MEDICATION LOGS ────────────────────────────────

    public function logDose($medication_id, $patient_id, $status) {
        $stmt = $this->db->prepare("
            INSERT INTO medication_logs (medication_id, patient_id, status)
            VALUES (:mid, :pid, :status)
        ");
        return $stmt->execute([
            'mid'    => $medication_id,
            'pid'    => $patient_id,
            'status' => $status
        ]);
    }

    public function getTodayLogs($patient_id) {
        $stmt = $this->db->prepare("
            SELECT ml.*, m.name, m.dosage, m.schedule_time
            FROM medication_logs ml
            JOIN medications m ON m.id = ml.medication_id
            WHERE ml.patient_id = :pid
              AND DATE(ml.logged_at) = CURDATE()
            ORDER BY ml.logged_at DESC
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetchAll();
    }

    public function getAllLogs($patient_id) {
        $stmt = $this->db->prepare("
            SELECT ml.*, m.name, m.dosage, m.schedule_time
            FROM medication_logs ml
            JOIN medications m ON m.id = ml.medication_id
            WHERE ml.patient_id = :pid
            ORDER BY ml.logged_at DESC
            LIMIT 50
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetchAll();
    }

    public function getTodayStats($patient_id) {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total,
                SUM(status = 'Taken')  as taken,
                SUM(status = 'Missed') as missed
            FROM medication_logs
            WHERE patient_id = :pid
              AND DATE(logged_at) = CURDATE()
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetch();
    }

    // Check if a dose was already logged today for a medication
    public function alreadyLoggedToday($medication_id, $patient_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM medication_logs
            WHERE medication_id = :mid
              AND patient_id    = :pid
              AND DATE(logged_at) = CURDATE()
        ");
        $stmt->execute(['mid' => $medication_id, 'pid' => $patient_id]);
        return $stmt->fetchColumn() > 0;
    }
}