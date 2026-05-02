<?php

require_once __DIR__ . '/Model.php';

class UserModel extends Model {

    // Find user by email
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    // Create new user
    public function register($name, $email, $password, $role) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password, role) 
            VALUES (:name, :email, :password, :role)
        ");
        return $stmt->execute([
            'name'     => $name,
            'email'    => $email,
            'password' => $hashed,
            'role'     => $role
        ]);
    }

    // Find user by ID
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // Update name and email
    public function updateInfo($id, $name, $email) {
        $stmt = $this->db->prepare("
            UPDATE users SET name = :name, email = :email WHERE id = :id
        ");
        return $stmt->execute(['name' => $name, 'email' => $email, 'id' => $id]);
    }

    // Update password (hashed)
    public function updatePassword($id, $newPassword) {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        return $stmt->execute(['password' => $hashed, 'id' => $id]);
    }
}