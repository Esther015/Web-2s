<!-- sales.php -->

<?php

include 'config.php';

# VENTE SELECTIONNEE
$selectedSale = null;

if(isset($_GET['sale'])) {
    $selectedSale = $_GET['sale'];
}

# AJOUT VENTE
if(isset($_POST['add'])) {

    $medicine_id = $_POST['medicine_id'];
    $customer_id = $_POST['customer_id'];
    $employee_id = $_POST['employee_id'];
    $quantity = $_POST['quantity'];

    if(
        empty($medicine_id) ||
        empty($customer_id) ||
        empty($employee_id) ||
        empty($quantity)
    ) {
        die("Заполните все обязательные поля");
    }

    # AJOUT
    $sql = "
    INSERT INTO sales(
        medicine_id,
        customer_id,
        employee_id,
        quantity,
        sale_date
    )
    VALUES(?, ?, ?, ?, CURDATE())
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $medicine_id,
        $customer_id,
        $employee_id,
        $quantity
    ]);

    header("Location: sales.php");
    exit;
}

# SUPPRESSION
if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $sql = "DELETE FROM sales WHERE id=?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$id]);

    header("Location: sales.php");
    exit;
}

# MEDICAMENTS
$medicines = $pdo->query("
SELECT *
FROM medicines
ORDER BY name ASC
");

# CLIENTS
$customers = $pdo->query("
SELECT *
FROM customers
ORDER BY full_name ASC
");

# EMPLOYES
$employees = $pdo->query("
SELECT *
FROM employees
ORDER BY full_name ASC
");

# VENTES
$sqlSales = "

SELECT

    sales.*,

    medicines.name AS medicine_name,
    medicines.price AS medicine_price,

    customers.full_name,

    employees.full_name AS employee_name

FROM sales

JOIN medicines
ON sales.medicine_id = medicines.id

JOIN customers
ON sales.customer_id = customers.id

JOIN employees
ON sales.employee_id = employees.id

ORDER BY sales.id DESC

";

$result = $pdo->query($sqlSales);

?>

<!DOCTYPE html>
<html lang="ru">

<head>

<meta charset="UTF-8">

<title>
Продажи
</title>

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<link rel="stylesheet"
href="style.css">

</head>

<body>

<div class="container">
<div class="page-content">

<h1>
Продажи
</h1>

<a href="clients.php"
class="back">
Назад
</a>

<!-- AJOUT -->
<h2>
Добавить продажу
</h2>

<form method="POST">

<select name="medicine_id" required>

<option value="">
Лекарство
</option>

<?php while($medicine = $medicines->fetch(PDO::FETCH_ASSOC)) { ?>

<option value="<?= $medicine['id'] ?>">

<?= $medicine['name'] ?>
(остаток: <?= $medicine['stock'] ?>)

</option>

<?php } ?>

</select>

<select name="customer_id" required>

<option value="">
Клиент
</option>

<?php while($customer = $customers->fetch(PDO::FETCH_ASSOC)) { ?>

<option value="<?= $customer['id'] ?>">

<?= $customer['full_name'] ?>

</option>

<?php } ?>

</select>

<select name="employee_id" required>

<option value="">
Сотрудник
</option>

<?php while($employee = $employees->fetch(PDO::FETCH_ASSOC)) { ?>

<option value="<?= $employee['id'] ?>">

<?= $employee['full_name'] ?>

</option>

<?php } ?>

</select>

<input type="number"
name="quantity"
placeholder="Количество"
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
<th>Лекарство</th>
<th>Клиент</th>
<th>Сотрудник</th>
<th>Количество</th>
<th>Prix</th>
<th>Дата</th>
<th>Действие</th>

</tr>

<?php while($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>

<tr
id="sale<?= $row['id'] ?>"
class="<?= ($row['id'] == $selectedSale) ? 'highlight' : '' ?>">

<td>

<?= $row['id'] ?>

</td>

<td>

<?= $row['medicine_name'] ?>

</td>

<td>

<?= $row['full_name'] ?>

</td>

<td>

<?= $row['employee_name'] ?>

</td>

<td>

<?= $row['quantity'] ?>

</td>

<td>

<?= $row['medicine_price'] ?> €

</td>

<td>

<?= $row['sale_date'] ?>

</td>

<td>

<a href="#">
Изменить
</a>

<a class="delete"
href="?delete=<?= $row['id'] ?>"
onclick="return confirm('Удалить продажу ?')">

Удалить

</a>

</td>

</tr>

<?php } ?>

</table>

</div>

</div>
</div>

<?php if($selectedSale) { ?>

<script>

window.onload = function() {

    const element =
    document.getElementById(
        "sale<?= $selectedSale ?>"
    );

    if(element) {

        element.scrollIntoView({

            behavior: "smooth",
            block: "center"

        });

    }

};

</script>

<?php } ?>

</body>
</html>
