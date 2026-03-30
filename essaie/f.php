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
        margin-right: 5px;
      }
      button, input[type="submit"] {
        background-color: #4CAF50;
        color: white;
        padding: 10px;
        border: none;
        cursor: pointer;
        width: 100%;
      }
      button:hover, input[type="submit"]:hover {
        background-color: #45a049;
      }
      .error {
        border: 2px solid red !important;
      }
      .radio-group, .checkbox-group {
        margin-bottom: 10px;
      }
      .radio-group label, .checkbox-group label {
        display: inline-block;
        margin-right: 15px;
      }
    </style>
  </head>
  <body>
    <div class="container">
<?php
if (!empty($messages)) {
  print('<div id="messages" style="background:#d4edda; padding:15px; border-radius:6px; margin-bottom:15px; color:#155724;">');
  foreach ($messages as $message) {
    print($message);
  }
  print('</div>');
}
?>

    <form action="" method="POST">
      <!--name-->
      <label>ФИО:</label>
      <input name="fio" 
        <?php if (!empty($errors['fio'])) {print 'class="error"';} ?> 
        value="<?php print $values['fio']; ?>" 
        placeholder="Введите ваше ФИО"
      />

      <!--phone-->
      <label>Телефон:</label>
      <input name="phone" 
        <?php if (!empty($errors['phone'])) {print 'class="error"';} ?> 
        value="<?php print $values['phone']; ?>" 
        placeholder="+7 (999) 123-45-67"
      />

      <!--email-->
      <label>Email:</label>
      <input name="email" 
        <?php if (!empty($errors['email'])) {print 'class="error"';} ?> 
        value="<?php print $values['email']; ?>" 
        placeholder="example@mail.com"
      />

      <!--date-->
      <label>Дата рождения:</label>
      <input type="date" name="date" 
        <?php if (!empty($errors['date'])) {print 'class="error"';} ?> 
        value="<?php print $values['date']; ?>" 
      />

      <!--gender-->
      <label>Пол:</label>
      <div class="radio-group">
        <label><input type="radio" name="gender" value="male" <?php if ($values['gender'] == 'male') echo 'checked'; ?>> Мужской</label>
        <label><input type="radio" name="gender" value="female" <?php if ($values['gender'] == 'female') echo 'checked'; ?>> Женский</label>
      </div>
      <?php if (!empty($errors['gender'])) {print '<div style="color:red;">Выберите пол</div>';} ?>

      <!--languages-->
      <label>Языки программирования:</label>
      <div class="checkbox-group">
        <label><input type="checkbox" name="languages[]" value="PHP" <?php if (strpos($values['languages'], 'PHP') !== false) echo 'checked'; ?>> PHP</label>
        <label><input type="checkbox" name="languages[]" value="Python" <?php if (strpos($values['languages'], 'Python') !== false) echo 'checked'; ?>> Python</label>
        <label><input type="checkbox" name="languages[]" value="Java" <?php if (strpos($values['languages'], 'Java') !== false) echo 'checked'; ?>> Java</label>
        <label><input type="checkbox" name="languages[]" value="JavaScript" <?php if (strpos($values['languages'], 'JavaScript') !== false) echo 'checked'; ?>> JavaScript</label>
      </div>
      <?php if (!empty($errors['languages'])) {print '<div style="color:red;">Выберите хотя бы один язык</div>';} ?>

      <!--biography-->
      <label>Биография:</label>
      <textarea name="biography" rows="5" 
        <?php if (!empty($errors['biography'])) {print 'class="error"';} ?> 
        placeholder="Расскажите о себе (минимум 10 символов)"><?php print $values['biography']; ?></textarea>

      <!--contract-->
      <label>
        <input type="checkbox" name="contract" value="yes" <?php if ($values['contract'] == 'yes') echo 'checked'; ?>>
        Я согласен с условиями контракта
      </label>
      <?php if (!empty($errors['contract'])) {print '<div style="color:red;">Необходимо принять условия контракта</div>';} ?>

      <input type="submit" value="Отправить" />
    </form>
    </div>
  </body>
</html>
