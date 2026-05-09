<?php

include 'config.php';

# AJOUT VENTE
if(isset($_POST['add'])) {

    $medicine = $_POST['medicine'];
    $customer = $_POST['customer'];
    $employee = $_POST['employee'];
    $quantity = $_POST['quantity'];

    # AJOUT DANS SALES
    $sql = "INSERT INTO sales
            (medicine_id, customer_id, employee_id, quantity, sale_date)
            VALUES (?, ?, ?, ?, NOW())";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $medicine,
        $customer,
        $employee,
        $quantity
    ]);

    # DIMINUER STOCK
    $sql2 = "UPDATE medicines
             SET quantity = quantity - ?
             WHERE id=?";

    $stmt2 = $pdo->prepare($sql2);

    $stmt2->execute([$quantity, $medicine]);
}

# SUPPRESSION
if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $sql = "DELETE FROM sales WHERE id=?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$id]);
}

# AFFICHAGE AVEC JOIN
$sql = "SELECT sales.id,
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
        ON sales.employee_id = employees.id

        ORDER BY sales.id DESC";

$result = $pdo->query($sql);

$medicines = $pdo->query("SELECT * FROM medicines");
$customers = $pdo->query("SELECT * FROM customers");
$employees = $pdo->query("SELECT * FROM employees");

?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Продажи</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

<h1>Продажи</h1>

<a href="index.php" class="back">
← Назад
</a>

<h2>Добавить продажу</h2>

<form method="POST">

<select name="medicine">

<?php while($m = $medicines->fetch(PDO::FETCH_ASSOC)) { ?>

<option value="<?= $m['id'] ?>">
<?= $m['name'] ?>
</option>

<?php } ?>

</select>

<select name="customer">

<?php while($c = $customers->fetch(PDO::FETCH_ASSOC)) { ?>

<option value="<?= $c['id'] ?>">
<?= $c['full_name'] ?>
</option>

<?php } ?>

</select>

<select name="employee">

<?php while($e = $employees->fetch(PDO::FETCH_ASSOC)) { ?>

<option value="<?= $e['id'] ?>">
<?= $e['full_name'] ?>
</option>

<?php } ?>

</select>

<input type="number"
name="quantity"
min="1"
placeholder="Количество"
required>

<button type="submit"
name="add">
Добавить
</button>

</form>

<table>

<tr>
<th>ID</th>
<th>Лекарство</th>
<th>Клиент</th>
<th>Сотрудник</th>
<th>Количество</th>
<th>Дата</th>
<th>Действие</th>
</tr>

<?php while($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>

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
Удалить
</a>
</td>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>
