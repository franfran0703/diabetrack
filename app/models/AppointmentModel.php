<?php

require_once __DIR__ . '/Model.php';

class AppointmentModel extends Model {

    // Add appointment
    public function addAppointment($patient_id, $data) {
        $stmt = $this->db->prepare("
            INSERT INTO appointments
                (patient_id, doctor_name, appointment_date, status, notes)
            VALUES
                (:patient_id, :doctor_name, :appointment_date, :status, :notes)
        ");
        return $stmt->execute([
            'patient_id'       => $patient_id,
            'doctor_name'      => $data['doctor_name'],
            'appointment_date' => $data['appointment_date'],
            'status'           => 'Upcoming',
            'notes'            => $data['notes'] ?: null,
        ]);
    }

    // Get all appointments
    public function getAll($patient_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM appointments
            WHERE patient_id = :pid
            ORDER BY appointment_date ASC
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetchAll();
    }

    // Get upcoming appointments
    public function getUpcoming($patient_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM appointments
            WHERE patient_id = :pid
              AND status = 'Upcoming'
              AND appointment_date >= NOW()
            ORDER BY appointment_date ASC
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetchAll();
    }

    // Get next appointment
    public function getNext($patient_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM appointments
            WHERE patient_id = :pid
              AND status = 'Upcoming'
              AND appointment_date >= NOW()
            ORDER BY appointment_date ASC
            LIMIT 1
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetch();
    }

    // Update status
    public function updateStatus($id, $patient_id, $status) {
        $stmt = $this->db->prepare("
            UPDATE appointments
            SET status = :status
            WHERE id = :id AND patient_id = :pid
        ");
        return $stmt->execute([
            'status' => $status,
            'id'     => $id,
            'pid'    => $patient_id,
        ]);
    }

    // Delete appointment
    public function delete($id, $patient_id) {
        $stmt = $this->db->prepare("
            DELETE FROM appointments
            WHERE id = :id AND patient_id = :pid
        ");
        return $stmt->execute(['id' => $id, 'pid' => $patient_id]);
    }

    // Count by status
    public function countByStatus($patient_id, $status) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM appointments
            WHERE patient_id = :pid AND status = :status
        ");
        $stmt->execute(['pid' => $patient_id, 'status' => $status]);
        return $stmt->fetchColumn();
    }
}