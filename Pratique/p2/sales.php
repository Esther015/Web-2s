<?php

include 'config.php';

# AJOUT VENTE
if(isset($_POST['save_sale'])) {

    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $employee = $_POST['employee'];

    $medicine_ids = $_POST['medicine_id'];
    $quantities = $_POST['quantity'];

    if(empty($customer_name)){
        die("Введите имя клиента");
    }

    # CLIENT EXISTE ?
    $sqlCustomer = "SELECT * FROM customers WHERE full_name=?";
    $stmtCustomer = $pdo->prepare($sqlCustomer);
    $stmtCustomer->execute([$customer_name]);

    $customer = $stmtCustomer->fetch(PDO::FETCH_ASSOC);

    if($customer){

        $customer_id = $customer['id'];

    } else {

        $sqlInsertCustomer = "
        INSERT INTO customers(full_name, phone)
        VALUES(?, ?)
        ";

        $stmtInsert = $pdo->prepare($sqlInsertCustomer);

        $stmtInsert->execute([
            $customer_name,
            $customer_phone
        ]);

        $customer_id = $pdo->lastInsertId();
    }

    $total = 0;

    foreach($medicine_ids as $index => $med_id){

        $qty = $quantities[$index];

        $sqlMed = "SELECT * FROM medicines WHERE id=?";
        $stmtMed = $pdo->prepare($sqlMed);
        $stmtMed->execute([$med_id]);

        $med = $stmtMed->fetch(PDO::FETCH_ASSOC);

        if($qty > $med['quantity']){
            die("Недостаточно товара");
        }

        $total += $med['price'] * $qty;
    }

    # CREER VENTE
    $sqlSale = "
    INSERT INTO sales(customer_id, employee_id, total_price, sale_date)
    VALUES(?, ?, ?, NOW())
    ";

    $stmtSale = $pdo->prepare($sqlSale);

    $stmtSale->execute([
        $customer_id,
        $employee,
        $total
    ]);

    $sale_id = $pdo->lastInsertId();

    foreach($medicine_ids as $index => $med_id){

        $qty = $quantities[$index];

        $sqlMed = "SELECT * FROM medicines WHERE id=?";
        $stmtMed = $pdo->prepare($sqlMed);
        $stmtMed->execute([$med_id]);

        $med = $stmtMed->fetch(PDO::FETCH_ASSOC);

        # AJOUT PANIER
        $sqlItem = "
        INSERT INTO sale_items
        (sale_id, medicine_id, quantity, unit_price)
        VALUES(?, ?, ?, ?)
        ";

        $stmtItem = $pdo->prepare($sqlItem);

        $stmtItem->execute([
            $sale_id,
            $med_id,
            $qty,
            $med['price']
        ]);

        # DIMINUER STOCK
        $sqlStock = "
        UPDATE medicines
        SET quantity = quantity - ?
        WHERE id=?
        ";

        $stmtStock = $pdo->prepare($sqlStock);

        $stmtStock->execute([
            $qty,
            $med_id
        ]);
    }

    header("Location: sales.php");
    exit;
}

# SUPPRESSION
if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $sqlItems = "
    SELECT * FROM sale_items
    WHERE sale_id=?
    ";

    $stmtItems = $pdo->prepare($sqlItems);
    $stmtItems->execute([$id]);

    while($item = $stmtItems->fetch(PDO::FETCH_ASSOC)){

        $sqlRestore = "
        UPDATE medicines
        SET quantity = quantity + ?
        WHERE id=?
        ";

        $stmtRestore = $pdo->prepare($sqlRestore);

        $stmtRestore->execute([
            $item['quantity'],
            $item['medicine_id']
        ]);
    }

    $sqlDelete = "DELETE FROM sales WHERE id=?";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute([$id]);

    header("Location: sales.php");
    exit;
}

# AFFICHAGE
$sql = "
SELECT sales.*,
customers.full_name AS customer,
employees.full_name AS employee

FROM sales

JOIN customers
ON sales.customer_id = customers.id

JOIN employees
ON sales.employee_id = employees.id

ORDER BY sales.id DESC
";

$result = $pdo->query($sql);

$medicines = $pdo->query("
SELECT * FROM medicines
ORDER BY name
");

$employees = $pdo->query("
SELECT * FROM employees
ORDER BY full_name
");

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

<div class="top-bar">

<h1>Продажи</h1>

<div class="actions">

<button onclick="openModal()">
Новая продажа
</button>

<a href="index.php" class="back">
Назад
</a>

</div>

</div>

<div class="table-wrapper">

<table>

<tr>

<th>ID</th>
<th>Клиент</th>
<th>Сотрудник</th>
<th>Сумма</th>
<th>Дата</th>
<th>Действия</th>

</tr>

<?php while($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>

<tr>

<td><?= $row['id'] ?></td>

<td><?= $row['customer'] ?></td>

<td><?= $row['employee'] ?></td>

<td><?= $row['total_price'] ?> ₽</td>

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

</div>

<!-- MODAL -->

<div class="modal" id="saleModal">

<div class="modal-content">

<h2>Новая продажа</h2>

<form method="POST">

<input type="text"
name="customer_name"
id="customer_name"
placeholder="Клиент"
autocomplete="off"
required>

<div id="suggestions"></div>

<input type="text"
name="customer_phone"
id="customer_phone"
placeholder="Телефон">

<select name="employee" required>

<?php while($e = $employees->fetch(PDO::FETCH_ASSOC)) { ?>

<option value="<?= $e['id'] ?>">

<?= $e['full_name'] ?>

</option>

<?php } ?>

</select>

<div id="cart-items">

<div class="cart-row">

<select name="medicine_id[]" required>

<?php while($m = $medicines->fetch(PDO::FETCH_ASSOC)) { ?>

<option
value="<?= $m['id'] ?>"
data-price="<?= $m['price'] ?>">

<?= $m['name'] ?>
(<?= $m['quantity'] ?>)

</option>

<?php } ?>

</select>

<input type="number"
name="quantity[]"
placeholder="Количество"
min="1"
value="1"
required>

</div>

</div>

<button type="button"
onclick="addMedicine()">
+ Добавить лекарство
</button>

<h3 id="total">
Итого: 0 ₽
</h3>

<button type="submit"
name="save_sale">
Сохранить
</button>

<button type="button"
onclick="closeModal()">
Закрыть
</button>

</form>

</div>

</div>

<script>

function openModal(){
    document.getElementById('saleModal').style.display='flex';
}

function closeModal(){
    document.getElementById('saleModal').style.display='none';
}

function addMedicine(){

    let row = document.querySelector('.cart-row').cloneNode(true);

    document.getElementById('cart-items').appendChild(row);
}

const customerInput =
document.getElementById('customer_name');

customerInput.addEventListener('keyup', function(){

    let value = this.value;

    fetch('search_customer.php?search=' + value)

    .then(res => res.json())

    .then(data => {

        let suggestions =
        document.getElementById('suggestions');

        suggestions.innerHTML='';

        data.forEach(customer => {

            let div =
            document.createElement('div');

            div.classList.add('suggestion-item');

            div.innerHTML = customer.full_name;

            div.onclick = function(){

                customerInput.value =
                customer.full_name;

                document.getElementById(
                'customer_phone'
                ).value = customer.phone;

                suggestions.innerHTML='';
            }

            suggestions.appendChild(div);
        });
    });
});

</script>

</body>
</html>
