<?php

/**
 * Задача 6. Реализовать вход администратора с использованием
 * HTTP-авторизации для просмотра и удаления результатов.
 **/

// 1. CONFIGURATION DE LA BASE DE DONNÉES
$host = 'localhost';
$dbname = 'u82384';
$username = 'u82384';
$password = 'd5#RdgdgH';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// 2. AUTHENTIFICATION HTTP
// Vérifier si l'admin est dans la base de données
$isAuthenticated = false;

if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
    // Chercher l'admin dans la table admins
    $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
        $isAuthenticated = true;
    }
}

// Fallback simple (comme dans le squelette du prof) si pas d'admin en base
if (!$isAuthenticated) {
    if (empty($_SERVER['PHP_AUTH_USER']) ||
        empty($_SERVER['PHP_AUTH_PW']) ||
        $_SERVER['PHP_AUTH_USER'] != 'admin' ||
        md5($_SERVER['PHP_AUTH_PW']) != md5('123')) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="My site"');
        print('<h1>401 Требуется авторизация</h1>');
        exit();
    }
    $isAuthenticated = true;
}

// 3. TRAITEMENT DES ACTIONS (SUPPRESSION)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Récupérer le user_id lié
        $stmt = $pdo->prepare("SELECT id FROM users WHERE application_id = ?");
        $stmt->execute([$deleteId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Supprimer l'utilisateur
        if ($user) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
        }
        
        // Supprimer les langages liés
        $stmt = $pdo->prepare("DELETE FROM application_language WHERE application_id = ?");
        $stmt->execute([$deleteId]);
        
        // Supprimer l'application
        $stmt = $pdo->prepare("DELETE FROM application WHERE id = ?");
        $stmt->execute([$deleteId]);
        
        $pdo->commit();
        $deleteMessage = '<p style="color:green;"> Запись #' . $deleteId . ' успешно удалена.</p>';
    } catch (Exception $e) {
        $pdo->rollBack();
        $deleteMessage = '<p style="color:red;"> Ошибка при удалении: ' . $e->getMessage() . '</p>';
    }
}

// 4. RÉCUPÉRATION DES DONNÉES
// Toutes les applications avec leurs langages
$stmt = $pdo->query("
    SELECT a.* 
    FROM application a 
    ORDER BY a.id DESC
");
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ajouter les langages à chaque application
foreach ($applications as &$app) {
    $stmt = $pdo->prepare("
        SELECT l.name 
        FROM application_language al 
        JOIN languages l ON al.language_id = l.id 
        WHERE al.application_id = ?
    ");
    $stmt->execute([$app['id']]);
    $app['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
unset($app); // Détruire la référence

// 5. STATISTIQUES
$stmt = $pdo->query("
    SELECT l.name, COUNT(al.application_id) as count
    FROM languages l
    LEFT JOIN application_language al ON l.id = al.language_id
    GROUP BY l.id, l.name
    ORDER BY count DESC
");
$languageStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. AFFICHAGE

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
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
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
        
        /* Message de succès */
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        /* Statistiques */
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            min-width: 150px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #1a237e;
        }
        .stat-card .label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        /* Tableau */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #1a237e;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        tr:nth-child(even):hover {
            background-color: #f0f0f0;
        }
        
        /* Badges langages */
        .lang-badge {
            display: inline-block;
            background: #e3f2fd;
            color: #1a237e;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            margin: 2px;
            border: 1px solid #bbdefb;
        }
        
        /* Bouton supprimer */
        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        
        .info-text {
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1> Панель администратора</h1>
        
        <p>Вы успешно авторизовались и видите защищенные паролем данные.</p>
        
        <?php if (isset($deleteMessage)) echo $deleteMessage; ?>
        
        <!--SECTION STATISTIQUES -->
        <h2> Статистика по языкам программирования</h2>
        <div class="stats-container">
            <?php foreach ($languageStats as $stat): ?>
                <div class="stat-card">
                    <div class="number"><?php echo $stat['count']; ?></div>
                    <div class="label">
                        <?php echo htmlspecialchars($stat['name']); ?>
                        (<?php 
                            $count = $stat['count'];
                            if ($count == 1) echo 'пользователь';
                            elseif ($count >= 2 && $count <= 4) echo 'пользователя';
                            else echo 'пользователей';
                        ?>)
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- SECTION TABLEAU DES DONNÉES-->
        <h2> Все введенные пользователями данные (<?php echo count($applications); ?> записей)</h2>
        
        <?php if (empty($applications)): ?>
            <p class="info-text">Нет данных для отображения.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Дата рождения</th>
                    <th>Пол</th>
                    <th>Языки</th>
                    <th>Биография</th>
                    <th>Контракт</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?php echo $app['id']; ?></td>
                    <td><?php echo htmlspecialchars($app['name']); ?></td>
                    <td><?php echo htmlspecialchars($app['phone']); ?></td>
                    <td><?php echo htmlspecialchars($app['email']); ?></td>
                    <td><?php echo htmlspecialchars($app['birthdate']); ?></td>
                    <td>
                        <?php 
                            echo $app['gender'] === 'male' ? 'Мужской' : 
                                ($app['gender'] === 'female' ? 'Женский' : htmlspecialchars($app['gender'])); 
                        ?>
                    </td>
                    <td>
                        <?php if (!empty($app['languages'])): ?>
                            <?php foreach ($app['languages'] as $lang): ?>
                                <span class="lang-badge"><?php echo htmlspecialchars($lang); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="color:#999;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                            $bio = $app['biography'];
                            echo htmlspecialchars(mb_strlen($bio) > 50 ? mb_substr($bio, 0, 50) . '...' : $bio);
                        ?>
                    </td>
                    <td><?php echo $app['contract'] === 'yes' ? ' Да' : ' Нет'; ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Вы уверены, что хотите удалить запись #<?php echo $app['id']; ?>?');">
                            <input type="hidden" name="delete_id" value="<?php echo $app['id']; ?>">
                            <button type="submit" class="btn-delete"> Удалить</button>
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
