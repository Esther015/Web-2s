<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Анкета</title>
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
  </style>
</head>
<body>

<div class="container">
<h2>Анкета</h2>

<form method="POST">

  <label>ФИО:</label>
  <input type="text" name="fio" required>

  <label>Телефон:</label>
  <input type="tel" name="phone" required>

  <label>Email:</label>
  <input type="email" name="email" required>

  <label>Дата рождения:</label>
  <input type="date" name="birthdate" required>

  <label>Пол:</label><br>
  <input type="radio" name="gender" value="male" required> Мужской
  <input type="radio" name="gender" value="female"> Женский
  <br><br>

  <label>Любимый язык программирования:</label>
  <select name="languages[]" multiple size="3" required>
    <option value="1">Pascal</option>
    <option value="2">C</option>
    <option value="3">C++</option>
    <option value="4">JavaScript</option>
    <option value="5">PHP</option>
    <option value="6">Java</option>
    <option value="7">Python</option>
  </select>

  <label>Биография:</label>
  <textarea name="biography" rows="4" required></textarea>

  <br>
  <input type="checkbox" name="contract" value="1" required>
  С контрактом ознакомлен(а)
  <br><br>

  <button type="submit">Сохранить</button>

</form>
</div>

</body>
</html>
