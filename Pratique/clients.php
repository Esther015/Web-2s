<?php

include 'config.php';

if(isset($_POST['add'])) {

    $name = $_POST['name'];
    $phone = $_POST['phone'];

    mysqli_query($conn,
    "INSERT INTO customers(full_name, phone)

    VALUES('$name','$phone')");
}

if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    mysqli_query($conn,
    "DELETE FROM customers WHERE id=$id");
}

if(isset($_POST['update'])) {

    $id = $_POST['id'];

    $name = $_POST['name'];
    $phone = $_POST['phone'];

    mysqli_query($conn,
    "UPDATE customers SET

    full_name='$name',
    phone='$phone'

    WHERE id=$id");
}

$result = mysqli_query($conn,
"SELECT * FROM customers");

?>

<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<title>Clients</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

<h1>Clients</h1>

<a href="index.php" class="back">
← Retour
</a>

<form method="POST">

<input type="text"
name="name"
placeholder="Nom">

<input type="text"
name="phone"
placeholder="Téléphone">

<button type="submit"
name="add">
Ajouter
</button>

</form>

<table>

<tr>
<th>ID</th>
<th>Nom</th>
<th>Téléphone</th>
<th>Actions</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)) { ?>

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
Modifier
</button>

<a class="delete"
href="?delete=<?= $row['id'] ?>">
Supprimer
</a>

</td>

</form>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>
