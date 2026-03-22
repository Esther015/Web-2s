<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Анкета</title>

<style>
body {
  font-family: Arial;
  background: #f2f2f2;
}
.container {
  width: 500px;
  margin: 40px auto;
  background: white;
  padding: 20px;
  border-radius: 8px;
}
input, select, textarea {
  width: 100%;
  margin-bottom: 10px;
  padding: 6px;
}
.error {
  border: 2px solid red;
}
.error-message {
  color: red;
}
</style>

</head>
<body>

<div class="container">
<h2>Анкета</h2>

<form method="POST" action="index.php">

<!-- NAME -->
<label>ФИО:</label>
<input type="text" name="name"
 value="<?= $_COOKIE['name'] ?? '' ?>"
 class="<?= isset($_COOKIE['error_name']) ? 'error' : '' ?>">
<?php if (!empty($_COOKIE['error_name'])) {
  echo "<div class='error-message'>{$_COOKIE['error_name']}</div>";
  setcookie('error_name','',time()-3600);
} ?>

<!-- PHONE -->
<label>Телефон:</label>
<input type="text" name="phone"
 value="<?= $_COOKIE['phone'] ?? '' ?>"
 class="<?= isset($_COOKIE['error_phone']) ? 'error' : '' ?>">
<?php if (!empty($_COOKIE['error_phone'])) {
  echo "<div class='error-message'>{$_COOKIE['error_phone']}</div>";
  setcookie('error_phone','',time()-3600);
} ?>

<!-- EMAIL -->
<label>Email:</label>
<input type="text" name="email"
 value="<?= $_COOKIE['email'] ?? '' ?>"
 class="<?= isset($_COOKIE['error_email']) ? 'error' : '' ?>">
<?php if (!empty($_COOKIE['error_email'])) {
  echo "<div class='error-message'>{$_COOKIE['error_email']}</div>";
  setcookie('error_email','',time()-3600);
} ?>

<!-- DATE -->
<label>Дата рождения:</label>
<input type="date" name="birthdate"
 value="<?= $_COOKIE['birthdate'] ?? '' ?>"
 class="<?= isset($_COOKIE['error_birthdate']) ? 'error' : '' ?>">

<!-- GENDER -->
<label>Пол:</label><br>
<input type="radio" name="gender" value="male"
 <?= (($_COOKIE['gender'] ?? '')=='male')?'checked':'' ?>> Мужской
<input type="radio" name="gender" value="female"
 <?= (($_COOKIE['gender'] ?? '')=='female')?'checked':'' ?>> Женский

<?php if (!empty($_COOKIE['error_gender'])) {
  echo "<div class='error-message'>{$_COOKIE['error_gender']}</div>";
  setcookie('error_gender','',time()-3600);
} ?>

<br><br>

<!-- LANGUAGES -->
<?php
$selected = [];
if (!empty($_COOKIE['languages'])) {
  $selected = json_decode($_COOKIE['languages'], true);
}
?>

<label>Любимый язык:</label>
<select name="languages[]" multiple size="3"
 class="<?= isset($_COOKIE['error_languages']) ? 'error' : '' ?>">
  <option value="1" <?= in_array("1",$selected)?"selected":"" ?>>Pascal</option>
  <option value="2" <?= in_array("2",$selected)?"selected":"" ?>>C</option>
  <option value="3" <?= in_array("3",$selected)?"selected":"" ?>>C++</option>
  <option value="4" <?= in_array("4",$selected)?"selected":"" ?>>JavaScript</option>
  <option value="5" <?= in_array("5",$selected)?"selected":"" ?>>PHP</option>
  <option value="6" <?= in_array("6",$selected)?"selected":"" ?>>Java</option>
  <option value="7" <?= in_array("7",$selected)?"selected":"" ?>>Python</option>
</select>

<?php if (!empty($_COOKIE['error_languages'])) {
  echo "<div class='error-message'>{$_COOKIE['error_languages']}</div>";
  setcookie('error_languages','',time()-3600);
} ?>

<!-- BIO -->
<label>Биография:</label>
<textarea name="biography"
 class="<?= isset($_COOKIE['error_biography']) ? 'error' : '' ?>"><?= $_COOKIE['biography'] ?? '' ?></textarea>

<?php if (!empty($_COOKIE['error_biography'])) {
  echo "<div class='error-message'>{$_COOKIE['error_biography']}</div>";
  setcookie('error_biography','',time()-3600);
} ?>

<!-- CONTRACT -->
<input type="checkbox" name="contract" value="1"
 <?= isset($_COOKIE['contract']) ? 'checked' : '' ?>>
С контрактом ознакомлен

<?php if (!empty($_COOKIE['error_contract'])) {
  echo "<div class='error-message'>{$_COOKIE['error_contract']}</div>";
  setcookie('error_contract','',time()-3600);
} ?>

<br><br>

<button type="submit">Сохранить</button>

</form>
</div>

</body>
</html>
