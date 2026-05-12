<?php

include 'config.php';

# MODE EDITION
$editSale = null;
$editId = null;

if(isset($_GET['edit'])) {

    $editId = $_GET['edit'];

    $sqlEdit = "
    SELECT sales.*,
           customers.full_name,
           customers.phone
    FROM sales

    JOIN customers
    ON sales.customer_id = customers.id

    WHERE sales.id=?
    ";

    $stmtEdit = $pdo->prepare($sqlEdit);

    $stmtEdit->execute([$editId]);

    $editSale = $stmtEdit->fetch(PDO::FETCH_ASSOC);

    if(!$editSale){
        die("Продажа не найдена");
    }
}

# VENTE SELECTIONNEE
$selectedSale = null;

if(isset($_GET['sale'])) {
    $selectedSale = $_GET['sale'];
}

# AJOUT VENTE
if(isset($_POST['add'])) {

    $medicine_id = $_POST['medicine_id'];

    $customer_name = trim($_POST['customer_name']);
    $phone = trim($_POST['phone']);

    $employee_id = $_POST['employee_id'];

    $quantity = trim($_POST['quantity']);

    if(
        empty($medicine_id) ||
        empty($customer_name) ||
        empty($employee_id) ||
        empty($quantity)
    ) {
        die("Заполните все обязательные поля");
    }

    # RECHERCHE CLIENT
    $sqlCustomer = "
    SELECT id , phone
    FROM customers
    WHERE full_name=?
    LIMIT 1
    ";

    $stmtCustomer = $pdo->prepare($sqlCustomer);

    $stmtCustomer->execute([$customer_name]);

    $customer = $stmtCustomer->fetch(PDO::FETCH_ASSOC);

    # SI CLIENT N'EXISTE PAS
    if(!$customer) {

        $sqlInsertCustomer = "
        INSERT INTO customers(full_name, phone)
        VALUES(?, ?)
        ";

        $stmtInsertCustomer =
        $pdo->prepare($sqlInsertCustomer);

        $stmtInsertCustomer->execute([
            $customer_name,
            $phone
        ]);

        $customer_id = $pdo->lastInsertId();

    } else {

        $customer_id = $customer['id'];

        if(empty($phone)) {
            $phone = $customer['phone'];
        }
    }

    # AJOUT VENTE
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

# MODIFICATION
if(isset($_POST['update'])) {

    $id = $_POST['id'];

    $medicine_id = $_POST['medicine_id'];
    $employee_id = $_POST['employee_id'];

    $customer_name = trim($_POST['customer_name']);
    $phone = trim($_POST['phone']);

    $quantity = trim($_POST['quantity']);

    if(
        empty($medicine_id) ||
        empty($customer_name) ||
        empty($employee_id) ||
        empty($quantity)
    ) {
        die("Заполните все обязательные поля");
    }

    # CLIENT
    $sqlCustomer = "
    SELECT id
    FROM customers
    WHERE full_name=?
    LIMIT 1
    ";

    $stmtCustomer = $pdo->prepare($sqlCustomer);

    $stmtCustomer->execute([$customer_name]);

    $customer = $stmtCustomer->fetch(PDO::FETCH_ASSOC);

    if(!$customer){

        $sqlInsertCustomer = "
        INSERT INTO customers(full_name, phone)
        VALUES(?, ?)
        ";

        $stmtInsert = $pdo->prepare($sqlInsertCustomer);

        $stmtInsert->execute([
            $customer_name,
            $phone
        ]);

        $customer_id = $pdo->lastInsertId();

    } else {

        $customer_id = $customer['id'];
    }

    # UPDATE SALE
    $sql = "
    UPDATE sales

    SET
        medicine_id=?,
        customer_id=?,
        employee_id=?,
        quantity=?

    WHERE id=?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $medicine_id,
        $customer_id,
        $employee_id,
        $quantity,
        $id
    ]);

    header("Location: sales.php?sale=".$id);

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

# EMPLOYES
$employees = $pdo->query("
SELECT *
FROM employees
ORDER BY full_name ASC
");

# CLIENTS POUR SUGGESTIONS
$customers = $pdo->query("
SELECT *
FROM customers
ORDER BY full_name ASC
");

# VENTES
$sqlSales = "

SELECT

    sales.*,

    medicines.name AS medicine_name,
    medicines.price AS medicine_price,

    customers.full_name,
    customers.phone,

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

<!-- CLIENT -->
<input
list="customers"
type="text"
name="customer_name"
placeholder="Имя клиента"
required>

<datalist id="customers">

<?php while($customer = $customers->fetch(PDO::FETCH_ASSOC)) { ?>

<option value="<?= $customer['full_name'] ?>">

<?php } ?>

</datalist>

<input
type="text"
name="phone"
placeholder="Телефон">

<!-- EMPLOYE -->
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

<input
type="number"
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
<th>Телефон</th>
<th>Сотрудник</th>
<th>Количество</th>
<th>Цена</th>
<th>Дата</th>
<th>Действие</th>

</tr>

<?php while($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>

<tr
id="sale<?= $row['id'] ?>"
class="<?= ($row['id'] == $selectedSale) ? 'highlight' : '' ?>">

<td><?= $row['id'] ?></td>
<td><?= $row['medicine_name'] ?></td>
<td><?= $row['full_name'] ?></td>
<td><?= $row['phone'] ?></td>
<td><?= $row['employee_name'] ?></td>
<td><?= $row['quantity'] ?></td>
<td><?= $row['medicine_price'] ?> ₽</td>
<td><?= $row['sale_date'] ?></td>

<td>

<a class="edit"
href="?edit=<?= $row['id'] ?>">
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
