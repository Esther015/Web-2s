<?php

include 'config.php';

# CLIENT ACTIF
$activeCustomer = null;

if(isset($_GET['customer'])) {

    $stmt = $pdo->prepare("SELECT full_name FROM customers WHERE id=?");
    $stmt->execute([$_GET['customer']]);
    $activeCustomer = $stmt->fetch(PDO::FETCH_ASSOC);
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

    # STOCK
    $stmt = $pdo->prepare("SELECT quantity FROM medicines WHERE id=?");
    $stmt->execute([$medicine]);
    $med = $stmt->fetch(PDO::FETCH_ASSOC);

    if($quantity > $med['quantity']){
        die("Недостаточно товара на складе");
    }

    # CLIENT
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE full_name=? OR phone=?");
    $stmt->execute([$customer_name, $customer_phone]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$customer){

        $stmt = $pdo->prepare("INSERT INTO customers(full_name, phone) VALUES(?, ?)");
        $stmt->execute([$customer_name, $customer_phone]);

        $customer_id = $pdo->lastInsertId();

    } else {
        $customer_id = $customer['id'];
    }

    # VENTE
    $stmt = $pdo->prepare("INSERT INTO sales(medicine_id, customer_id, employee_id, quantity, sale_date)
                           VALUES (?, ?, ?, ?, NOW())");

    $stmt->execute([$medicine, $customer_id, $employee, $quantity]);

    # STOCK UPDATE
    $pdo->prepare("UPDATE medicines SET quantity = quantity - ? WHERE id=?")
        ->execute([$quantity, $medicine]);

    header("Location: sales.php");
    exit;
}

# MODIFICATION VENTE
if(isset($_POST['update'])) {

    $id = $_POST['id'];
    $medicine = $_POST['medicine'];
    $employee = $_POST['employee'];
    $quantity = $_POST['quantity'];

    $old = $pdo->prepare("SELECT medicine_id, quantity FROM sales WHERE id=?");
    $old->execute([$id]);
    $old = $old->fetch(PDO::FETCH_ASSOC);

    $pdo->prepare("UPDATE medicines SET quantity = quantity + ? WHERE id=?")
        ->execute([$old['quantity'], $old['medicine_id']]);

    $pdo->prepare("UPDATE sales SET medicine_id=?, employee_id=?, quantity=? WHERE id=?")
        ->execute([$medicine, $employee, $quantity, $id]);

    $pdo->prepare("UPDATE medicines SET quantity = quantity - ? WHERE id=?")
        ->execute([$quantity, $medicine]);

    header("Location: sales.php");
    exit;
}

# REQUETE
$sql = "SELECT sales.*, 
        medicines.name AS medicine,
        customers.full_name AS customer,
        employees.full_name AS employee
        FROM sales
        JOIN medicines ON sales.medicine_id = medicines.id
        JOIN customers ON sales.customer_id = customers.id
        JOIN employees ON sales.employee_id = employees.id";

$conditions = [];
$params = [];

if(isset($_GET['customer'])) {
    $conditions[] = "customers.id = ?";
    $params[] = $_GET['customer'];
}

if(isset($_GET['search']) && !empty($_GET['search'])) {

    $search = "%".$_GET['search']."%";

    $conditions[] = "(medicines.name LIKE ? OR customers.full_name LIKE ? OR employees.full_name LIKE ?)";
    array_push($params, $search, $search, $search);
}

if(count($conditions)){
    $sql .= " WHERE ".implode(" AND ", $conditions);
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
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

<h1>Продажи</h1>

<?php if($activeCustomer) { ?>
<h3>Клиент: <?= $activeCustomer['full_name'] ?></h3>
<?php } ?>

<form method="GET">
<input type="text" name="search" placeholder="Поиск">
<button>Поиск</button>
</form>

<h2>Добавить продажу</h2>

<form method="POST">

<select name="medicine">
<?php while($m = $medicines->fetch(PDO::FETCH_ASSOC)) { ?>
<option value="<?= $m['id'] ?>"><?= $m['name'] ?></option>
<?php } ?>
</select>

<select name="employee">
<?php while($e = $employees->fetch(PDO::FETCH_ASSOC)) { ?>
<option value="<?= $e['id'] ?>"><?= $e['full_name'] ?></option>
<?php } ?>
</select>

<input type="text" name="customer_name" placeholder="Клиент" required>
<input type="text" name="customer_phone" placeholder="Телефон">
<input type="number" name="quantity" min="1" required>

<button type="submit" name="add">Добавить</button>

</form>

<table>

<tr>
<th>ID</th>
<th>Лекарство</th>
<th>Клиент</th>
<th>Сотрудник</th>
<th>Количество</th>
<th>Дата</th>
<th>Действия</th>
</tr>

<?php while($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>

<tr class="<?= isset($_GET['customer']) && $_GET['customer'] == $row['customer_id'] ? 'active-sale' : '' ?>">

<td><?= $row['id'] ?></td>
<td><?= $row['medicine'] ?></td>
<td><?= $row['customer'] ?></td>
<td><?= $row['employee'] ?></td>
<td><?= $row['quantity'] ?></td>
<td><?= $row['sale_date'] ?></td>

<td>
<a class="edit-btn" href="sales.php?edit=<?= $row['id'] ?>">Изменить</a>
</td>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>
