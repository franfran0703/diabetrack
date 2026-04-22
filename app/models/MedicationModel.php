<?php

require_once __DIR__ . '/Model.php';

class MedicationModel extends Model {

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

    public function logDose($medication_id, $patient_id, $status) {
        $stmt = $this->db->prepare("
            INSERT INTO medication_logs (medication_id, patient_id, status)
            VALUES (:mid, :pid, :status)
        ");
        $result = $stmt->execute([
            'mid'    => $medication_id,
            'pid'    => $patient_id,
            'status' => $status
        ]);

        // Auto-generate alert if missed
        if ($status === 'Missed') {
            $med = $this->db->prepare("SELECT name FROM medications WHERE id = :mid");
            $med->execute(['mid' => $medication_id]);
            $medName = $med->fetchColumn();

            $message = "Medication '{$medName}' was not taken as scheduled.";

            // Alert for patient
            $this->db->prepare("
                INSERT INTO alerts (user_id, patient_id, type, message)
                VALUES (:uid, :pid, 'Missed Dose', :message)
            ")->execute([
                'uid'     => $patient_id,
                'pid'     => $patient_id,
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
                    VALUES (:uid, :pid, 'Missed Dose', :message)
                ")->execute([
                    'uid'     => $caregiver['caregiver_id'],
                    'pid'     => $patient_id,
                    'message' => $message,
                ]);
            }
        }

        return $result;
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