<?php

include 'config.php';

# MODE EDITION
$editSale = null;

if(isset($_GET['edit'])) {

    $id = $_GET['edit'];

    $sql = "SELECT * FROM sales WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    $editSale = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$editSale){
        die("Продажа не найдена");
    }
}

# AJOUT VENTE
if(isset($_POST['add'])) {

    $medicine = $_POST['medicine'];
    $employee = $_POST['employee'];

    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);

    $quantity = $_POST['quantity'];

    if(empty($customer_name)){
        die("Введите имя клиента");
    }

    if($quantity <= 0){
        die("Количество должно быть больше нуля");
    }

    # VERIFICATION STOCK
    $sqlCheck = "SELECT quantity FROM medicines WHERE id=?";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$medicine]);

    $med = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if($quantity > $med['quantity']){
        die("Недостаточно товара на складе");
    }

    # VERIFIER SI CLIENT EXISTE
    $sqlCustomer = "SELECT id
                    FROM customers
                    WHERE full_name=?
                    OR phone=?";

    $stmtCustomer = $pdo->prepare($sqlCustomer);

    $stmtCustomer->execute([
        $customer_name,
        $customer_phone
    ]);

    $customer = $stmtCustomer->fetch(PDO::FETCH_ASSOC);

    # AJOUT CLIENT SI N'EXISTE PAS
    if(!$customer){

        $sqlInsertCustomer = "INSERT INTO customers
                              (full_name, phone)
                              VALUES(?, ?)";

        $stmtInsert = $pdo->prepare($sqlInsertCustomer);

        $stmtInsert->execute([
            $customer_name,
            $customer_phone
        ]);

        $customer_id = $pdo->lastInsertId();

    } else {

        $customer_id = $customer['id'];
    }

    # AJOUT VENTE
    $sql = "INSERT INTO sales
            (medicine_id, customer_id,
             employee_id, quantity, sale_date)
            VALUES (?, ?, ?, ?, NOW())";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $medicine,
        $customer_id,
        $employee,
        $quantity
    ]);

    # DIMINUER STOCK
    $sql2 = "UPDATE medicines
             SET quantity = quantity - ?
             WHERE id=?";

    $stmt2 = $pdo->prepare($sql2);

    $stmt2->execute([$quantity, $medicine]);

    header("Location: sales.php");
    exit;
}

# MODIFICATION VENTE
if(isset($_POST['update'])) {

    $id = $_POST['id'];
    $medicine = $_POST['medicine'];
    $employee = $_POST['employee'];
    $quantity = $_POST['quantity'];

    if($quantity <= 0){
        die("Количество должно быть больше нуля");
    }

    $sqlOld = "SELECT medicine_id, quantity FROM sales WHERE id=?";
    $stmtOld = $pdo->prepare($sqlOld);
    $stmtOld->execute([$id]);
    $old = $stmtOld->fetch(PDO::FETCH_ASSOC);

    $pdo->prepare("UPDATE medicines SET quantity = quantity + ? WHERE id=?")
        ->execute([$old['quantity'], $old['medicine_id']]);

    $sqlCheck = "SELECT quantity FROM medicines WHERE id=?";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$medicine]);
    $med = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if($quantity > $med['quantity']){
        die("Недостаточно товара");
    }

    $sql = "UPDATE sales
            SET medicine_id=?, employee_id=?, quantity=?
            WHERE id=?";

    $pdo->prepare($sql)->execute([
        $medicine,
        $employee,
        $quantity,
        $id
    ]);

    $pdo->prepare("UPDATE medicines SET quantity = quantity - ? WHERE id=?")
        ->execute([$quantity, $medicine]);

    header("Location: sales.php");
    exit;
}

# AFFICHAGE
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
        ON sales.employee_id = employees.id";

$conditions = [];
$params = [];

if(isset($_GET['customer'])) {
    $conditions[] = "customers.id = ?";
    $params[] = $_GET['customer'];
}

if(isset($_GET['search']) && !empty($_GET['search'])) {

    $search = "%" . $_GET['search'] . "%";

    $conditions[] = "(medicines.name LIKE ?
                    OR customers.full_name LIKE ?
                    OR employees.full_name LIKE ?
                    OR sales.sale_date LIKE ?)";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

if(count($conditions) > 0){
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY sales.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$result = $stmt;

$medicines = $pdo->query("SELECT * FROM medicines");
$employees = $pdo->query("SELECT * FROM employees");

?>

<!DOCTYPE html>
<html lang="ru">

<head>
<meta charset="UTF-8">
<title>Продажи</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">
<div class="page-content">

<h1>Продажи</h1>

<a href="index.php" class="back">Назад</a>

<form method="GET">
<input type="text" name="search" placeholder="Поиск продажи">
<button type="submit">Поиск</button>
</form>

<h2>
<?= isset($editSale) ? "Modifier une vente" : "Добавить продажу" ?>
</h2>

<form method="POST">

<?php if(isset($editSale)) { ?>
    <input type="hidden" name="id" value="<?= $editSale['id'] ?>">
<?php } ?>

<select name="medicine" required>
<?php while($m = $medicines->fetch(PDO::FETCH_ASSOC)) { ?>
<option value="<?= $m['id'] ?>">
<?= $m['name'] ?> (остаток: <?= $m['quantity'] ?>)
</option>
<?php } ?>
</select>

<select name="employee" required>
<?php while($e = $employees->fetch(PDO::FETCH_ASSOC)) { ?>
<option value="<?= $e['id'] ?>">
<?= $e['full_name'] ?>
</option>
<?php } ?>
</select>

<input type="text"
name="customer_name"
placeholder="Имя клиента"
list="customers_list"
autocomplete="off"
required>

<datalist id="customers_list">
<?php
$customersList = $pdo->query("SELECT full_name FROM customers ORDER BY full_name ASC");
while($c = $customersList->fetch(PDO::FETCH_ASSOC)) {
?>
<option value="<?= $c['full_name'] ?>">
<?php } ?>
</datalist>

<input type="text" name="customer_phone" placeholder="Телефон">

<input type="number" name="quantity" min="1" required>

<button type="submit"
name="<?= isset($editSale) ? 'update' : 'add' ?>">
<?= isset($editSale) ? "Сохранить" : "Добавить" ?>
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
<a class="edit-btn" href="sales.php?edit=<?= $row['id'] ?>">Изменить</a>
<a class="delete" href="sales.php?delete=<?= $row['id'] ?>" onclick="return confirm('Supprimer cette vente ?')">Удалить</a>
</td>

</tr>

<?php } ?>

</table>

</div>

</div>
</div>

</body>
</html>
