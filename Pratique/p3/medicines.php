<?php
include 'config.php';
# AJOUT
if(isset($_POST['add'])) {
$name = trim($_POST['name']);
$manufacturer = trim($_POST['manufacturer']);
$price = trim($_POST['price']);
$quantity = trim($_POST['quantity']);
$expiration = $_POST['expiration'];
if(empty($name) || $price === '' || $quantity === ''){
die("Заполните обязательные поля");
}
if($quantity < 0){
die("Количество не может быть отрицательным");
}
$sql = "INSERT INTO medicines
(name, manufacturer, price,
quantity, expiration_date)
VALUES(?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
$name,
$manufacturer,
$price,
$quantity,
$expiration
]);
header("Location: medicines.php");
exit;
}
# SUPPRESSION
if(isset($_GET['delete'])) {
$id = $_GET['delete'];
$sql = "DELETE FROM medicines WHERE id=?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
header("Location: medicines.php");
exit;
}
# MODIFICATION
if(isset($_POST['update'])) {
$id = $_POST['id'];
$name = trim($_POST['name']);
$manufacturer = trim($_POST['manufacturer']);
$price = trim($_POST['price']);
$quantity = trim($_POST['quantity']);
$expiration = $_POST['expiration'];
if($quantity < 0){
die("Количество не может быть отрицательным");
}
$sql = "UPDATE medicines
            SET
            name=?,
            manufacturer=?,
            price=?,
            quantity=?,
            expiration_date=?
            WHERE id=?";
$stmt = $pdo->prepare($sql);
$stmt->execute([
$name,
$manufacturer,
$price,
$quantity,
$expiration,
$id
]);
header("Location: medicines.php");
exit;
}
# RECHERCHE
if(isset($_GET['search'])) {
$search = "%" . $_GET['search'] . "%";
$sql = "SELECT * FROM medicines
WHERE name LIKE ?
OR manufacturer LIKE ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$search, $search]);
$result = $stmt;
}
else {
$result = $pdo->query("SELECT * FROM medicines ORDER BY id DESC");
}
?>
