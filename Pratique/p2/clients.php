<?php

include 'config.php';

# AJOUT CLIENT
if(isset($_POST['add'])) {

    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    if(empty($name) || empty($phone)){
        die("Заполните все поля");
    }

    $sql = "
    INSERT INTO customers(full_name, phone)
    VALUES(?, ?)
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $name,
        $phone
    ]);

    header("Location: clients.php");
    exit;
}

# SUPPRESSION
if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $sql = "
    DELETE FROM customers
    WHERE id=?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$id]);

    header("Location: clients.php");
    exit;
}

# RECHERCHE
if(isset($_GET['search']) && !empty($_GET['search'])) {

    $search = "%" . $_GET['search'] . "%";

    $sql = "
    SELECT *
    FROM customers
    WHERE full_name LIKE ?
    OR phone LIKE ?
    ORDER BY full_name
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $search,
        $search
    ]);

    $customers = $stmt;
}
else{

    $customers = $pdo->query("
    SELECT *
    FROM customers
    ORDER BY full_name
    ");
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>

<meta charset="UTF-8">

<title>Клиенты</title>

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<link rel="stylesheet"
href="style.css">

</head>

<body>

<div class="container">

<!-- HEADER -->

<div class="top-bar">

<h1>Клиенты</h1>

<div class="actions">

<a href="index.php"
class="back">

Назад

</a>

</div>

</div>

<!-- RECHERCHE -->

<form method="GET" class="search-form">

<input type="text"
name="search"
placeholder="Поиск клиента">

<button type="submit">

Поиск

</button>

</form>

<!-- AJOUT CLIENT -->

<div class="add-client-box">

<h2>Добавить клиента</h2>

<form method="POST">

<input type="text"
name="name"
placeholder="Имя клиента"
required>

<input type="text"
name="phone"
placeholder="Телефон"
required>

<button type="submit"
name="add">

Добавить

</button>

</form>

</div>

<!-- TABLEAU -->

<div class="table-wrapper">

<table>

<tr>

<th>ID</th>
<th>Имя клиента</th>
<th>Телефон</th>
<th>Действие</th>

</tr>

<?php while($row = $customers->fetch(PDO::FETCH_ASSOC)) { ?>

<tr>

<td>

<?= $row['id'] ?>

</td>

<td>

<a href="#"
class="client-link"

data-id="<?= $row['id'] ?>"

data-name="<?= $row['full_name'] ?>"

data-phone="<?= $row['phone'] ?>">

<?= $row['full_name'] ?>

</a>

</td>

<td>

<?= $row['phone'] ?>

</td>

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

<!-- MODAL CLIENT -->

<div class="modal"
id="clientModal">

<div class="modal-content">

<h2 id="modal-name">

Клиент

</h2>

<p>

<b>Телефон :</b>

<span id="modal-phone"></span>

</p>

<h3>

История покупок

</h3>

<div id="purchase-history">

</div>

<button onclick="closeModal()">

Закрыть

</button>

</div>

</div>

<script>

function closeModal(){

    document.getElementById(
    'clientModal'
    ).style.display='none';
}

document.querySelectorAll(
'.client-link'
).forEach(link => {

    link.addEventListener(
    'click',
    function(e){

        e.preventDefault();

        let id =
        this.dataset.id;

        let name =
        this.dataset.name;

        let phone =
        this.dataset.phone;

        document.getElementById(
        'modal-name'
        ).innerHTML = name;

        document.getElementById(
        'modal-phone'
        ).innerHTML = phone;

        fetch(
        'clients.php?history=' + id
        )

        .then(res => res.text())

        .then(data => {

            document.getElementById(
            'purchase-history'
            ).innerHTML = data;
        });

        document.getElementById(
        'clientModal'
        ).style.display='flex';
    });
});

</script>

</body>
</html>

<?php

# HISTORIQUE CLIENT AJAX
if(isset($_GET['history'])){

    $id = $_GET['history'];

    $sql = "

    SELECT sales.sale_date,
           sales.total_price

    FROM sales

    WHERE sales.customer_id=?

    ORDER BY sales.id DESC

    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$id]);

    echo "<table style='margin-top:10px;'>";

    echo "
    <tr>
    <th>Дата</th>
    <th>Сумма</th>
    </tr>
    ";

    while($sale = $stmt->fetch(PDO::FETCH_ASSOC)){

        echo "<tr>";

        echo "<td>"
        . $sale['sale_date']
        . "</td>";

        echo "<td>"
        . $sale['total_price']
        . " ₽</td>";

        echo "</tr>";
    }

    echo "</table>";

    exit;
}

?>
