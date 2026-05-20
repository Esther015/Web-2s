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
    
    // Vérifier si le client a des ventes avant de supprimer
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

# HISTORIQUE CLIENT AJAX - AVEC DÉTAILS DES MÉDICAMENTS
if(isset($_GET['history'])){
    $id = (int)$_GET['history'];
    
    // Récupérer les ventes avec les médicaments
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
        echo "<table class='history-table'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Дата</th>";
        echo "<th>Лекарства</th>";
        echo "<th>Сумма</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach($sales as $sale){
            echo "<tr>";
            echo "<td>" . htmlspecialchars($sale['date']) . "</td>";
            echo "<td>";
            foreach($sale['medicines'] as $medicine){
                echo "• " . htmlspecialchars($medicine) . "<br>";
            }
            echo "</td>";
            echo "<td class='total-price'>" . htmlspecialchars($sale['total']) . " ₽</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p class='no-purchases'>У этого клиента нет покупок</p>";
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
    <style>
        /* Styles spécifiques pour cette page */
        .customers-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .customers-table th {
            padding: 8px;
            text-align: left;
            background: #f5f5f5;
            border-bottom: 2px solid #ddd;
        }
        
        .customers-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        
        .client-link {
            color: #007bff;
            text-decoration: none;
            cursor: pointer;
        }
        
        .client-link:hover {
            text-decoration: underline;
        }
        
        .delete {
            color: #dc3545;
            text-decoration: none;
        }
        
        .delete:hover {
            text-decoration: underline;
        }
        
        /* Styles pour l'historique */
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .history-table th {
            background: #4CAF50;
            color: white;
            padding: 10px;
            text-align: left;
        }
        
        .history-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        
        .history-table .total-price {
            font-weight: bold;
            color: #4CAF50;
        }
        
        .no-purchases {
            text-align: center;
            color: #999;
            padding: 20px;
        }
        
        /* Header styles */
        .top-bar {
            margin-bottom: 20px;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            margin-top: 0;
            padding-top: 20px;
        }
        
        .actions {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .back {
            background-color: #6c757d;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .back:hover {
            background-color: #5a6268;
        }
        
        .search-form {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .search-form input {
            padding: 8px;
            width: 250px;
        }
        
        .search-form button {
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        
        .add-client-box {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .add-client-box h2 {
            margin-top: 0;
        }
        
        .add-client-box input {
            padding: 8px;
            margin-right: 10px;
        }
        
        .add-client-box button {
            padding: 8px 16px;
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
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
        <input type="text" name="search" placeholder="Поиск клиента" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
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
        <table class="customers-table">
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

<!-- MODAL CLIENT -->
<div class="modal" id="clientModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div class="modal-content" style="background:white; padding:20px; border-radius:8px; max-width:700px; width:90%; max-height:80vh; overflow-y:auto;">
        <h2 id="modal-name" style="margin-top:0; color:#333;">Клиент</h2>
        <p style="margin-bottom:20px;"><strong>Телефон :</strong> <span id="modal-phone"></span></p>
        
        <h3 style="color:#4CAF50;">История покупок</h3>
        <div id="purchase-history" style="margin-top:15px;">
            <!-- Les achats du client seront chargés ici -->
        </div>
        
        <button onclick="closeModal()" style="margin-top:20px; padding:10px 20px; background:#6c757d; color:white; border:none; border-radius:4px; cursor:pointer; font-size:14px;">
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
        document.getElementById('purchase-history').innerHTML = '<p style="text-align:center; color:#999;">Загрузка...</p>';
        
        // Récupérer l'historique des achats du client
        fetch('clients.php?history=' + id)
            .then(res => res.text())
            .then(data => {
                document.getElementById('purchase-history').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('purchase-history').innerHTML = '<p style="color:red; text-align:center;">Ошибка загрузки истории</p>';
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
