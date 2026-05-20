<?php
include 'config.php';

# AJAX CLIENT SEARCH
if(isset($_GET['ajax'])){
    $search = '%' . $_GET['search'] . '%';
    
    $sql = "SELECT * FROM customers WHERE full_name LIKE ? LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$search]);
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

# AJAX POUR RÉCUPÉRER LES DÉTAILS D'UNE VENTE (MODIFICATION)
if(isset($_GET['get_sale'])){
    $sale_id = (int)$_GET['get_sale'];
    
    # Récupérer la vente
    $sqlSale = "SELECT sales.*, customers.full_name AS customer_name, customers.phone AS customer_phone
                FROM sales
                JOIN customers ON sales.customer_id = customers.id
                WHERE sales.id = ?";
    $stmtSale = $pdo->prepare($sqlSale);
    $stmtSale->execute([$sale_id]);
    $sale = $stmtSale->fetch(PDO::FETCH_ASSOC);
    
    # Récupérer les articles
    $sqlItems = "SELECT sale_items.*, medicines.name 
                 FROM sale_items
                 JOIN medicines ON sale_items.medicine_id = medicines.id
                 WHERE sale_items.sale_id = ?";
    $stmtItems = $pdo->prepare($sqlItems);
    $stmtItems->execute([$sale_id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['sale' => $sale, 'items' => $items]);
    exit;
}

# AJOUT VENTE
if(isset($_POST['save_sale'])) {
    try {
        $pdo->beginTransaction();
        
        $customer_name = trim($_POST['customer_name']);
        $customer_phone = trim($_POST['customer_phone']);
        $employee = $_POST['employee'];
        $medicine_ids = $_POST['medicine_id'];
        $quantities = $_POST['quantity'];
        
        if(empty($customer_name)){
            throw new Exception("Введите имя клиента");
        }
        
        # GESTION CLIENT
        $sqlCustomer = "SELECT * FROM customers WHERE full_name=?";
        $stmtCustomer = $pdo->prepare($sqlCustomer);
        $stmtCustomer->execute([$customer_name]);
        $customer = $stmtCustomer->fetch(PDO::FETCH_ASSOC);
        
        if($customer){
            $customer_id = $customer['id'];
        } else {
            $sqlInsertCustomer = "INSERT INTO customers(full_name, phone) VALUES(?, ?)";
            $stmtInsert = $pdo->prepare($sqlInsertCustomer);
            $stmtInsert->execute([$customer_name, $customer_phone]);
            $customer_id = $pdo->lastInsertId();
        }
        
        # VÉRIFICATION STOCK
        $total = 0;
        $items = [];
        
        foreach($medicine_ids as $index => $med_id){
            $qty = (int)$quantities[$index];
            
            if($qty <= 0){
                throw new Exception("Неверное количество товара");
            }
            
            $sqlMed = "SELECT * FROM medicines WHERE id=?";
            $stmtMed = $pdo->prepare($sqlMed);
            $stmtMed->execute([$med_id]);
            $med = $stmtMed->fetch(PDO::FETCH_ASSOC);
            
            if(!$med){
                throw new Exception("Лекарство не найдено");
            }
            
            if($qty > $med['quantity']){
                throw new Exception("Недостаточно товара: " . htmlspecialchars($med['name']));
            }
            
            $total += $med['price'] * $qty;
            $items[] = ['med' => $med, 'qty' => $qty];
        }
        
        # CRÉER VENTE
        $sqlSale = "INSERT INTO sales (customer_id, employee_id, total_price, sale_date) VALUES(?, ?, ?, NOW())";
        $stmtSale = $pdo->prepare($sqlSale);
        $stmtSale->execute([$customer_id, $employee, $total]);
        $sale_id = $pdo->lastInsertId();
        
        # INSÉRER ITEMS ET METTRE À JOUR STOCK
        foreach($items as $item){
            $med = $item['med'];
            $qty = $item['qty'];
            
            $sqlItem = "INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price) VALUES(?, ?, ?, ?)";
            $stmtItem = $pdo->prepare($sqlItem);
            $stmtItem->execute([$sale_id, $med['id'], $qty, $med['price']]);
            
            $sqlStock = "UPDATE medicines SET quantity = quantity - ? WHERE id=?";
            $stmtStock = $pdo->prepare($sqlStock);
            $stmtStock->execute([$qty, $med['id']]);
        }
        
        $pdo->commit();
        header("Location: sales.php");
        exit;
        
    } catch(Exception $e) {
        $pdo->rollBack();
        die("Ошибка: " . htmlspecialchars($e->getMessage()));
    }
}

# MODIFICATION VENTE
if(isset($_POST['update_sale'])) {
    try {
        $pdo->beginTransaction();
        
        $sale_id = $_POST['sale_id'];
        $customer_name = trim($_POST['customer_name']);
        $customer_phone = trim($_POST['customer_phone']);
        $employee = $_POST['employee'];
        $medicine_ids = $_POST['medicine_id'];
        $quantities = $_POST['quantity'];
        
        if(empty($customer_name)){
            throw new Exception("Введите имя клиента");
        }
        
        # RÉCUPÉRER L'ANCIENNE VENTE POUR RESTAURER LE STOCK
        $sqlOldItems = "SELECT * FROM sale_items WHERE sale_id=?";
        $stmtOldItems = $pdo->prepare($sqlOldItems);
        $stmtOldItems->execute([$sale_id]);
        $oldItems = $stmtOldItems->fetchAll(PDO::FETCH_ASSOC);
        
        # RESTAURER L'ANCIEN STOCK
        foreach($oldItems as $oldItem){
            $sqlRestore = "UPDATE medicines SET quantity = quantity + ? WHERE id=?";
            $stmtRestore = $pdo->prepare($sqlRestore);
            $stmtRestore->execute([$oldItem['quantity'], $oldItem['medicine_id']]);
        }
        
        # SUPPRIMER LES ANCIENS ITEMS
        $sqlDeleteItems = "DELETE FROM sale_items WHERE sale_id=?";
        $stmtDeleteItems = $pdo->prepare($sqlDeleteItems);
        $stmtDeleteItems->execute([$sale_id]);
        
        # GESTION CLIENT
        $sqlCustomer = "SELECT * FROM customers WHERE full_name=?";
        $stmtCustomer = $pdo->prepare($sqlCustomer);
        $stmtCustomer->execute([$customer_name]);
        $customer = $stmtCustomer->fetch(PDO::FETCH_ASSOC);
        
        if($customer){
            $customer_id = $customer['id'];
        } else {
            $sqlInsertCustomer = "INSERT INTO customers(full_name, phone) VALUES(?, ?)";
            $stmtInsert = $pdo->prepare($sqlInsertCustomer);
            $stmtInsert->execute([$customer_name, $customer_phone]);
            $customer_id = $pdo->lastInsertId();
        }
        
        # VÉRIFICATION STOCK ET CALCUL
        $total = 0;
        $items = [];
        
        foreach($medicine_ids as $index => $med_id){
            $qty = (int)$quantities[$index];
            
            if($qty <= 0){
                throw new Exception("Неверное количество товара");
            }
            
            $sqlMed = "SELECT * FROM medicines WHERE id=?";
            $stmtMed = $pdo->prepare($sqlMed);
            $stmtMed->execute([$med_id]);
            $med = $stmtMed->fetch(PDO::FETCH_ASSOC);
            
            if(!$med){
                throw new Exception("Лекарство не найдено");
            }
            
            if($qty > $med['quantity']){
                throw new Exception("Недостаточно товара: " . htmlspecialchars($med['name']));
            }
            
            $total += $med['price'] * $qty;
            $items[] = ['med' => $med, 'qty' => $qty];
        }
        
        # METTRE À JOUR LA VENTE
        $sqlSale = "UPDATE sales SET customer_id=?, employee_id=?, total_price=? WHERE id=?";
        $stmtSale = $pdo->prepare($sqlSale);
        $stmtSale->execute([$customer_id, $employee, $total, $sale_id]);
        
        # INSÉRER LES NOUVEAUX ITEMS ET METTRE À JOUR LE STOCK
        foreach($items as $item){
            $med = $item['med'];
            $qty = $item['qty'];
            
            $sqlItem = "INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price) VALUES(?, ?, ?, ?)";
            $stmtItem = $pdo->prepare($sqlItem);
            $stmtItem->execute([$sale_id, $med['id'], $qty, $med['price']]);
            
            $sqlStock = "UPDATE medicines SET quantity = quantity - ? WHERE id=?";
            $stmtStock = $pdo->prepare($sqlStock);
            $stmtStock->execute([$qty, $med['id']]);
        }
        
        $pdo->commit();
        header("Location: sales.php");
        exit;
        
    } catch(Exception $e) {
        $pdo->rollBack();
        die("Ошибка при обновлении: " . htmlspecialchars($e->getMessage()));
    }
}

# SUPPRESSION
if(isset($_GET['delete'])){
    try {
        $pdo->beginTransaction();
        
        $id = (int)$_GET['delete'];
        
        $sqlItems = "SELECT * FROM sale_items WHERE sale_id=?";
        $stmtItems = $pdo->prepare($sqlItems);
        $stmtItems->execute([$id]);
        
        while($item = $stmtItems->fetch(PDO::FETCH_ASSOC)){
            $sqlRestore = "UPDATE medicines SET quantity = quantity + ? WHERE id=?";
            $stmtRestore = $pdo->prepare($sqlRestore);
            $stmtRestore->execute([$item['quantity'], $item['medicine_id']]);
        }
        
        $sqlDelete = "DELETE FROM sales WHERE id=?";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute([$id]);
        
        $pdo->commit();
        header("Location: sales.php");
        exit;
        
    } catch(Exception $e) {
        $pdo->rollBack();
        die("Ошибка при удалении: " . htmlspecialchars($e->getMessage()));
    }
}

# AFFICHAGE VENTES AVEC LISTE DES MÉDICAMENTS
$sql = "SELECT sales.*, 
        customers.full_name AS customer, 
        employees.full_name AS employee,
        GROUP_CONCAT(CONCAT(medicines.name, ' (', sale_items.quantity, ' шт)') SEPARATOR ', ') AS medicines_list
        FROM sales
        JOIN customers ON sales.customer_id = customers.id
        JOIN employees ON sales.employee_id = employees.id
        JOIN sale_items ON sales.id = sale_items.sale_id
        JOIN medicines ON sale_items.medicine_id = medicines.id
        GROUP BY sales.id
        ORDER BY sales.id DESC";
$result = $pdo->query($sql);

# MÉDICAMENTS
$medicines = $pdo->query("SELECT * FROM medicines ORDER BY name");

# EMPLOYÉS
$employees = $pdo->query("SELECT * FROM employees ORDER BY full_name");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Продажи</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        .suggestion-item {
            cursor: pointer;
            padding: 8px;
            border: 1px solid #ddd;
            background: white;
        }
        .suggestion-item:hover {
            background: #f0f0f0;
        }
        .cart-row {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #eee;
        }
        .delete-row {
            margin-left: 10px;
            background: #ff4444;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .edit {
            background: #ffc107;
            color: #000;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            margin-right: 5px;
        }
        .delete {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
        }
        .medicines-list {
            max-width: 300px;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h1>Продажи</h1>
        <div class="actions">
            <a href="index.php" class="back">Назад</a>
            <button onclick="openModal()" class="add-btn">+ Новая продажа</button>
        </div>
    </div>
    
    <div class="table-wrapper">
        <table border="1" cellpadding="10" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Клиент</th>
                    <th>Сотрудник</th>
                    <th>Лекарства</th>
                    <th>Сумма</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['customer']) ?></td>
                    <td><?= htmlspecialchars($row['employee']) ?></td>
                    <td class="medicines-list"><?= htmlspecialchars($row['medicines_list']) ?></td>
                    <td><?= htmlspecialchars($row['total_price']) ?> ₽</td>
                    <td><?= htmlspecialchars($row['sale_date']) ?></td>
                    <td>
                        <a class="edit" href="javascript:void(0)" onclick="editSale(<?= $row['id'] ?>)">Редактировать</a>
                        <a class="delete" href="?delete=<?= htmlspecialchars($row['id']) ?>" 
                           onclick="return confirm('Удалить эту продажу?')">Удалить</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL AJOUT/MODIFICATION -->
<div class="modal" id="saleModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div class="modal-content" style="background:white; padding:20px; border-radius:8px; max-width:600px; width:90%; max-height:80vh; overflow-y:auto;">
        <h2 id="modalTitle">Новая продажа</h2>
        
        <form method="POST" id="saleForm">
            <input type="hidden" name="sale_id" id="sale_id">
            
            <div style="position:relative;">
                <input type="text"
                       name="customer_name"
                       id="customer_name"
                       placeholder="Имя клиента"
                       autocomplete="off"
                       required
                       style="width:100%; padding:8px;">
                <div id="suggestions" style="position:absolute; width:100%; z-index:1001;"></div>
            </div>
            
            <input type="text"
                   name="customer_phone"
                   id="customer_phone"
                   placeholder="Телефон"
                   style="width:100%; padding:8px; margin-top:10px;">
            
            <select name="employee" required style="width:100%; padding:8px; margin-top:10px;">
                <?php 
                $employees->execute();
                while($e = $employees->fetch(PDO::FETCH_ASSOC)) { ?>
                    <option value="<?= htmlspecialchars($e['id']) ?>">
                        <?= htmlspecialchars($e['full_name']) ?>
                    </option>
                <?php } ?>
            </select>
            
            <div id="cart-items">
                <div class="cart-row">
                    <select name="medicine_id[]" class="medicine-select" required style="width:60%; padding:8px;">
                        <?php 
                        $medicines->execute();
                        while($m = $medicines->fetch(PDO::FETCH_ASSOC)) { ?>
                            <option value="<?= htmlspecialchars($m['id']) ?>" 
                                    data-price="<?= htmlspecialchars($m['price']) ?>"
                                    data-stock="<?= htmlspecialchars($m['quantity']) ?>">
                                <?= htmlspecialchars($m['name']) ?> 
                                (остаток: <?= htmlspecialchars($m['quantity']) ?>, цена: <?= htmlspecialchars($m['price']) ?> ₽)
                            </option>
                        <?php } ?>
                    </select>
                    
                    <input type="number"
                           name="quantity[]"
                           class="qty"
                           placeholder="Количество"
                           min="1"
                           value="1"
                           required
                           style="width:30%; padding:8px;">
                    
                    <button type="button" class="delete-row" onclick="this.parentElement.remove(); calculateTotal();">×</button>
                </div>
            </div>
            
            <button type="button" onclick="addMedicine()" style="margin-top:10px;">
                + Добавить лекарство
            </button>
            
            <h3 id="total" style="margin-top:20px;">Итого: 0 ₽</h3>
            
            <button type="submit" name="save_sale" id="saveBtn" style="background:#4CAF50; color:white; padding:10px 20px; margin-top:10px;">
                Сохранить
            </button>
            
            <button type="button" onclick="closeModal()" style="background:#f44336; color:white; padding:10px 20px; margin-top:10px;">
                Закрыть
            </button>
        </form>
    </div>
</div>

<script>
let isEditMode = false;

function openModal(){
    document.getElementById('saleModal').style.display = 'flex';
    isEditMode = false;
    document.getElementById('modalTitle').innerHTML = 'Новая продажа';
    document.getElementById('saveBtn').name = 'save_sale';
    document.getElementById('sale_id').value = '';
    document.getElementById('saleForm').reset();
    
    // Réinitialiser le panier
    let cartItems = document.getElementById('cart-items');
    cartItems.innerHTML = '';
    let firstRow = document.querySelector('.cart-row').cloneNode(true);
    cartItems.appendChild(firstRow);
    attachEventsToRow(firstRow);
    calculateTotal();
}

function closeModal(){
    document.getElementById('saleModal').style.display = 'none';
}

function editSale(saleId){
    fetch('sales.php?get_sale=' + saleId)
        .then(res => res.json())
        .then(data => {
            isEditMode = true;
            document.getElementById('modalTitle').innerHTML = 'Редактирование продажи #' + saleId;
            document.getElementById('saveBtn').name = 'update_sale';
            document.getElementById('sale_id').value = saleId;
            
            // Remplir les infos client
            document.getElementById('customer_name').value = data.sale.customer_name;
            document.getElementById('customer_phone').value = data.sale.customer_phone || '';
            
            // Sélectionner l'employé
            let employeeSelect = document.querySelector('select[name="employee"]');
            for(let i = 0; i < employeeSelect.options.length; i++){
                if(employeeSelect.options[i].value == data.sale.employee_id){
                    employeeSelect.selectedIndex = i;
                    break;
                }
            }
            
            // Remplir le panier
            let cartItems = document.getElementById('cart-items');
            cartItems.innerHTML = '';
            
            data.items.forEach((item, index) => {
                let newRow = document.querySelector('.cart-row').cloneNode(true);
                
                // Sélectionner le bon médicament
                let medSelect = newRow.querySelector('.medicine-select');
                for(let i = 0; i < medSelect.options.length; i++){
                    if(medSelect.options[i].value == item.medicine_id){
                        medSelect.selectedIndex = i;
                        break;
                    }
                }
                
                // Définir la quantité
                newRow.querySelector('.qty').value = item.quantity;
                
                cartItems.appendChild(newRow);
                attachEventsToRow(newRow);
            });
            
            calculateTotal();
            openModal();
        })
        .catch(error => console.error('Ошибка:', error));
}

function addMedicine(){
    let originalRow = document.querySelector('.cart-row');
    let newRow = originalRow.cloneNode(true);
    
    let qtyInput = newRow.querySelector('.qty');
    if(qtyInput) qtyInput.value = '1';
    
    document.getElementById('cart-items').appendChild(newRow);
    
    attachEventsToRow(newRow);
    calculateTotal();
}

function attachEventsToRow(row){
    let select = row.querySelector('.medicine-select');
    let qty = row.querySelector('.qty');
    
    if(select) select.onchange = calculateTotal;
    if(qty) qty.oninput = calculateTotal;
}

function calculateTotal(){
    let total = 0;
    let rows = document.querySelectorAll('.cart-row');
    
    rows.forEach(row => {
        let select = row.querySelector('.medicine-select');
        let qty = row.querySelector('.qty');
        
        if(select && qty && qty.value > 0){
            let price = parseFloat(select.options[select.selectedIndex].dataset.price);
            let quantity = parseFloat(qty.value);
            
            if(!isNaN(price) && !isNaN(quantity)){
                total += price * quantity;
            }
        }
    });
    
    document.getElementById('total').innerHTML = 'Итого: ' + total + ' ₽';
}

// ПОИСК КЛИЕНТА
const customerInput = document.getElementById('customer_name');
if(customerInput){
    customerInput.addEventListener('keyup', function(){
        let value = this.value.trim();
        
        if(value.length < 2){
            document.getElementById('suggestions').innerHTML = '';
            return;
        }
        
        fetch('sales.php?ajax=1&search=' + encodeURIComponent(value))
            .then(res => res.json())
            .then(data => {
                let suggestions = document.getElementById('suggestions');
                suggestions.innerHTML = '';
                
                data.forEach(customer => {
                    let div = document.createElement('div');
                    div.classList.add('suggestion-item');
                    div.innerHTML = htmlEscape(customer.full_name);
                    
                    div.onclick = function(){
                        customerInput.value = customer.full_name;
                        let phoneInput = document.getElementById('customer_phone');
                        if(phoneInput) phoneInput.value = customer.phone || '';
                        suggestions.innerHTML = '';
                    };
                    
                    suggestions.appendChild(div);
                });
            })
            .catch(error => console.error('Ошибка:', error));
    });
}

function htmlEscape(str){
    if(!str) return '';
    return str.replace(/[&<>]/g, function(m){
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}

// Привязка событий к существующим строкам
document.querySelectorAll('.cart-row').forEach(row => attachEventsToRow(row));
calculateTotal();

// Валидация формы
document.getElementById('saleForm')?.addEventListener('submit', function(e){
    let rows = document.querySelectorAll('.cart-row');
    if(rows.length === 0){
        e.preventDefault();
        alert('Добавьте хотя бы одно лекарство');
        return false;
    }
    
    for(let row of rows){
        let qty = row.querySelector('.qty');
        let select = row.querySelector('.medicine-select');
        
        if(!qty.value || qty.value < 1){
            e.preventDefault();
            alert('Неверное количество');
            return false;
        }
        
        let maxStock = parseInt(select.options[select.selectedIndex].dataset.stock);
        if(!isEditMode && parseInt(qty.value) > maxStock){
            e.preventDefault();
            alert('Недостаточно товара: ' + select.options[select.selectedIndex].text);
            return false;
        }
    }
});
</script>

</body>
</html>
