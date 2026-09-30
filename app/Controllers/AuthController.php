<?php
require_once dirname(__DIR__) . '/../config/db.php';

class AuthController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo =$pdo;
    }

    /**
     * Register a new user with secure password hashing
     */
    public function register($role_id, $user_name,$phone_number, $password,$region) {
        try {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);$sql = "INSERT INTO `User` (role_id, user_name, phone_number, password, region) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$role_id, $user_name,$phone_number, $hashed_password,$region]);
        } catch (PDOException $e) {
            // Handle duplicate phone numbers or foreign key errors
            return false;
        }
    }

    /**
     * Authenticate user by phone number and password
     */
    public function login($phone_number,$password) {
        $stmt =$this->pdo->prepare("SELECT * FROM `User` WHERE phone_number = ?");
        $stmt->execute([$phone_number]);
        $user =$stmt->fetch();

        if ($user && password_verify($password,$user['password'])) {
            // Remove sensitive password hash from return array
            unset($user['password']);
            return $user;
        }
        return false;
    }
}