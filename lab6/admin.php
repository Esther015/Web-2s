<?php

/**
 * Задача 6. Реализовать вход администратора с использованием
 * HTTP-авторизации для просмотра и удаления результатов.
 **/

// Connexion à la base de données
require_once 'config.php';

// ============================================
// AUTHENTIFICATION HTTP
// ============================================
if (empty($_SERVER['PHP_AUTH_USER']) ||
    empty($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] != 'admin' ||
    md5($_SERVER['PHP_AUTH_PW']) != md5('123')) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="My site"');
    print('<h1>401 Требуется авторизация</h1>');
    exit();
}

// ============================================
// TRAITEMENT DE LA SUPPRESSION
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Supprimer l'utilisateur lié
        $stmt = $pdo->prepare("SELECT id FROM users WHERE application_id = ?");
        $stmt->execute([$deleteId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
        }
        
        // Supprimer les langages
        $stmt = $pdo->prepare("DELETE FROM application_language WHERE application_id = ?");
        $stmt->execute([$deleteId]);
        
        // Supprimer l'application
        $stmt = $pdo->prepare("DELETE FROM application WHERE id = ?");
        $stmt->execute([$deleteId]);
        
        $pdo->commit();
        $message = '<div style="color:green; padding:10px; background:#d4edda; margin-bottom:15px;">✅ Запись #' . $deleteId . ' успешно удалена.</div>';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = '<div style="color:red; padding:10px; background:#f8d7da; margin-bottom:15px;">❌ Ошибка при удалении.</div>';
    }
}

// ============================================
// TRAITEMENT DE LA MODIFICATION
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    $editId = (int)$_POST['edit_id'];
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $biography = $_POST['biography'] ?? '';
    $contract = isset($_POST['contract']) ? 'yes' : 'no';
    $selectedLanguages = $_POST['languages'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        // Mettre à jour l'application
        $stmt = $pdo->prepare("UPDATE application SET name=?, phone=?, email=?, birthdate=?, gender=?, biography=?, contract=? WHERE id=?");
        $stmt->execute([$name, $phone, $email, $birthdate, $gender, $biography, $contract, $editId]);
        
        // Mettre à jour les langages
        $stmt = $pdo->prepare("DELETE FROM application_language WHERE application_id = ?");
        $stmt->execute([$editId]);
        
        if (!empty($selectedLanguages)) {
            $stmt = $pdo->prepare("INSERT INTO application_language (application_id, language_id) VALUES (?, ?)");
            foreach ($selectedLanguages as $langId) {
                $stmt->execute([$editId, (int)$langId]);
            }
        }
        
        $pdo->commit();
        $message = '<div style="color:green; padding:10px; background:#d4edda; margin-bottom:15px;">✅ Запись #' . $editId . ' успешно обновлена.</div>';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = '<div style="color:red; padding:10px; background:#f8d7da; margin-bottom:15px;">❌ Ошибка при обновлении.</div>';
    }
}

// ============================================
// RÉCUPÉRATION DES DONNÉES
// ============================================
$stmt = $pdo->query("SELECT * FROM application ORDER BY id DESC");
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ajouter les langages à chaque application
foreach ($applications as &$app) {
    $stmt = $pdo->prepare("
        SELECT lp.name 
        FROM application_language al 
        JOIN language_programming lp ON al.language_id = lp.id 
        WHERE al.application_id = ?
    ");
    $stmt->execute([$app['id']]);
    $app['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
unset($app);

// Récupérer tous les langages pour le formulaire d'édition
$stmt = $pdo->query("SELECT * FROM language_programming ORDER BY name");
$allLanguages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// STATISTIQUES
// ============================================
$stmt = $pdo->query("
    SELECT lp.name, COUNT(al.application_id) as count
    FROM language_programming lp
    LEFT JOIN application_language al ON lp.id = al.language_id
    GROUP BY lp.id, lp.name
    ORDER BY count DESC
");
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// APPLICATION À ÉDITER
// ============================================
$editApp = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM application WHERE id = ?");
    $stmt->execute([$editId]);
    $editApp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($editApp) {
        $stmt = $pdo->prepare("SELECT language_id FROM application_language WHERE application_id = ?");
        $stmt->execute([$editId]);
        $editApp['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// ============================================
// AFFICHAGE
// ============================================
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        h1 {
            color: #1a237e;
            border-bottom: 3px solid #1a237e;
            padding-bottom: 10px;
        }
        h2 {
            color: #283593;
            margin-top: 30px;
        }
        
        /* Formulaire d'édition */
        .edit-form {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .edit-form h2 {
            margin-top: 0;
            color: #1a237e;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="date"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            height: 80px;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .btn-save {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-save:hover { background: #218838; }
        .btn-cancel {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 14px;
            display: inline-block;
            margin-left: 10px;
        }
        .btn-cancel:hover { background: #5a6268; }
        
        /* Statistiques */
        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 18px 25px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            min-width: 130px;
        }
        .stat-card .num {
            font-size: 30px;
            font-weight: bold;
            color: #1a237e;
        }
        .stat-card .lbl {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        /* Tableau */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }
        th {
            background: #1a237e;
            color: white;
        }
        tr:hover { background: #f5f5f5; }
        tr:nth-child(even) { background: #fafafa; }
        
        .badge {
            display: inline-block;
            background: #e3f2fd;
            color: #1a237e;
            padding: 2px 9px;
            border-radius: 11px;
            font-size: 11px;
            margin: 1px;
        }
        .btn-edit {
            background: #ffc107;
            color: #333;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            margin-right: 5px;
        }
        .btn-edit:hover { background: #ffb300; }
        .btn-del {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .btn-del:hover { background: #c82333; }
        
        @media (max-width: 768px) {
            .form-row { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="container">

<h1>🛡️ Панель администратора</h1>
<p>Вы успешно авторизовались и видите защищенные паролем данные.</p>

<?php if (isset($message)) echo $message; ?>

<!-- ====== FORMULAIRE D'ÉDITION ====== -->
<?php if ($editApp): ?>
<div class="edit-form">
    <h2>✏️ Редактировать запись #<?php echo $editApp['id']; ?></h2>
    <form method="post">
        <input type="hidden" name="edit_id" value="<?php echo $editApp['id']; ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label>ФИО:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($editApp['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Телефон:</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($editApp['phone']); ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($editApp['email']); ?>" required>
            </div>
            <div class="form-group">
                <label>Дата рождения:</label>
                <input type="date" name="birthdate" value="<?php echo htmlspecialchars($editApp['birthdate']); ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Пол:</label>
            <select name="gender">
                <option value="male" <?php echo $editApp['gender'] === 'male' ? 'selected' : ''; ?>>Мужской</option>
                <option value="female" <?php echo $editApp['gender'] === 'female' ? 'selected' : ''; ?>>Женский</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Любимые языки:</label>
            <select name="languages[]" multiple size="5" style="height: auto;">
                <?php foreach ($allLanguages as $lang): ?>
                    <option value="<?php echo $lang['id']; ?>"
                        <?php echo in_array($lang['id'], $editApp['languages'] ?? []) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lang['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="color:#666;">Ctrl+clic pour sélection multiple</small>
        </div>
        
        <div class="form-group">
            <label>Биография:</label>
            <textarea name="biography" required><?php echo htmlspecialchars($editApp['biography']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="contract" value="yes" <?php echo $editApp['contract'] === 'yes' ? 'checked' : ''; ?>>
                Согласен с контрактом
            </label>
        </div>
        
        <button type="submit" class="btn-save">💾 Сохранить</button>
        <a href="admin.php" class="btn-cancel">❌ Отмена</a>
    </form>
</div>
<?php endif; ?>

<!-- ====== STATISTIQUES ====== -->
<h2>📊 Статистика по языкам</h2>
<div class="stats">
    <?php foreach ($stats as $s): ?>
        <div class="stat-card">
            <div class="num"><?php echo $s['count']; ?></div>
            <div class="lbl"><?php echo htmlspecialchars($s['name']); ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ====== TABLEAU DES DONNÉES ====== -->
<h2>📋 Все данные (<?php echo count($applications); ?> записей)</h2>

<?php if (empty($applications)): ?>
    <p>Нет данных.</p>
<?php else: ?>
<table>
<thead>
<tr>
    <th>ID</th><th>ФИО</th><th>Телефон</th><th>Email</th>
    <th>Дата рождения</th><th>Пол</th><th>Языки</th>
    <th>Биография</th><th>Контракт</th><th>Действия</th>
</tr>
</thead>
<tbody>
<?php foreach ($applications as $a): ?>
<tr>
    <td><?php echo $a['id']; ?></td>
    <td><?php echo htmlspecialchars($a['name']); ?></td>
    <td><?php echo htmlspecialchars($a['phone']); ?></td>
    <td><?php echo htmlspecialchars($a['email']); ?></td>
    <td><?php echo htmlspecialchars($a['birthdate']); ?></td>
    <td><?php echo $a['gender'] === 'male' ? 'Муж' : 'Жен'; ?></td>
    <td>
        <?php if (!empty($a['languages'])): ?>
            <?php foreach ($a['languages'] as $l): ?>
                <span class="badge"><?php echo htmlspecialchars($l); ?></span>
            <?php endforeach; ?>
        <?php else: ?>
            <span style="color:#999;">—</span>
        <?php endif; ?>
    </td>
    <td><?php echo htmlspecialchars(mb_substr($a['biography'], 0, 40)) . (mb_strlen($a['biography']) > 40 ? '...' : ''); ?></td>
    <td><?php echo $a['contract'] === 'yes' ? '✅' : '❌'; ?></td>
    <td style="white-space: nowrap;">
        <a href="admin.php?edit=<?php echo $a['id']; ?>" class="btn-edit">✏️</a>
        <form method="post" style="display:inline;" onsubmit="return confirm('Удалить запись #<?php echo $a['id']; ?>?');">
            <input type="hidden" name="delete_id" value="<?php echo $a['id']; ?>">
            <button type="submit" class="btn-del">🗑️</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

</div>
</body>
</html>
