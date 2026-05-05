<?php

/**
 * Задача 6. Реализовать вход администратора с использованием
 * HTTP-авторизации для просмотра и удаления результатов.
 **/

// Connexion à la base de données
require_once 'config.php';

// AUTHENTIFICATION HTTP (fallback simple)
if (empty($_SERVER['PHP_AUTH_USER']) ||
    empty($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] != 'admin' ||
    md5($_SERVER['PHP_AUTH_PW']) != md5('123')) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="My site"');
    print('<h1>401 Требуется авторизация</h1>');
    exit();
}

// TRAITEMENT DE LA SUPPRESSION
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
        $message = '<div style="color:green; padding:10px; background:#d4edda; margin-bottom:15px;">Запись #' . $deleteId . ' успешно удалена.</div>';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = '<div style="color:red; padding:10px; background:#f8d7da; margin-bottom:15px;">Ошибка при удалении.</div>';
    }
}
// RÉCUPÉRATION DES DONNÉES
$stmt = $pdo->query("SELECT * FROM application ORDER BY id DESC");
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ajouter les langages à chaque application (sans référence)
$applicationsWithLanguages = [];
foreach ($applications as $app) {
    $stmt = $pdo->prepare("
        SELECT pl.name 
        FROM application_language al 
        JOIN programming_language pl ON al.language_id = pl.id 
        WHERE al.application_id = ?
    ");
    $stmt->execute([$app['id']]);
    $app['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $applicationsWithLanguages[] = $app;
}
$applications = $applicationsWithLanguages;

// Ajouter les langages
foreach ($applications as &$app) {
    $stmt = $pdo->prepare("
        SELECT l.name 
        FROM application_language al 
        JOIN programming_language l ON al.language_id = l.id 
        WHERE al.application_id = ?
    ");
    $stmt->execute([$app['id']]);
    $app['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
unset($app);

// STATISTIQUES
$stmt = $pdo->query("
    SELECT l.name, COUNT(al.application_id) as count
    FROM programming_language l
    LEFT JOIN application_language al ON l.id = al.language_id
    GROUP BY l.id, l.name
    ORDER BY count DESC
");
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// AFFICHAGE
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
    </style>
</head>
<body>
<div class="container">

<h1>Панель администратора</h1>
<p>Вы успешно авторизовались и видите защищенные паролем данные.</p>

<?php if (isset($message)) echo $message; ?>

<!-- ====== STATISTIQUES ====== -->
<h2>Статистика по языкам</h2>
<div class="stats">
    <?php foreach ($stats as $s): ?>
        <div class="stat-card">
            <div class="num"><?php echo $s['count']; ?></div>
            <div class="lbl"><?php echo htmlspecialchars($s['name']); ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ====== TABLEAU DES DONNÉES ====== -->
<h2>Все данные (<?php echo count($applications); ?> записей)</h2>

<?php if (empty($applications)): ?>
    <p>Нет данных.</p>
<?php else: ?>
<table>
<thead>
<tr>
    <th>ID</th><th>ФИО</th><th>Телефон</th><th>Email</th>
    <th>Дата рождения</th><th>Пол</th><th>Языки</th>
    <th>Биография</th><th>Контракт</th><th>Действие</th>
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
        <?php foreach ($a['languages'] as $l): ?>
            <span class="badge"><?php echo htmlspecialchars($l); ?></span>
        <?php endforeach; ?>
    </td>
    <td><?php echo htmlspecialchars(substr($a['biography'], 0, 40)) . (strlen($a['biography']) > 40 ? '...' : ''); ?></td>
    <td><?php echo $a['contract'] === 'yes' ? 'Да' : 'Нет'; ?></td>
    <td>
        <form method="post" onsubmit="return confirm('Удалить запись #<?php echo $a['id']; ?>?');">
            <input type="hidden" name="delete_id" value="<?php echo $a['id']; ?>">
            <button type="submit" class="btn-del">Supprime</button>
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
