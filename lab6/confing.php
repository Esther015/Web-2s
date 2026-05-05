<?php
/**
 * config.php - Configuration et connexion à la base de données
 * Principe DRY : un seul fichier pour toutes les connexions
 */

$host = 'localhost';
$dbname = 'u82384';
$username = 'u82384';
$password = 'd5#RdgdgH';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
