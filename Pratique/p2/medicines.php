<?php
include 'config.php';

# AJOUT
if(isset($_POST['add'])) {

    $name = $_POST['name'];
    $manufacturer = $_POST['manufacturer'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $expiration = $_POST['expiration'];
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;

    # EMPÊCHER QUANTITÉ NÉGATIVE
    if($quantity < 0){
        die("Количество не может быть отрицательным");
    }

    $sql = "INSERT INTO medicines
            (name, manufacturer, price,
            quantity, expiration_date, category_id)
            VALUES(?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $name,
        $manufacturer,
        $price,
        $quantity,
        $expiration,
        $category_id
    ]);

    header("Location: medicines.php");
    exit;
}

# SUPPRESSION
if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $sql = "DELETE FROM medicines
            WHERE id=?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$id]);

    header("Location: medicines.php");
    exit;
}

# MODIFICATION
if(isset($_POST['update'])) {

    $id = $_POST['id'];

    $name = $_POST['name'];
    $manufacturer = $_POST['manufacturer'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $expiration = $_POST['expiration'];
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;

    # EMPÊCHER QUANTITÉ NÉGATIVE
    if($quantity < 0){
        die("Количество не может быть отрицательным");
    }

    $sql = "UPDATE medicines
            SET
            name=?,
            manufacturer=?,
            price=?,
            quantity=?,
            expiration_date=?,
            category_id=?
            WHERE id=?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $name,
        $manufacturer,
        $price,
        $quantity,
        $expiration,
        $category_id,
        $id
    ]);

    header("Location: medicines.php");
    exit;
}

# RECHERCHE
if(isset($_GET['search']) && !empty($_GET['search'])) {

    $search = "%" . $_GET['search'] . "%";

    $sql = "SELECT m.*, c.name as category_name, c.description as category_description
            FROM medicines m
            LEFT JOIN categories c ON m.category_id = c.id
            WHERE m.name LIKE ?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$search]);

    $result = $stmt;
}
else {

    $result = $pdo->query("SELECT m.*, c.name as category_name, c.description as category_description
                           FROM medicines m
                           LEFT JOIN categories c ON m.category_id = c.id");
}

# Récupérer toutes les catégories pour les formulaires
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Лекарства</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="layout">
    <div class="sidebar">
        <h2>⚕️Аптека </h2>
        <a href="index.php" class="menu-link">Главная</a>
        <a href="medicines.php" class="menu-link">💊 Лекарства</a>
        <a href="employees.php" class="menu-link">🥼 Сотрудники</a>
        <a href="clients.php" class="menu-link">👥 Клиенты</a>
        <a href="sales.php" class="menu-link"> 🛒 Продажи</a>
    </div>

    <div class="content">
        <div class="page-content">
            <div class="content-header">
                <h1>Управление лекарствами</h1>
            </div>

            <!-- Section Recherche -->
            <div class="search-section">
                <form method="GET" class="search-form">
                    <input type="text"
                        name="search"
                        placeholder="Поиск лекарства по названию..."
                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <button type="submit">Поиск</button>
                    <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                        <a href="medicines.php" class="reset-btn">Сбросить</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Section Ajout -->
            <div class="add-section">
                <h2>➕ Добавить лекарство</h2>
                <form method="POST" class="add-form">
                    <input type="text"
                        name="name"
                        placeholder="Название *"
                        required>

                    <input type="text"
                        name="manufacturer"
                        placeholder="Производитель">

                    <input type="number"
                        step="0.01"
                        name="price"
                        placeholder="Цена *"
                        min="0"
                        required>

                    <input type="number"
                        name="quantity"
                        placeholder="Количество *"
                        min="0"
                        required>

                    <input type="date"
                        name="expiration">

                    <select name="category_id">
                        <option value="">Выберите категорию</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"
                                    title="<?= htmlspecialchars($category['description']) ?>">
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" name="add" class="add-btn">
                        Добавить
                    </button>
                </form>
            </div>

            <!-- Section Liste -->
            <div class="table-section">
                <h2>📋 Список лекарств</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название</th>
                                <th>Производитель</th>
                                <th>Цена</th>
                                <th>Количество</th>
                                <th>Срок годности</th>
                                <th>Категория</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $hasResults = false;
                            while($row = $result->fetch(PDO::FETCH_ASSOC)) { 
                                $hasResults = true;
                            ?>
                                <tr>
                                    <form method="POST" class="inline-form">
                                        <td data-label="ID">
                                            <?= $row['id'] ?>
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        </td>
                                        <td data-label="Название">
                                            <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
                                        </td>
                                        <td data-label="Производитель">
                                            <input type="text" name="manufacturer" value="<?= htmlspecialchars($row['manufacturer']) ?>">
                                        </td>
                                        <td data-label="Цена">
                                            <input type="number" step="0.01" name="price" value="<?= $row['price'] ?>" min="0" required>
                                        </td>
                                        <td data-label="Количество">
                                            <input type="number" name="quantity" value="<?= $row['quantity'] ?>" min="0" required>
                                        </td>
                                        <td data-label="Срок годности">
                                            <input type="date" name="expiration" value="<?= $row['expiration_date'] ?>">
                                        </td>
                                        <td data-label="Категория">
                                            <select name="category_id">
                                                <option value="">Без категории</option>
                                                <?php foreach($categories as $category): ?>
                                                    <option value="<?= $category['id'] ?>"
                                                        <?= ($row['category_id'] == $category['id']) ? 'selected' : '' ?>
                                                        title="<?= htmlspecialchars($category['description']) ?>">
                                                        <?= htmlspecialchars($category['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td data-label="Действия">
                                            <button type="submit" name="update" class="edit-btn">Изменить</button>
                                            <a class="delete" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Удалить лекарство?')">Удалить</a>
                                        </td>
                                    </form>
                                </tr>
                            <?php } ?>
                            <?php if(!$hasResults): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px;">
                                        📭 Лекарства не найдены
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
