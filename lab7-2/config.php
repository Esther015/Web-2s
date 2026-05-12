<?php
/**
 * Конфигурация безопасности приложения
 * МОДИФИКАЦИИ БЕЗОПАСНОСТИ :
 * - Добавлено управление ошибками без отображения (Information Disclosure)
 * - Добавлены HTTP-заголовки безопасности
 * - Добавлены функции защиты CSRF
 * - Добавлена функция экранирования HTML (XSS)
 */

// ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #1 : Управление ошибками ==========
// Отключение отображения ошибок (Information Disclosure)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #2 : Безопасная сессия ==========
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #3 : Безопасное подключение БД ==========
$host = 'localhost';
$dbname = 'u82384'; 
$username = 'u82384';
$password = 'd5#RdgdgH';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Отключение эмуляции подготовленных запросов (SQL Injection)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    error_log($e->getMessage());
    die('Ошибка подключения к базе данных'); // Общее сообщение (Information Disclosure)
}

// ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #4 : Защита CSRF ==========
// Функция генерации CSRF-токена
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Функция проверки CSRF-токена (сравнение с постоянным временем)
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #5 : Защита XSS ==========
// Функция экранирования HTML для всего вывода
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #6 : HTTP-заголовки безопасности ==========
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// ========== СУЩЕСТВУЮЩИЕ ФУНКЦИИ (сохранены) ==========
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

function generateUniqueLogin($pdo) {
    do {
        $login = 'user_' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $exists = $stmt->fetch();
    } while ($exists);
    return $login;
}
?>
