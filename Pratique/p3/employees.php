<?php
include 'config.php';
# AJOUT
if(isset($_POST['add'])) {
$name = trim($_POST['name']);
$position = trim($_POST['position']);
$phone = trim($_POST['phone']);
if(empty($name) || empty($position) || empty($phone)){
die("Заполните все обязательные поля");
}
$sql = "INSERT INTO employees
            (full_name, position, phone)
            VALUES(?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$name, $position, $phone]);
header("Location: employees.php");
exit;
}
# SUPPRESSION
if(isset($_GET['delete'])) {
$id = $_GET['delete'];
$sql = "DELETE FROM employees WHERE id=?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
header("Location: employees.php");
exit;
}
# MODIFICATION
if(isset($_POST['update'])) {
$id = $_POST['id'];
$name = trim($_POST['name']);
$position = trim($_POST['position']);
$phone = trim($_POST['phone']);
if(empty($name) || empty($position) || empty($phone)){
die("Заполните все обязательные поля");
}
$sql = "UPDATE employees
            SET
            full_name=?,
            position=?,
            phone=?
            WHERE id=?";
$stmt = $pdo->prepare($sql);
$stmt->execute([
$name,
$position,
$phone,
$id
]);
header("Location: employees.php");
exit;
}
# RECHERCHE
if(isset($_GET['search'])){
$search = "%" . $_GET['search'] . "%";
$sql = "SELECT * FROM employees
WHERE full_name LIKE ?
OR position LIKE ?
OR phone LIKE ?";
$stmt = $pdo->prepare($sql);
$result = $stmt;
} else {
}
$stmt->execute([$search, $search, $search]);
$result = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
?>
