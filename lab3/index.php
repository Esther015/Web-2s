<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=UTF-8');

/* SI GET → AFFICHER FORMULAIRE */
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  include('form.php');
  exit();
}

/* DEBUG : VOIR LES DONNÉES DU FORMULAIRE */
echo "<pre>";
print_r($_POST);
echo "</pre>";
exit();

/* CONNEXION À LA BASE */

$user = 'u82384';
$pass = 'd5#RdgdgH';
$dbname = 'u82384';

try {
  $db = new PDO(
    "mysql:host=localhost;dbname=$dbname;charset=utf8",
    $user,
    $pass,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_PERSISTENT => true
    ]
  );
} catch (PDOException $e) {
  die("Erreur connexion BD : " . $e->getMessage());
}

/* INSERTION DANS application */

try {

  $stmt = $db->prepare("
    INSERT INTO application
    (name, phone, email, birthdate, gender, biography, contract)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  ");

  $stmt->execute([
    $_POST['name'],
    $_POST['phone'],
    $_POST['email'],
    $_POST['birthdate'],
    $_POST['gender'],
    $_POST['biography'],
    1
  ]);
}
  $application_id = $db->lastInsertId();

/* INSERTION DES LANGAGES */

  $stmt = $db->prepare("
    INSERT INTO application_language
    (application_id, language_id)
    VALUES (?, ?)
  ");

  foreach ($_POST['languages'] as $lang) {
    $stmt->execute([$application_id, $lang]);
  }

  echo "<h3>Данные успешно сохранены!</h3>";

} catch (PDOException $e) {
  die("Erreur insertion : " . $e->getMessage());
}

?>
