<?php

class AuthController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Register a new user
    public function register($role_id, $user_name, $phone_number, $password, $region) {
        // Check if phone number already exists
        $checkStmt = $this->pdo->prepare("SELECT user_id FROM user WHERE phone_number = ?");
        $checkStmt->execute([$phone_number]);
        if ($checkStmt->fetch()) {
            return false; // Phone number already registered
        }

        // Hash the password for security
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user record
        $stmt = $this->pdo->prepare("INSERT INTO user (role_id, user_name, phone_number, password, region) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            (int)$role_id,
            $user_name,
            $phone_number,
            $hashedPassword,
            $region
        ]);
    }

    // Authenticate existing user by phone number or username
    public function login($identifier, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM user WHERE phone_number = ? OR user_name = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verify password hash (fallback to plain comparison if legacy non-hashed passwords exist)
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                return $user;
            }
        }

        return false;
    }
}