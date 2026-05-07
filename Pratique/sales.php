<?php

include 'config.php';

if(isset($_POST['add'])) {

    $medicine = $_POST['medicine'];
    $customer = $_POST['customer'];
    $employee = $_POST['employee'];
    $quantity = $_POST['quantity'];
    $date = $_POST['date'];

    mysqli_query($conn,
    "INSERT INTO sales

    (medicine_id, customer_id, employee_id, quantity, sale_date)

    VALUES

    ('$medicine','$customer','$employee','$quantity','$date')");
}

if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    mysqli_query($conn,
    "DELETE FROM sales WHERE id=$id");
}

$medicines = mysqli_query($conn,
"SELECT * FROM medicines");

$customers = mysqli_query($conn,
"SELECT * FROM customers");

$employees = mysqli_query($conn,
"SELECT * FROM employees");

$result = mysqli_query($conn,

"SELECT sales.id,

medicines.name AS medicine,
customers.full_name AS customer,
employees.full_name AS employee,

sales.quantity,
sales.sale_date

FROM sales

JOIN medicines
ON sales.medicine_id = medicines.id

JOIN customers
ON sales.customer_id = customers.id

JOIN employees
ON sales.employee_id = employees.id");

?>

<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<title>Ventes</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

<h1>Ventes</h1>

<a href="index.php" class="back">
← Retour
</a>

<form method="POST">

<select name="medicine">

<?php while($m = mysqli_fetch_assoc($medicines)) { ?>

<option value="<?= $m['id'] ?>">
<?= $m['name'] ?>
</option>

<?php } ?>

</select>

<select name="customer">

<?php while($c = mysqli_fetch_assoc($customers)) { ?>

<option value="<?= $c['id'] ?>">
<?= $c['full_name'] ?>
</option>

<?php } ?>

</select>

<select name="employee">

<?php while($e = mysqli_fetch_assoc($employees)) { ?>

<option value="<?= $e['id'] ?>">
<?= $e['full_name'] ?>
</option>

<?php } ?>

</select>

<input type="number"
name="quantity"
placeholder="Quantité">

<input type="date"
name="date">

<button type="submit"
name="add">
Ajouter
</button>

</form>

<table>

<tr>
<th>ID</th>
<th>Médicament</th>
<th>Client</th>
<th>Employé</th>
<th>Quantité</th>
<th>Date</th>
<th>Action</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)) { ?>

<tr>

<td><?= $row['id'] ?></td>

<td><?= $row['medicine'] ?></td>

<td><?= $row['customer'] ?></td>

<td><?= $row['employee'] ?></td>

<td><?= $row['quantity'] ?></td>

<td><?= $row['sale_date'] ?></td>

<td>

<a class="delete"
href="?delete=<?= $row['id'] ?>">
Supprimer
</a>

</td>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>
