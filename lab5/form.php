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

<?php
if (!empty($messages)) {
  print('<div id="messages
          style="
          background:#d4edda;
          padding:15px;
          border-radius:6px;
          margin-bottom:15px;
          color:#155724;
          "
        ">');
  foreach ($messages as $message) {
    print($message);
  }
  print('</div>');
}

// Далее выводим форму отмечая элементы с ошибками классом error
// и задавая начальные значения элементов ранее сохраненными.
?>

    <form action="" method="POST">

      <!--name-->
      <input name="fio" 
        <?php if ($errors['fio']) {print 'class="error"';} ?> 
        value="<?php print $values['fio']; ?>" 
      />

      <!--phone-->
      <input name="fio" 
        <?php if ($errors['fio']) {print 'class="error"';} ?> 
        value="<?php print $values['fio']; ?>" 
      />

      <!--email-->
      <input name="fio" 
        <?php if ($errors['fio']) {print 'class="error"';} ?> 
        value="<?php print $values['fio']; ?>" 
      />

      <!--date-->
      <input name="fio" 
        <?php if ($errors['fio']) {print 'class="error"';} ?> 
        value="<?php print $values['fio']; ?>" 
      />

      <!--gender-->
      <input name="fio" 
        <?php if ($errors['fio']) {print 'class="error"';} ?> 
        value="<?php print $values['fio']; ?>" 
      />

      <!--languages-->
      <input name="fio" 
        <?php if ($errors['fio']) {print 'class="error"';} ?> 
        value="<?php print $values['fio']; ?>" 
      />

      <!--biography-->
      <input name="fio" 
        <?php if ($errors['fio']) {print 'class="error"';} ?> 
        value="<?php print $values['fio']; ?>" 
      />

      <!--contract-->
      

      <input type="submit" value="ok" />
    </form>
  </body>
</html>
