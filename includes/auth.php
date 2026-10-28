<?php
require_once 'config.php';

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // User login
    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        return false;
    }

    // User registration
    public function register($first_name, $last_name, $email, $password, $phone = '', $date_of_birth = '', $address = '') {
        // Check if email already exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            return false; // Email already exists
        }

        // Insert new user
        $stmt = $this->pdo->prepare("INSERT INTO users (first_name, last_name, email, password, phone, date_of_birth, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        return $stmt->execute([$first_name, $last_name, $email, $hashed_password, $phone, $date_of_birth, $address]);
    }

    // Get user by ID
    public function getUser($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all users
    public function getAllUsers() {
        $stmt = $this->pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update user
    public function updateUser($id, $first_name, $last_name, $email, $phone, $date_of_birth, $address, $role) {
        $stmt = $this->pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, date_of_birth = ?, address = ?, role = ? WHERE id = ?");
        return $stmt->execute([$first_name, $last_name, $email, $phone, $date_of_birth, $address, $role, $id]);
    }

    // Delete user
    public function deleteUser($id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Check if email exists
    public function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }

    // Get user by email
    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update user profile
    public function updateProfile($id, $first_name, $last_name, $phone, $date_of_birth, $address) {
        $stmt = $this->pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, date_of_birth = ?, address = ? WHERE id = ?");
        return $stmt->execute([$first_name, $last_name, $phone, $date_of_birth, $address, $id]);
    }
}
?>