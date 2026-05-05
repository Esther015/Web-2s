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
      .error-message {
        color: red;
        font-size: 14px;
        margin-top: -8px;
        margin-bottom: 10px;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <h2>Анкета</h2>
      
      <div style="text-align:right; margin-bottom:15px;">
        <a href="login.php" style="color:#1a237e; text-decoration:none; font-size:14px;">
           Уже есть аккаунт? Войти
        </a>
      </div>
      <!-- FIN DE L'AJOUT -->
      
<?php
if (!empty($messages)) {
  // ... le reste reste identique
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
      <input name="name" 
        <?php if (!empty($errors['name'])) {print 'class="error"';} ?> 
        value="<?php print $values['name']; ?>" 
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
      <input type="date" name="birthdate" 
        <?php if (!empty($errors['birthdate'])) {print 'class="error"';} ?> 
        value="<?php print $values['birthdate']; ?>" 
      />

      <!--gender-->
      <label>Пол:</label>
      <div class="radio-group">
        <label><input type="radio" name="gender" value="male" <?php if ($values['gender'] == 'male') echo 'checked'; ?>> Мужской</label>
        <label><input type="radio" name="gender" value="female" <?php if ($values['gender'] == 'female') echo 'checked'; ?>> Женский</label>
      </div>
      <?php if (!empty($errors['gender'])) {print '<div style="color:red;">Выберите пол</div>';} ?>

      <!--languages-->
      <label>Любимый язык:</label>
      <select name="languages[]" multiple size="3"
        <?php if ($errors['languages']) print 'class="error"'; ?>>
        <option value="1" <?php if (in_array("1", $values['languages'])) print 'selected'; ?>>Pascal</option>
        <option value="2" <?php if (in_array("2", $values['languages'])) print 'selected'; ?>>C</option>
        <option value="3" <?php if (in_array("3", $values['languages'])) print 'selected'; ?>>C++</option>
        <option value="4" <?php if (in_array("4", $values['languages'])) print 'selected'; ?>>JavaScript</option>
        <option value="5" <?php if (in_array("5", $values['languages'])) print 'selected'; ?>>PHP</option>
        <option value="6" <?php if (in_array("6", $values['languages'])) print 'selected'; ?>>Java</option>
        <option value="7" <?php if (in_array("7", $values['languages'])) print 'selected'; ?>>Python</option>
      </select>
      <?php if ($errors['languages']) print '<div class="error-message">Выберите хотя бы один язык</div>'; ?>

      <!--biography-->
      <label>Биография:</label>
      <textarea name="biography" rows="5" 
        <?php if (!empty($errors['biography'])) {print 'class="error"';} ?> 
        placeholder="Расскажите о себе (минимум 10 символов)"><?php print $values['biography']; ?></textarea>

      <!--contract-->
      <label>
        <input type="checkbox" name="contract" value="1" <?php if ($values['contract'] == '1') echo 'checked'; ?>>
        Я согласен с условиями контракта
      </label>
      <?php if (!empty($errors['contract'])) {print '<div style="color:red;">Необходимо принять условия контракта</div>';} ?>

      <input type="submit" value="Отправить" />
    </form>
    </div>
  </body>
</html>
