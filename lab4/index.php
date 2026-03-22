<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=UTF-8');

/* GET → AFFICHER FORM */
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  include('form.php');
  exit();
}

/* VALIDATION */ 
$errors = [];

if (!preg_match("/^[a-zA-Zа-яА-Я\s]+$/u", $_POST['name'])) {
  $errors['name'] = "Допустимы только буквы и пробелы";
}

if (!preg_match("/^\+?[0-9\s\-]+$/", $_POST['phone'])) {
  $errors['phone'] = "Неверный формат телефона";
}

if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
  $errors['email'] = "Неверный email";
}

if (empty($_POST['birthdate'])) {
  $errors['birthdate'] = "Укажите дату рождения";
}

if (empty($_POST['gender'])) {
  $errors['gender'] = "Выберите пол";
}

if (empty($_POST['languages'])) {
  $errors['languages'] = "Выберите язык";
}

if (empty($_POST['biography'])) {
  $errors['biography'] = "Заполните биографию";
}

if (empty($_POST['contract'])) {
  $errors['contract'] = "Необходимо согласие";
}

/*SI ERREURS */

if (!empty($errors)) {

  foreach ($errors as $field => $message) {
    setcookie("error_$field", $message, 0);
  }

  foreach ($_POST as $key => $value) {
    if (is_array($value)) {
      setcookie($key, json_encode($value), 0);
    } else {
      setcookie($key, $value, 0);
    }
  }

  header("Location: index.php");
  exit();
}

/* SUCCÈS → COOKIES 1 AN  */

foreach ($_POST as $key => $value) {
  if (is_array($value)) {
    setcookie($key, json_encode($value), time() + 365*24*60*60);
  } else {
    setcookie($key, $value, time() + 365*24*60*60);
  }
}

/*  BASE DE DONNÉES  */

$user = 'u82384';
$pass = 'd5#RdgdgH';
$dbname = 'u82384';

try {
  $db = new PDO(
    "mysql:host=localhost;dbname=$dbname;charset=utf8",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (PDOException $e) {
  die("Erreur connexion BD : " . $e->getMessage());
}

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

  $application_id = $db->lastInsertId();

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
