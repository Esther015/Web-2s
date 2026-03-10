<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=UTF-8');

// SI GET → AFFICHER FORMULAIRE
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  if (!empty($_GET['save'])) {
    echo "<h3>Спасибо, данные успешно сохранены!</h3>";
  }
  include('form.php');
  exit();
}

//VALIDATION

$errors = FALSE;

if (empty($_POST['fio']) ||
    !preg_match("/^[a-zA-Zа-яА-ЯёЁ\s]{1,150}$/u", $_POST['fio'])) {
  echo "Некорректное ФИО.<br>";
  $errors = TRUE;
}

if (empty($_POST['phone'])) {
  echo "Введите телефон.<br>";
  $errors = TRUE;
}

if (empty($_POST['email']) ||
    !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
  echo "Некорректный Email.<br>";
  $errors = TRUE;
}

if (empty($_POST['birthdate'])) {
  echo "Укажите дату рождения.<br>";
  $errors = TRUE;
}

if (empty($_POST['gender']) ||
    !in_array($_POST['gender'], ['male','female'])) {
  echo "Выберите корректный пол.<br>";
  $errors = TRUE;
}

if (empty($_POST['languages']) || !is_array($_POST['languages'])) {
  echo "Выберите минимум один язык.<br>";
  $errors = TRUE;
}

if (empty($_POST['biography'])) {
  echo "Введите биографию.<br>";
  $errors = TRUE;
}

if (empty($_POST['contract'])) {
  echo "Необходимо согласиться с контрактом.<br>";
  $errors = TRUE;
}

if ($errors) {
  exit();
}

// CONNEXION BASE

$user = 'u82384';      // TON LOGIN
$pass = 'd5#RdgdgH';    // TON PASSWORD
$db = new PDO('mysql:host=localhost;dbname=u82384', $user, $pass,
  [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); // Заменить test на имя БД, совпадает с логином uXXXXX

try {
 $stmt = $db->prepare("INSERT INTO application SET name = ?");
  $stmt->execute([$_POST['fio']]);
  );
 /* echo "Base connectee :" .$dbname;
  exit();*/
} catch (PDOException $e) {
  die("Erreur connexion BD : " . $e->getMessage());
}

// INSERTION DANS application

try {

  $stmt = $db->prepare("
    INSERT INTO application
    (name, phone, email, birthdate, gender, biography, contract)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  ");

  $stmt->execute([
    $_POST['fio'],
    $_POST['phone'],
    $_POST['email'],
    $_POST['birthdate'],
    $_POST['gender'],
    $_POST['biography'],
    1
  ]);

  $application_id = $db->lastInsertId();

  //INSERTION LANGAGES 

  $stmt = $db->prepare("
    INSERT INTO application_language
    (application_id, language_id)
    VALUES (?, ?)
  ");

  foreach ($_POST['languages'] as $lang) {
    $stmt->execute([$application_id, $lang]);
  }

} catch (PDOException $e) {
  die("Erreur insertion : " . $e->getMessage());
}

// SUCCES 

header('Location: ?save=1');

echo "INSERT OK";
exit();
