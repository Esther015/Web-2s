<!-- sales.php -->

<?php

include 'config.php';

# VENTE SELECTIONNEE
$selectedSale = null;

if(isset($_GET['sale'])) {
    $selectedSale = $_GET['sale'];
}

# REQUETE VENTES
$sql = "SELECT sales.*, customers.full_name
        FROM sales
        JOIN customers
        ON sales.customer_id = customers.id
        ORDER BY sales.id DESC";

$stmt = $pdo->query($sql);

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

<a href="clients.php" class="back">
Назад
</a>

<div class="table-wrapper">

<table>

<tr>
<th>ID</th>
<th>Client</th>
<th>Produit</th>
<th>Montant</th>
<th>Date</th>
</tr>

<?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>

<tr
id="sale<?= $row['id'] ?>"
class="<?= ($row['id'] == $selectedSale) ? 'highlight' : '' ?>">

<td>
<?= $row['id'] ?>
</td>

<td>
<?= $row['full_name'] ?>
</td>

<td>
<?= $row['product_name'] ?>
</td>

<td>
<?= $row['amount'] ?>
</td>

<td>
<?= $row['created_at'] ?>
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
    document.getElementById("sale<?= $selectedSale ?>");

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
