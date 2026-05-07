<?php

include 'config.php';

# AJOUT
if(isset($_POST['add'])) {

    $name = $_POST['name'];
    $manufacturer = $_POST['manufacturer'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $expiration = $_POST['expiration'];

    mysqli_query($conn,
    "INSERT INTO medicines(name, manufacturer, price, quantity, expiration_date)

    VALUES('$name','$manufacturer','$price','$quantity','$expiration')");
}

# SUPPRESSION
if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    mysqli_query($conn,
    "DELETE FROM medicines WHERE id=$id");
}

# MODIFICATION
if(isset($_POST['update'])) {

    $id = $_POST['id'];

    $name = $_POST['name'];
    $manufacturer = $_POST['manufacturer'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $expiration = $_POST['expiration'];

    mysqli_query($conn,
    "UPDATE medicines SET

    name='$name',
    manufacturer='$manufacturer',
    price='$price',
    quantity='$quantity',
    expiration_date='$expiration'

    WHERE id=$id");
}

# RECHERCHE
$search = "";

if(isset($_GET['search'])) {
    $search = $_GET['search'];

    $result = mysqli_query($conn,
    "SELECT * FROM medicines
    WHERE name LIKE '%$search%'");
}
else {
    $result = mysqli_query($conn,
    "SELECT * FROM medicines");
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Médicaments</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

<h1>Médicaments</h1>

<a href="index.php" class="back">
← Retour
</a>

<form method="GET">

    <input type="text"
    name="search"
    placeholder="Recherche médicament">

    <button type="submit">
        Rechercher
    </button>

</form>

<h2>Ajouter médicament</h2>

<form method="POST">

    <input type="text"
    name="name"
    placeholder="Nom"
    required>

    <input type="text"
    name="manufacturer"
    placeholder="Fabricant">

    <input type="number"
    step="0.01"
    name="price"
    placeholder="Prix">

    <input type="number"
    name="quantity"
    placeholder="Quantité">

    <input type="date"
    name="expiration">

    <button type="submit" name="add">
        Ajouter
    </button>

</form>

<h2>Liste des médicaments</h2>

<table>

<tr>
    <th>ID</th>
    <th>Nom</th>
    <th>Fabricant</th>
    <th>Prix</th>
    <th>Quantité</th>
    <th>Expiration</th>
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
value="<?= $row['quantity'] ?>">
</td>

<td>
<input type="date"
name="expiration"
value="<?= $row['expiration_date'] ?>">
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
