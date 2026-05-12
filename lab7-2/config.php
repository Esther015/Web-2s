<?php
/**
 * Configuration sécurisée de l'application
 * MODIFICATIONS DE SÉCURITÉ :
 * - Ajout de la gestion des erreurs sans affichage
 * - Ajout des en-têtes de sécurité HTTP
 * - Ajout des fonctions de protection CSRF
 * - Ajout de la fonction d'échappement HTML
 */

// ========== MODIF SÉCURITÉ #1 : Gestion des erreurs ==========
// Désactiver l'affichage des erreurs (Information Disclosure)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// ========== MODIF SÉCURITÉ #2 : Session sécurisée ==========
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========== MODIF SÉCURITÉ #3 : Connexion BDD sécurisée ==========
$host = 'localhost';
$dbname = 'u82384';  // À modifier selon votre configuration
$username = 'u82384'; // À modifier selon votre configuration
$password = 'd5#RdgdgH'; // À modifier selon votre configuration

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Désactiver les requêtes préparées émulées (SQL Injection)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    error_log($e->getMessage());
    die('Erreur de connexion à la base de données'); // Message générique (Information Disclosure)
}

// ========== MODIF SÉCURITÉ #4 : Protection CSRF ==========
// Fonction pour générer un token CSRF unique
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour vérifier le token CSRF (comparaison temps constant)
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ========== MODIF SÉCURITÉ #5 : Protection XSS ==========
// Fonction d'échappement HTML pour tous les affichages
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// ========== MODIF SÉCURITÉ #6 : En-têtes HTTP sécurité ==========
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// ========== FONCTIONS EXISTANTES CONSERVÉES ==========
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
