<?php

require_once __DIR__ . '/Model.php';

class BloodSugarModel extends Model {

    // Add a new reading
    public function addReading($patient_id, $reading, $reading_type, $notes) {
        // Auto-determine status
        if ($reading < 70) {
            $status = 'Low';
        } elseif ($reading <= 180) {
            $status = 'Normal';
        } else {
            $status = 'High';
        }

        $stmt = $this->db->prepare("
            INSERT INTO blood_sugar_logs 
                (patient_id, reading, reading_type, status, notes)
            VALUES 
                (:patient_id, :reading, :reading_type, :status, :notes)
        ");

        return $stmt->execute([
            'patient_id'   => $patient_id,
            'reading'      => $reading,
            'reading_type' => $reading_type,
            'status'       => $status,
            'notes'        => $notes
        ]);
    }

    // Get all logs for a patient (latest first)
    public function getLogs($patient_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM blood_sugar_logs
            WHERE patient_id = :patient_id
            ORDER BY logged_at DESC
        ");
        $stmt->execute(['patient_id' => $patient_id]);
        return $stmt->fetchAll();
    }

    // Get latest reading
    public function getLatest($patient_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM blood_sugar_logs
            WHERE patient_id = :patient_id
            ORDER BY logged_at DESC
            LIMIT 1
        ");
        $stmt->execute(['patient_id' => $patient_id]);
        return $stmt->fetch();
    }

    // Get last 7 readings for chart
    public function getLast7($patient_id) {
        $stmt = $this->db->prepare("
            SELECT reading, reading_type, status, logged_at
            FROM blood_sugar_logs
            WHERE patient_id = :patient_id
            ORDER BY logged_at DESC
            LIMIT 7
        ");
        $stmt->execute(['patient_id' => $patient_id]);
        return array_reverse($stmt->fetchAll());
    }

    // Delete a log
    public function deleteLog($id, $patient_id) {
        $stmt = $this->db->prepare("
            DELETE FROM blood_sugar_logs
            WHERE id = :id AND patient_id = :patient_id
        ");
        return $stmt->execute(['id' => $id, 'patient_id' => $patient_id]);
    }
}