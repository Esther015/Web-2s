<?php

include 'config.php';

# AJOUT
if(isset($_POST['add'])) {

    $name = $_POST['name'];
    $phone = $_POST['phone'];

    $sql = "INSERT INTO customers(full_name, phone)
            VALUES(?, ?)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$name, $phone]);
}

# SUPPRESSION
if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $sql = "DELETE FROM customers WHERE id=?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$id]);
}

# MODIFICATION
if(isset($_POST['update'])) {

    $id = $_POST['id'];

    $name = $_POST['name'];
    $phone = $_POST['phone'];

    $sql = "UPDATE customers
            SET full_name=?, phone=?
            WHERE id=?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$name, $phone, $id]);
}

# AFFICHAGE
$result = $pdo->query("SELECT * FROM customers");

?>

<!DOCTYPE html>
<html lang="ru">

<head>

<meta charset="UTF-8">
<title>Клиенты</title>

<link rel="stylesheet" href="style.css">

</head>

<body>

<div class="container">

<h1>Клиенты</h1>

<a href="index.php" 
   class="back"
   aria-label="Назад">
   Назад
</a>

<h2>Добавить клиента</h2>

<form method="POST">

<input type="text"
name="name"
placeholder="Имя"
required>

<input type="text"
name="phone"
placeholder="Телефон"
required>

<button type="submit"
name="add">
Добавить
</button>

</form>

<table>

<tr>
<th>ID</th>
<th>Имя</th>
<th>Телефон</th>
<th>Действия</th>
</tr>

<?php while($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>

<tr>

<form method="POST">

<td>

<?= $row['id'] ?>

<input type="hidden"
name="id"
value="<?= $row['id'] ?>">

</td>

<td>

<input type="text"
name="name"
value="<?= $row['full_name'] ?>">

</td>

<td>

<input type="text"
name="phone"
value="<?= $row['phone'] ?>">

</td>

<td>

<button type="submit"
name="update">
Изменить
</button>

<a class="delete"
href="?delete=<?= $row['id'] ?>">
Удалить
</a>

</td>

</form>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>
