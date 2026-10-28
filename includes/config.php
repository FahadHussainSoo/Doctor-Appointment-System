<?php
session_start();


define('DB_HOST', 'sql106.infinityfree.com');
define('DB_NAME', 'if0_42626534_doctor');
define('DB_USER', 'if0_42626534');
define('DB_PASS', 'M4fme1Z7c2PJR');
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}


// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isDoctor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'doctor';
}

function isPatient() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'patient';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: ../index.php");
        exit();
    }
}

function requireDoctor() {
    if (!isDoctor()) {
        header("Location: ../index.php");
        exit();
    }
}

function requirePatient() {
    if (!isPatient()) {
        header("Location: ../index.php");
        exit();
    }
}
?>