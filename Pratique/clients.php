<?php

include 'config.php';

# AJOUT
if(isset($_POST['add'])) {

    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    if(empty($name) || empty($phone)){
        die("Заполните все обязательные поля");
    }

    $sql = "INSERT INTO customers(full_name, phone)
            VALUES(?, ?)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$name, $phone]);

    header("Location: clients.php");
    exit;
}

# SUPPRESSION
if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $sql = "DELETE FROM customers WHERE id=?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$id]);

    header("Location: clients.php");
    exit;
}

# MODIFICATION
if(isset($_POST['update'])) {
$id = $_POST['id'];

    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    if(empty($name) || empty($phone)){
        die("Заполните все обязательные поля");
    }

    $sql = "UPDATE customers
            SET full_name=?, phone=?
            WHERE id=?";
            $stmt = $pdo->prepare($sql);

    $stmt->execute([$name, $phone, $id]);

    header("Location: clients.php");
    exit;
}

# RECHERCHE
if(isset($_GET['search'])) {

    $search = "%" . $_GET['search'] . "%";

    $sql = "SELECT * FROM customers
            WHERE full_name LIKE ?
            OR phone LIKE ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$search, $search]);

    $result = $stmt;
}
else {

    $result = $pdo->query("SELECT * FROM customers ORDER BY id DESC");
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>

<meta charset="UTF-8">
<title>Клиенты</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">

</head>

<body>

<div class="container">

<h1>Клиенты</h1>

<a href="index.php" class="back">Назад</a>

<form method="GET">
<input type="text"
name="search"
placeholder="Поиск клиента">

<button type="submit">
Поиск
</button>

</form>

<h2>Добавить клиента</h2>

<form method="POST">

<input type="text"
name="name"
placeholder="Имя"
required>

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
<div class="table-wrapper">
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
<input type="hidden" name="id" value="<?= $row['id'] ?>">
</td>
<td>
<input type="text" name="name" value="<?= $row['full_name'] ?>" required>
</td>

<td>
<input type="text" name="phone" value="<?= $row['phone'] ?>" required>
</td>

<td>
<button type="submit" name="update">Изменить</button>

<a class="delete" href="?delete=<?= $row['id'] ?>">
Удалить
</a>
</td>

</form>
</tr>

<?php } ?>
    </table>
</div>
</div>

</body>
</html>
