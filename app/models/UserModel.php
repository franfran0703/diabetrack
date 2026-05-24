<?php

require_once __DIR__ . '/Model.php';

class UserModel extends Model {

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function register($name, $email, $password, $role) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password, role)
            VALUES (:name, :email, :password, :role)
        ");
        $stmt->execute([
            'name'     => $name,
            'email'    => $email,
            'password' => $hashed,
            'role'     => $role,
        ]);

        $newId = $this->db->lastInsertId();

        // Auto-create matching profile row
        if ($role === 'patient') {
            $this->db->prepare("INSERT INTO patient_profiles (user_id) VALUES (:uid)")
                     ->execute(['uid' => $newId]);
        } elseif ($role === 'caregiver') {
            $this->db->prepare("INSERT INTO caregiver_profiles (user_id) VALUES (:uid)")
                     ->execute(['uid' => $newId]);
        }

        return $newId;
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findPatientProfile($userId) {
        $stmt = $this->db->prepare("SELECT * FROM patient_profiles WHERE user_id = :uid LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetch();
    }

    public function findCaregiverProfile($userId) {
        $stmt = $this->db->prepare("SELECT * FROM caregiver_profiles WHERE user_id = :uid LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetch();
    }

    public function updateInfo($id, $name, $email) {
        $stmt = $this->db->prepare("
            UPDATE users SET name = :name, email = :email WHERE id = :id
        ");
        return $stmt->execute(['name' => $name, 'email' => $email, 'id' => $id]);
    }

    public function updatePassword($id, $newPassword) {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        return $stmt->execute(['password' => $hashed, 'id' => $id]);
    }

    public function updatePatientProfile($userId, $data) {
        $stmt = $this->db->prepare("
            UPDATE patient_profiles
            SET date_of_birth             = :dob,
                diabetes_type             = :dtype,
                emergency_contact_name    = :ecname,
                emergency_contact_number  = :ecnum
            WHERE user_id = :uid
        ");
        return $stmt->execute([
            'dob'    => $data['date_of_birth']            ?? null,
            'dtype'  => $data['diabetes_type']            ?? null,
            'ecname' => $data['emergency_contact_name']   ?? null,
            'ecnum'  => $data['emergency_contact_number'] ?? null,
            'uid'    => $userId,
        ]);
    }

    public function updateCaregiverProfile($userId, $data) {
        $stmt = $this->db->prepare("
            UPDATE caregiver_profiles
            SET contact_number = :phone,
                address        = :addr
            WHERE user_id = :uid
        ");
        return $stmt->execute([
            'phone' => $data['contact_number'] ?? null,
            'addr'  => $data['address']        ?? null,
            'uid'   => $userId,
        ]);
    }
}