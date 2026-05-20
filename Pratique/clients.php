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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Клиенты</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        h1 {
            text-align: center;
            margin-bottom: 30px;
            margin-top: 0;
            padding-top: 20px;
        }
        
        .purchase-item {
            border: 1px solid #ddd;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
        }
        
        .purchase-header {
            background-color: #f5f5f5;
            padding: 8px;
            margin: -10px -10px 10px -10px;
            border-radius: 5px 5px 0 0;
            font-weight: bold;
        }
        
        .medicine-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        
        .medicine-table th,
        .medicine-table td {
            padding: 5px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .medicine-table th {
            background-color: #f9f9f9;
        }
        
        .total-price {
            margin-top: 10px;
            text-align: right;
            font-weight: bold;
            color: #28a745;
        }
    </style>
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

    <!-- TABLEAU -->
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
                    <td class="action-buttons">
                        <a class="delete" href="?delete=<?= htmlspecialchars($row['id']) ?>" 
                           onclick="return confirm('Удалить клиента?')">Удалить</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL CLIENT -->
<div class="modal" id="clientModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div class="modal-content" style="background:white; padding:20px; border-radius:8px; max-width:800px; width:90%; max-height:80vh; overflow-y:auto;">
        <h2 id="modal-name">Клиент</h2>
        <p><b>Телефон :</b> <span id="modal-phone"></span></p>
        
        <h3>История покупок</h3>
        <div id="purchase-history" style="max-height:500px; overflow-y:auto;"></div>
        
        <button onclick="closeModal()" style="margin-top:20px; padding:8px 16px; background-color:#6c757d; color:white; border:none; border-radius:4px; cursor:pointer;">
            Закрыть
        </button>
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
        
        // Afficher le chargement
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

<?php
# HISTORIQUE CLIENT AJAX (MODIFIÉ POUR AFFICHER LES DÉTAILS)
if(isset($_GET['history'])){
    $id = (int)$_GET['history'];
    
    // Récupérer toutes les ventes du client avec les détails des médicaments
    $sql = "
    SELECT 
        sales.id as sale_id,
        sales.sale_date,
        sales.total_price,
        sale_items.medicine_id,
        sale_items.quantity,
        sale_items.unit_price,
        medicines.name as medicine_name,
        medicines.price as medicine_price
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
                'items' => []
            ];
        }
        
        $sales[$sale_id]['items'][] = [
            'name' => $row['medicine_name'],
            'quantity' => $row['quantity'],
            'price' => $row['unit_price'],
            'total' => $row['quantity'] * $row['unit_price']
        ];
    }
    
    if(count($sales) > 0){
        echo "<div class='purchases-list'>";
        
        foreach($sales as $sale){
            echo "<div class='purchase-item'>";
            echo "<div class='purchase-header'>";
            echo "📅 " . htmlspecialchars($sale['date']);
            echo "</div>";
            
            echo "<table class='medicine-table'>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>Лекарство</th>";
            echo "<th>Кол-во</th>";
            echo "<th>Цена</th>";
            echo "<th>Сумма</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            
            foreach($sale['items'] as $item){
                echo "<tr>";
                echo "<td>" . htmlspecialchars($item['name']) . "</td>";
                echo "<td>" . $item['quantity'] . "</td>";
                echo "<td>" . $item['price'] . " ₽</td>";
                echo "<td>" . $item['total'] . " ₽</td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "</table>";
            echo "<div class='total-price'>Общая сумма: " . htmlspecialchars($sale['total']) . " ₽</div>";
            echo "</div>";
        }
        
        echo "</div>";
    } else {
        echo "<p style='text-align:center; color:#666;'>Нет истории покупок</p>";
    }
    
    exit;
}
?>
