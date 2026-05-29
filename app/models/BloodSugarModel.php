<?php

require_once __DIR__ . '/Model.php';

class BloodSugarModel extends Model {
    private const THRESHOLDS = [
        'Fasting'     => ['low' => 70, 'high' => 130, 'limit_label' => '130 mg/dL (fasting)'],
        'Before Meal' => ['low' => 70, 'high' => 130, 'limit_label' => '130 mg/dL (pre-meal)'],
        'After Meal'  => ['low' => 70, 'high' => 180, 'limit_label' => '180 mg/dL (2hr post-meal)'],
        'Bedtime'     => ['low' => 70, 'high' => 150, 'limit_label' => '150 mg/dL (bedtime)'],
    ];

    private function classify(float $reading, string $type): string {
        $t = self::THRESHOLDS[$type] ?? self::THRESHOLDS['Before Meal'];
        if ($reading < $t['low'])  return 'Low';
        if ($reading > $t['high']) return 'High';
        return 'Normal';
    }

    public function addReading($patient_id, $reading, $reading_type, $notes) {
        $status = $this->classify((float)$reading, $reading_type);

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
            'notes'        => $notes,
        ]);

        // Auto-generate alert if abnormal
        if ($status === 'High' || $status === 'Low') {
            $t       = self::THRESHOLDS[$reading_type] ?? self::THRESHOLDS['Before Meal'];
            $type    = $status === 'High' ? 'High Sugar' : 'Low Sugar';
            $limit   = $status === 'High' ? $t['limit_label'] : '70 mg/dL';
            $message = $status === 'High'
                ? "Blood sugar reading of {$reading} mg/dL ({$reading_type}) is above the target limit of {$limit}."
                : "Blood sugar reading of {$reading} mg/dL ({$reading_type}) is below the safe limit of {$limit}.";

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
