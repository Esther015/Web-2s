<html>
<head>

<style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f2f2f2;
    }
    .container {
      width: 500px;
      margin: 40px auto;
      background: #ffffff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    input, select, textarea {
      width: 100%;
      margin-bottom: 10px;
      padding: 6px;
      box-sizing: border-box;
    }
    input[type="radio"],
    input[type="checkbox"] {
      width: auto;
    }
    button {
      background-color: #4CAF50;
      color: white;
      padding: 10px;
      border: none;
      cursor: pointer;
      width: 100%;
    }
    button:hover {
      background-color: #45a049;
    }
    .error {
  border: 2px solid red;
    }
  </style>

  
</head>

<body>

<div class="container">

<h2>Анкета</h2>

<?php
if (!empty($messages)) {
  print('<div style="
    background:#d4edda;
    padding:15px;
    border-radius:6px;
    margin-bottom:15px;
    color:#155724;
  ">');
  
  foreach ($messages as $message) {
    print($message);
  }
  
  print('</div>');
}

/*if (!empty($messages)) {
  print('<div id="messages">');
  foreach ($messages as $message) {
    print($message);
  }
  print('</div>');
}*/
?>

<form action="" method="POST">

<!-- NAME -->
<label>ФИО:</label>
<input name="name"
  <?php if ($errors['name']) print 'class="error"'; ?>
  value="<?php print $values['name']; ?>" />

<!-- PHONE -->
<label>Телефон:</label>
<input name="phone"
  <?php if ($errors['phone']) print 'class="error"'; ?>
  value="<?php print $values['phone']; ?>" />

<!-- EMAIL -->
<label>Email:</label>
<input name="email"
  <?php if ($errors['email']) print 'class="error"'; ?>
  value="<?php print $values['email']; ?>" />

<!-- DATE -->
<label>Дата рождения:</label>
<input type="date" name="birthdate"
  <?php if ($errors['birthdate']) print 'class="error"'; ?>
  value="<?php print $values['birthdate']; ?>" />

<!-- GENDER -->
<label>Пол:</label><br>
<input type="radio" name="gender" value="male"
  <?php if ($values['gender']=='male') print 'checked'; ?>> Мужской

<input type="radio" name="gender" value="female"
  <?php if ($values['gender']=='female') print 'checked'; ?>> Женский

<?php if ($errors['gender']) print '<div class="error-message">Ошибка выбора пола</div>'; ?>

<br><br>

<!-- LANGUAGES -->
<label>Любимый язык:</label>

<?php
$selected = [];
if (!empty($_COOKIE['languages'])) {
  $selected = json_decode($_COOKIE['languages'], true);
}
?>

<select name="languages[]" multiple size="3"
  <?php if ($errors['languages']) print 'class="error"'; ?>>

<option value="1" <?php if (in_array("1",$selected)) print 'selected'; ?>>Pascal</option>
<option value="2" <?php if (in_array("2",$selected)) print 'selected'; ?>>C</option>
<option value="3" <?php if (in_array("3",$selected)) print 'selected'; ?>>C++</option>
<option value="4" <?php if (in_array("4",$selected)) print 'selected'; ?>>JavaScript</option>
<option value="5" <?php if (in_array("5",$selected)) print 'selected'; ?>>PHP</option>
<option value="6" <?php if (in_array("6",$selected)) print 'selected'; ?>>Java</option>
<option value="7" <?php if (in_array("7",$selected)) print 'selected'; ?>>Python</option>

</select>

<?php if ($errors['languages']) print '<div class="error-message">Выберите язык</div>'; ?>

<!-- BIO -->
<label>Биография:</label>
<textarea name="biography"
  <?php if ($errors['biography']) print 'class="error"'; ?>><?php print $values['biography']; ?></textarea>

<!-- CONTRACT -->
<input type="checkbox" name="contract" value="1"
  <?php if ($values['contract']) print 'checked'; ?>>
С контрактом ознакомлен

<?php if ($errors['contract']) print '<div class="error-message">Подтвердите</div>'; ?>

<br><br>

<button type="submit">Сохранить</button>

    
</form>

</div>

</body>
</html>
