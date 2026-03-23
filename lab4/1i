<?php
header('Content-Type: text/html; charset=UTF-8');

/* ================= GET ================= */
if ($_SERVER['REQUEST_METHOD'] == 'GET') {

  $messages = array();

  if (!empty($_COOKIE['save'])) {
    setcookie('save', '', 100000);
    $messages[] = 'Спасибо, результаты сохранены.';
  }

  $errors = array();
  $errors['name'] = !empty($_COOKIE['name_error']);
  $errors['phone'] = !empty($_COOKIE['phone_error']);
  $errors['email'] = !empty($_COOKIE['email_error']);
  $errors['birthdate'] = !empty($_COOKIE['birthdate_error']);
  $errors['gender'] = !empty($_COOKIE['gender_error']);
  $errors['languages'] = !empty($_COOKIE['languages_error']);
  $errors['biography'] = !empty($_COOKIE['biography_error']);
  $errors['contract'] = !empty($_COOKIE['contract_error']);

  /* MESSAGES */
  if ($errors['name']) {
    setcookie('name_error', '', 100000);
    $messages[] = '<div class="error">Введите корректное ФИО</div>';
  }

  if ($errors['phone']) {
    setcookie('phone_error', '', 100000);
    $messages[] = '<div class="error">Телефон неверный</div>';
  }

  if ($errors['email']) {
    setcookie('email_error', '', 100000);
    $messages[] = '<div class="error">Email неверный</div>';
  }

  if ($errors['birthdate']) {
    setcookie('birthdate_error', '', 100000);
    $messages[] = '<div class="error">Укажите дату</div>';
  }

  if ($errors['gender']) {
    setcookie('gender_error', '', 100000);
    $messages[] = '<div class="error">Выберите пол</div>';
  }

  if ($errors['languages']) {
    setcookie('languages_error', '', 100000);
    $messages[] = '<div class="error">Выберите язык</div>';
  }

  if ($errors['biography']) {
    setcookie('biography_error', '', 100000);
    $messages[] = '<div class="error">Заполните биографию</div>';
  }

  if ($errors['contract']) {
    setcookie('contract_error', '', 100000);
    $messages[] = '<div class="error">Подтвердите контракт</div>';
  }

  /* VALUES */
  $values = array();
  $values['name'] = $_COOKIE['name_value'] ?? '';
  $values['phone'] = $_COOKIE['phone_value'] ?? '';
  $values['email'] = $_COOKIE['email_value'] ?? '';
  $values['birthdate'] = $_COOKIE['birthdate_value'] ?? '';
  $values['gender'] = $_COOKIE['gender_value'] ?? '';
  $values['biography'] = $_COOKIE['biography_value'] ?? '';
  $values['contract'] = $_COOKIE['contract_value'] ?? '';

  include('form.php');
  exit();
}

/* ================= POST ================= */

$errors = FALSE;

/* VALIDATION */

if (!preg_match("/^[a-zA-Zа-яА-Я\s]+$/u", $_POST['name'])) {
  setcookie('name_error', '1', time()+86400);
  $errors = TRUE;
}
setcookie('name_value', $_POST['name'], time()+30*24*60*60);


if (!preg_match("/^\+?[0-9\s\-]+$/", $_POST['phone'])) {
  setcookie('phone_error', '1', time()+86400);
  $errors = TRUE;
}
setcookie('phone_value', $_POST['phone'], time()+30*24*60*60);


if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
  setcookie('email_error', '1', time()+86400);
  $errors = TRUE;
}
setcookie('email_value', $_POST['email'], time()+30*24*60*60);


if (empty($_POST['birthdate'])) {
  setcookie('birthdate_error', '1', time()+86400);
  $errors = TRUE;
}
setcookie('birthdate_value', $_POST['birthdate'], time()+30*24*60*60);


if (empty($_POST['gender'])) {
  setcookie('gender_error', '1', time()+86400);
  $errors = TRUE;
}
setcookie('gender_value', $_POST['gender'], time()+30*24*60*60);


if (empty($_POST['languages'])) {
  setcookie('languages_error', '1', time()+86400);
  $errors = TRUE;
}


if (empty($_POST['biography'])) {
  setcookie('biography_error', '1', time()+86400);
  $errors = TRUE;
}
setcookie('biography_value', $_POST['biography'], time()+30*24*60*60);


if (empty($_POST['contract'])) {
  setcookie('contract_error', '1', time()+86400);
  $errors = TRUE;
}
setcookie('contract_value', '1', time()+30*24*60*60);


/* SI ERREURS */
if ($errors) {
  header('Location: index.php');
  exit();
}

/* SUPPRIMER ERREURS */
setcookie('name_error', '', 100000);
setcookie('phone_error', '', 100000);
setcookie('email_error', '', 100000);
setcookie('birthdate_error', '', 100000);
setcookie('gender_error', '', 100000);
setcookie('languages_error', '', 100000);
setcookie('biography_error', '', 100000);
setcookie('contract_error', '', 100000);

/* ================= BDD ================= */

$user = 'u82384';
$pass = 'd5#RdgdgH';
$dbname = 'u82384';

try {
  $db = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8", $user, $pass);
} catch (PDOException $e) {
  die($e->getMessage());
}

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

$app_id = $db->lastInsertId();

$stmt = $db->prepare("
INSERT INTO application_language (application_id, language_id)
VALUES (?, ?)
");

foreach ($_POST['languages'] as $lang) {
  $stmt->execute([$app_id, $lang]);
}

/* SUCCÈS */
setcookie('save', '1');

header('Location: index.php');
?>
