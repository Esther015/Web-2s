<?php

include 'config.php';

# AJOUT
if(isset($_POST['add'])) {

    $name = $_POST['name'];
    $manufacturer = $_POST['manufacturer'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $expiration = $_POST['expiration'];

    $sql = "INSERT INTO medicines
            (name, manufacturer, price,
            quantity, expiration_date)
            VALUES(?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $name,
        $manufacturer,
        $price,
        $quantity,
        $expiration
    ]);
}

# SUPPRESSION
if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $sql = "DELETE FROM medicines WHERE id=?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$id]);
}

# MODIFICATION
if(isset($_POST['update'])) {

    $id = $_POST['id'];

    $name = $_POST['name'];
    $manufacturer = $_POST['manufacturer'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $expiration = $_POST['expiration'];

    $sql = "UPDATE medicines
            SET
            name=?,
            manufacturer=?,
            price=?,
            quantity=?,
            expiration_date=?
            WHERE id=?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $name,
        $manufacturer,
        $price,
        $quantity,
        $expiration,
        $id
    ]);
}

# RECHERCHE
if(isset($_GET['search'])) {

    $search = "%" . $_GET['search'] . "%";

    $sql = "SELECT * FROM medicines
            WHERE name LIKE ?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$search]);

    $result = $stmt;
}
else {

    $result = $pdo->query("SELECT * FROM medicines");
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>

<meta charset="UTF-8">
<title>Лекарства</title>

<link rel="stylesheet" href="style.css">

</head>

<body>

<div class="container">

<h1>Лекарства</h1>

<a href="index.php" class="back">
← Назад
</a>

<form method="GET">

<input type="text"
name="search"
placeholder="Поиск лекарства">

<button type="submit">
Поиск
</button>

</form>

<h2>Добавить лекарство</h2>

<form method="POST">

<input type="text"
name="name"
placeholder="Название"
required>

<input type="text"
name="manufacturer"
placeholder="Производитель">

<input type="number"
step="0.01"
name="price"
placeholder="Цена">

<input type="number"
name="quantity"
placeholder="Количество">

<input type="date"
name="expiration">

<button type="submit"
name="add">
Добавить
</button>

</form>

<table>

<tr>
<th>ID</th>
<th>Название</th>
<th>Производитель</th>
<th>Цена</th>
<th>Количество</th>
<th>Срок годности</th>
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
value="<?= $row['name'] ?>">

</td>

<td>

<input type="text"
name="manufacturer"
value="<?= $row['manufacturer'] ?>">

</td>

<td>

<input type="number"
step="0.01"
name="price"
value="<?= $row['price'] ?>">

</td>

<td>

<input type="number"
name="quantity"
    min ="0" required ?>
<!-- value="*?= $row['quantity'] ?>"*-->

</td>

<td>

<input type="date"
name="expiration"
value="<?= $row['expiration_date'] ?>">

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
