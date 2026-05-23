<?php

include 'config.php';

# AJOUT
if(isset($_POST['add'])) {

    $name = $_POST['name'];
    $position = $_POST['position'];
    $phone = $_POST['phone'];
    $hire_date = $_POST['hire_date'];

    $sql = "INSERT INTO employees
            (full_name, position, phone, hire_date)
            VALUES(?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$name, $position, $phone, $hire_date]);
}

# SUPPRESSION
if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $sql = "DELETE FROM employees WHERE id=?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$id]);
}
# RECHERCHE
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $sql = "SELECT * FROM employees WHERE full_name LIKE ? OR position LIKE ? OR phone LIKE ? ORDER BY full_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$search, $search, $search]);
    $employees = $stmt;
    $hasResults = $stmt->rowCount() > 0;
}
else{
    $employees = $pdo->query("SELECT * FROM employees ORDER BY full_name");
    $hasResults = true;
}
# MODIFICATION
if(isset($_POST['update'])) {

    $id = $_POST['id'];

    $name = $_POST['name'];
    $position = $_POST['position'];
    $phone = $_POST['phone'];
    $hire_date = $_POST['hire_date'];

    $sql = "UPDATE employees
            SET
            full_name=?,
            position=?,
            phone=?,
            hire_date=?
            WHERE id=?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $name,
        $position,
        $phone,
        $hire_date,
        $id
    ]);
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>

<meta charset="UTF-8">
<title>Сотрудники</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

<h1>🥼 Сотрудники</h1>

<a href="index.php" 
   class="back"
   aria-label="Назад">
   Назад
</a>
    
 <!-- RECHERCHE -->
    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Поиск сотрудников" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button type="submit">Поиск</button>
        <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                        <a href="employees.php" class="reset-btn">Сбросить</a>
        <?php endif; ?>
    </form>
    
<h2>➕ Добавить сотрудника</h2>

<form method="POST">

<input type="text"
name="name"
placeholder="Имя"
required>

<input type="text"
name="position"
placeholder="Должность"
required>

<input type="text"
name="phone"
placeholder="Телефон"
required>

<input type="date"
name="hire_date"
placeholder="Дата найма"
required>

<button type="submit"
name="add">
Добавить
</button>

</form>
<div class="table-wrapper">
    <h2>📋 Список сотрудников </h2>
<table>

<tr>
<th>ID</th>
<th>Имя</th>
<th>Должность</th>
<th>Телефон</th>
<th>Дата найма</th>
<th>Действия</th>
</tr>

<?php while($row = $employees->fetch(PDO::FETCH_ASSOC)) { ?>

<tr>

<form method="POST">

<td>

<?= $row['id'] ?>

<input type="hidden"
name="id"
value="<?= $row['id'] ?>">

</td>

<td>

<input type="text"
name="name"
value="<?= $row['full_name'] ?>">

</td>

<td>

<input type="text"
name="position"
value="<?= $row['position'] ?>">

</td>

<td>

<input type="text"
name="phone"
value="<?= $row['phone'] ?>">

</td>

<td>

<input type="date"
name="hire_date"
value="<?= $row['hire_date'] ?>">

</td>

<td>

<button type="submit"
name="update">
Изменить
</button>

<a class="delete" href="?delete=<?= htmlspecialchars($row['id']) ?>" 
onclick="return confirm('Удалить сотрудника?')">Удалить</a>


</td>

</form>

</tr>

<?php } ?>
<?php if(!$hasResults): ?>
     <tr>
        <td colspan="8" style="text-align: center; padding: 40px;"> сотрудник не найден </td>
     </tr>
<?php endif; ?>

</table>
</div>
</div>

</body>
</html>
