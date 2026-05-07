<?php

include 'config.php';

# AJOUT
if(isset($_POST['add'])) {

    $name = $_POST['name'];
    $position = $_POST['position'];
    $phone = $_POST['phone'];

    $sql = "INSERT INTO employees
            (full_name, position, phone)

            VALUES(?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$name, $position, $phone]);
}

# SUPPRESSION
if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $sql = "DELETE FROM employees WHERE id=?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$id]);
}

# MODIFICATION
if(isset($_POST['update'])) {

    $id = $_POST['id'];

    $name = $_POST['name'];
    $position = $_POST['position'];
    $phone = $_POST['phone'];

    $sql = "UPDATE employees

            SET
            full_name=?,
            position=?,
            phone=?

            WHERE id=?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $name,
        $position,
        $phone,
        $id
    ]);
}

$result = $pdo->query(
"SELECT * FROM employees");

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<title>Employés</title>

<link rel="stylesheet" href="style.css">

</head>

<body>

<div class="container">

<h1>Employés</h1>

<a href="index.php" class="back">
← Retour
</a>

<h2>Ajouter employé</h2>

<form method="POST">

<input type="text"
name="name"
placeholder="Nom"
required>

<input type="text"
name="position"
placeholder="Poste"
required>

<input type="text"
name="phone"
placeholder="Téléphone"
required>

<button type="submit"
name="add">
Ajouter
</button>

</form>

<table>

<tr>
<th>ID</th>
<th>Nom</th>
<th>Poste</th>
<th>Téléphone</th>
<th>Actions</th>
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
name="position"
value="<?= $row['position'] ?>">

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
