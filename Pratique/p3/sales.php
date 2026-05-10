<?php
include 'config.php';
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
$sqlCheck = "SELECT quantity FROM medicines WHERE id=?";
$stmtCheck = $pdo->prepare($sqlCheck);
$stmtCheck->execute([$medicine]);
$med = $stmtCheck->fetch(PDO::FETCH_ASSOC);
if($quantity > $med['quantity']){
die("Недостаточно товара на складе");
}
$sqlCustomer = "SELECT id FROM customers WHERE full_name=?";
$stmtCustomer = $pdo->prepare($sqlCustomer);
$stmtCustomer->execute([$customer_name]);
$customer = $stmtCustomer->fetch(PDO::FETCH_ASSOC);
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
$sql2 = "UPDATE medicines
SET quantity = quantity - ?
WHERE id=?";
$stmt2 = $pdo->prepare($sql2);
$stmt2->execute([$quantity, $medicine]);
header("Location: sales.php");
exit;
  }
