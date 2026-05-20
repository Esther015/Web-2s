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

# HISTORIQUE CLIENT AJAX - DOIT ÊTRE AVANT LE HTML
if(isset($_GET['history'])){
    $id = (int)$_GET['history'];
    
    // Récupérer uniquement les ventes de CE client
    $sql = "
    SELECT sales.id, sales.sale_date, sales.total_price
    FROM sales
    WHERE sales.customer_id = ?
    ORDER BY sales.id DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($sales) > 0){
        echo "<table style='width:100%; border-collapse:collapse;'>";
        echo "<thead>";
        echo "<tr style='background:#f5f5f5;'>";
        echo "<th style='padding:8px; text-align:left;'>Дата</th>";
        echo "<th style='padding:8px; text-align:left;'>Сумма</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach($sales as $sale){
            echo "<tr>";
            echo "<td style='padding:8px; border-bottom:1px solid #ddd;'>" . htmlspecialchars($sale['sale_date']) . "</td>";
            echo "<td style='padding:8px; border-bottom:1px solid #ddd;'>" . htmlspecialchars($sale['total_price']) . " ₽</td>";
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
    <style>
        h1 {
            text-align: center;
            margin-bottom: 30px;
            margin-top: 0;
            padding-top: 20px;
        }
        
        .client-link {
            color: #007bff;
            text-decoration: none;
            cursor: pointer;
        }
        
        .client-link:hover {
            text-decoration: underline;
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

    <!-- TABLEAU DES CLIENTS -->
    <div class="table-wrapper">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="padding:8px; text-align:left; background:#f5f5f5;">ID</th>
                    <th style="padding:8px; text-align:left; background:#f5f5f5;">Имя клиента</th>
                    <th style="padding:8px; text-align:left; background:#f5f5f5;">Телефон</th>
                    <th style="padding:8px; text-align:left; background:#f5f5f5;">Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $customers->fetch(PDO::FETCH_ASSOC)) { ?>
                <tr>
                    <td style="padding:8px; border-bottom:1px solid #ddd;"><?= htmlspecialchars($row['id']) ?></td>
                    <td style="padding:8px; border-bottom:1px solid #ddd;">
                        <a href="#" class="client-link" 
                           data-id="<?= htmlspecialchars($row['id']) ?>" 
                           data-name="<?= htmlspecialchars($row['full_name']) ?>" 
                           data-phone="<?= htmlspecialchars($row['phone']) ?>">
                            <?= htmlspecialchars($row['full_name']) ?>
                        </a>
                    </td>
                    <td style="padding:8px; border-bottom:1px solid #ddd;"><?= htmlspecialchars($row['phone']) ?></td>
                    <td style="padding:8px; border-bottom:1px solid #ddd;">
                        <a class="delete" href="?delete=<?= htmlspecialchars($row['id']) ?>" 
                           onclick="return confirm('Удалить клиента?')" style="color:red; text-decoration:none;">
                            Удалить
                        </a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL CLIENT -->
<div class="modal" id="clientModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div class="modal-content" style="background:white; padding:20px; border-radius:8px; max-width:600px; width:90%;">
        <h2 id="modal-name">Клиент</h2>
        <p><strong>Телефон :</strong> <span id="modal-phone"></span></p>
        
        <h3>История покупок</h3>
        <div id="purchase-history" style="margin-top:10px; max-height:400px; overflow-y:auto;">
            <!-- Les achats du client seront chargés ici -->
        </div>
        
        <button onclick="closeModal()" style="margin-top:20px; padding:8px 16px; background:#6c757d; color:white; border:none; border-radius:4px; cursor:pointer;">
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
        document.getElementById('purchase-history').innerHTML = '<p style="text-align:center;">Загрузка...</p>';
        
        // Récupérer l'historique des achats du client
        fetch('clients.php?history=' + id)
            .then(res => res.text())
            .then(data => {
                document.getElementById('purchase-history').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('purchase-history').innerHTML = '<p style="color:red; text-align:center;">Ошибка загрузки</p>';
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
