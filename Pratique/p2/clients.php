<?php
include 'config.php';

# AJOUT CLIENT
if(isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    if(empty($name) || empty($phone)){
        die("Заполните все поля");
    }

    $sql = "INSERT INTO customers(full_name, phone) VALUES(?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $phone]);

    header("Location: clients.php");
    exit;
}

# SUPPRESSION
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $check = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = ?");
    $check->execute([$id]);
    if($check->fetchColumn() > 0) {
        die("Невозможно удалить клиента с историей покупок");
    }
    
    $sql = "DELETE FROM customers WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    header("Location: clients.php");
    exit;
}

# RECHERCHE
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $sql = "SELECT * FROM customers WHERE full_name LIKE ? OR phone LIKE ? ORDER BY full_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$search, $search]);
    $customers = $stmt;
}
else{
    $customers = $pdo->query("SELECT * FROM customers ORDER BY full_name");
}

# HISTORIQUE CLIENT AJAX
if(isset($_GET['history'])){
    $id = (int)$_GET['history'];
    
    $sql = "
    SELECT 
        sales.id as sale_id,
        sales.sale_date,
        sales.total_price,
        medicines.name as medicine_name,
        sale_items.quantity
    FROM sales
    JOIN sale_items ON sales.id = sale_items.sale_id
    JOIN medicines ON sale_items.medicine_id = medicines.id
    WHERE sales.customer_id = ?
    ORDER BY sales.id DESC, sale_items.id ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    $sales = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $sale_id = $row['sale_id'];
        
        if(!isset($sales[$sale_id])){
            $sales[$sale_id] = [
                'date' => $row['sale_date'],
                'total' => $row['total_price'],
                'medicines' => []
            ];
        }
        
        $sales[$sale_id]['medicines'][] = $row['medicine_name'] . ' (' . $row['quantity'] . ' шт.)';
    }
    
    if(count($sales) > 0){
        echo "<table style='width:100%; border-collapse:collapse;'>";
        echo "<thead>";
        echo "<tr style='background:#4CAF50; color:white;'>";
        echo "<th style='padding:10px; text-align:left;'>Дата</th>";
        echo "<th style='padding:10px; text-align:left;'>Лекарства</th>";
        echo "<th style='padding:10px; text-align:left;'>Сумма</th>";
        echo "<tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach($sales as $sale){
            echo "<tr>";
            echo "<td style='padding:10px; border-bottom:1px solid #ddd; vertical-align:top;'>" . htmlspecialchars($sale['date']) . "</td>";
            echo "<td style='padding:10px; border-bottom:1px solid #ddd;'>";
            foreach($sale['medicines'] as $medicine){
                echo "• " . htmlspecialchars($medicine) . "<br>";
            }
            echo "</td>";
            echo "<td style='padding:10px; border-bottom:1px solid #ddd; font-weight:bold; color:#4CAF50;'>" . htmlspecialchars($sale['total']) . " ₽</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p style='text-align:center; color:#999; padding:20px;'>У этого клиента нет покупок</p>";
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Клиенты</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <!-- HEADER -->
    <div class="top-bar">
        <h1>Клиенты</h1>
        <div class="actions">
            <a href="index.php" class="back">Назад</a>
        </div>
    </div>

    <!-- RECHERCHE -->
    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Поиск клиента">
        <button type="submit">Поиск</button>
    </form>

    <!-- AJOUT CLIENT -->
    <div class="add-client-box">
        <h2>Добавить клиента</h2>
        <form method="POST">
            <input type="text" name="name" placeholder="Имя клиента" required>
            <input type="text" name="phone" placeholder="Телефон" required>
            <button type="submit" name="add">Добавить</button>
        </form>
    </div>

    <!-- TABLEAU DES CLIENTS -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя клиента</th>
                    <th>Телефон</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $customers->fetch(PDO::FETCH_ASSOC)) { ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td>
                        <a href="#" class="client-link" 
                           data-id="<?= htmlspecialchars($row['id']) ?>" 
                           data-name="<?= htmlspecialchars($row['full_name']) ?>" 
                           data-phone="<?= htmlspecialchars($row['phone']) ?>">
                            <?= htmlspecialchars($row['full_name']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td>
                        <a class="delete" href="?delete=<?= htmlspecialchars($row['id']) ?>" 
                           onclick="return confirm('Удалить клиента?')">Удалить</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL CLIENT (GARDÉ COMME AVANT) -->
<div class="modal" id="clientModal">
    <div class="modal-content">
        <h2 id="modal-name">Клиент</h2>
        <p><strong>Телефон :</strong> <span id="modal-phone"></span></p>
        
        <h3>История покупок</h3>
        <div id="purchase-history"></div>
        
        <button onclick="closeModal()">Закрыть</button>
    </div>
</div>

<script>
function closeModal(){
    document.getElementById('clientModal').style.display = 'none';
}

document.querySelectorAll('.client-link').forEach(link => {
    link.addEventListener('click', function(e){
        e.preventDefault();
        
        let id = this.dataset.id;
        let name = this.dataset.name;
        let phone = this.dataset.phone;
        
        document.getElementById('modal-name').innerHTML = htmlEscape(name);
        document.getElementById('modal-phone').innerHTML = htmlEscape(phone);
        
        document.getElementById('purchase-history').innerHTML = '<p>Загрузка...</p>';
        
        fetch('clients.php?history=' + id)
            .then(res => res.text())
            .then(data => {
                document.getElementById('purchase-history').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('purchase-history').innerHTML = '<p style="color:red;">Ошибка загрузки истории</p>';
                console.error('Erreur:', error);
            });
        
        document.getElementById('clientModal').style.display = 'flex';
    });
});

function htmlEscape(str){
    if(!str) return '';
    return str.replace(/[&<>]/g, function(m){
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}
</script>

</body>
</html>
