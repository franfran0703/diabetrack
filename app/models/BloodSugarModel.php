<?php

require_once __DIR__ . '/Model.php';

class BloodSugarModel extends Model {

    public function addReading($patient_id, $reading, $reading_type, $notes) {
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
        $result = $stmt->execute([
            'patient_id'   => $patient_id,
            'reading'      => $reading,
            'reading_type' => $reading_type,
            'status'       => $status,
            'notes'        => $notes
        ]);

        // Auto-generate alert if abnormal
        if ($status === 'High' || $status === 'Low') {
            $type    = $status === 'High' ? 'High Sugar' : 'Low Sugar';
            $message = $status === 'High'
                ? "Blood sugar reading of {$reading} mg/dL is above the safe limit (180 mg/dL)."
                : "Blood sugar reading of {$reading} mg/dL is below the safe limit (70 mg/dL).";

            // Alert for patient
            $this->db->prepare("
                INSERT INTO alerts (user_id, patient_id, type, message)
                VALUES (:uid, :pid, :type, :message)
            ")->execute([
                'uid'     => $patient_id,
                'pid'     => $patient_id,
                'type'    => $type,
                'message' => $message,
            ]);

            // Alert for linked caregivers
            $cg = $this->db->prepare("
                SELECT caregiver_id FROM caregiver_links
                WHERE patient_id = :pid
            ");
            $cg->execute(['pid' => $patient_id]);
            foreach ($cg->fetchAll() as $caregiver) {
                $this->db->prepare("
                    INSERT INTO alerts (user_id, patient_id, type, message)
                    VALUES (:uid, :pid, :type, :message)
                ")->execute([
                    'uid'     => $caregiver['caregiver_id'],
                    'pid'     => $patient_id,
                    'type'    => $type,
                    'message' => $message,
                ]);
            }
        }

        return $result;
    }

    public function getLogs($patient_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM blood_sugar_logs
            WHERE patient_id = :patient_id
            ORDER BY logged_at DESC
        ");
        $stmt->execute(['patient_id' => $patient_id]);
        return $stmt->fetchAll();
    }

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

    public function deleteLog($id, $patient_id) {
        $stmt = $this->db->prepare("
            DELETE FROM blood_sugar_logs
            WHERE id = :id AND patient_id = :patient_id
        ");
        return $stmt->execute(['id' => $id, 'patient_id' => $patient_id]);
    }
}