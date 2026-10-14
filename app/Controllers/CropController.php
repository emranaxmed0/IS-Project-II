<?php

class CropController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Fetch all crops using crop_id
    public function getAllCrops() {
        $stmt = $this->pdo->query("SELECT * FROM crop ORDER BY crop_id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add a new crop matching crop_name, description, growing_season, region
    public function addCrop($name, $description, $season, $region) {
        $stmt = $this->pdo->prepare("INSERT INTO crop (crop_name, description, growing_season, region) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$name, $description, $season, $region]);
    }

    // Update an existing crop
    public function updateCrop($id, $name, $description, $season, $region) {
        $stmt = $this->pdo->prepare("UPDATE crop SET crop_name = ?, description = ?, growing_season = ?, region = ? WHERE crop_id = ?");
        return $stmt->execute([$name, $description, $season, $region, $id]);
    }

    // Delete a crop by crop_id
    public function deleteCrop($id) {
        $stmt = $this->pdo->prepare("DELETE FROM crop WHERE crop_id = ?");
        return $stmt->execute([$id]);
    }

    // Fetch all registered users
    public function getAllUsers() {
        $stmt = $this->pdo->query("SELECT * FROM user ORDER BY user_id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}