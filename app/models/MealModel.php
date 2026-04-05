<?php

require_once __DIR__ . '/Model.php';

class MealModel extends Model {

    // Add a new meal log
    public function addMeal($patient_id, $data) {
        $stmt = $this->db->prepare("
            INSERT INTO meal_logs (
                patient_id, meal_name, meal_type,
                carbs, calories, sugar, fiber,
                protein, fat, sodium, glycemic_index, notes
            ) VALUES (
                :patient_id, :meal_name, :meal_type,
                :carbs, :calories, :sugar, :fiber,
                :protein, :fat, :sodium, :glycemic_index, :notes
            )
        ");
        return $stmt->execute([
            'patient_id'     => $patient_id,
            'meal_name'      => $data['meal_name'],
            'meal_type'      => $data['meal_type'],
            'carbs'          => $data['carbs'],
            'calories'       => $data['calories'] ?: null,
            'sugar'          => $data['sugar'] ?: null,
            'fiber'          => $data['fiber'] ?: null,
            'protein'        => $data['protein'] ?: null,
            'fat'            => $data['fat'] ?: null,
            'sodium'         => $data['sodium'] ?: null,
            'glycemic_index' => $data['glycemic_index'] ?: null,
            'notes'          => $data['notes'] ?: null,
        ]);
    }

    // Get all meal logs
    public function getLogs($patient_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM meal_logs
            WHERE patient_id = :pid
            ORDER BY logged_at DESC
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetchAll();
    }

    // Get today's logs
    public function getTodayLogs($patient_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM meal_logs
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
                COALESCE(SUM(carbs), 0)    as total_carbs,
                COALESCE(SUM(calories), 0) as total_calories,
                COALESCE(SUM(sugar), 0)    as total_sugar,
                COALESCE(SUM(protein), 0)  as total_protein,
                COALESCE(SUM(fat), 0)      as total_fat,
                COUNT(*) as total_meals
            FROM meal_logs
            WHERE patient_id = :pid
              AND DATE(logged_at) = CURDATE()
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetch();
    }

    // Delete a meal log
    public function deleteLog($id, $patient_id) {
        $stmt = $this->db->prepare("
            DELETE FROM meal_logs
            WHERE id = :id AND patient_id = :pid
        ");
        return $stmt->execute(['id' => $id, 'pid' => $patient_id]);
    }

    // Get latest meal
    public function getLatest($patient_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM meal_logs
            WHERE patient_id = :pid
            ORDER BY logged_at DESC
            LIMIT 1
        ");
        $stmt->execute(['pid' => $patient_id]);
        return $stmt->fetch();
    }
}