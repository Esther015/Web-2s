<?php
/**
 * Безопасная панель администратора
 * МОДИФИКАЦИИ БЕЗОПАСНОСТИ :
 * - Использование password_hash() вместо md5() для пароля admin
 * - Добавлена проверка CSRF для удаления
 * - Добавлен htmlspecialchars() для всего вывода (XSS)
 * - Строгая валидация типов с filter_input()
 * - Общие сообщения об ошибках (Information Disclosure)
 * - Использование подготовленных запросов PDO (SQL Injection)
 */

require_once 'config.php';

// ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #1 : Безопасная аутентификация admin ==========
// Использование password_hash() вместо md5()
// Для генерации хеша: password_hash('123', PASSWORD_DEFAULT)
if (empty($_SERVER['PHP_AUTH_USER']) ||
    empty($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] != 'admin' ||
    !password_verify($_SERVER['PHP_AUTH_PW'], '$2y$10$kBeuB2haNiuQ.DyNUDjsh.fKkaJZvrK/aExHUVqFMFOXJHdeDNMLO')) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Администрирование"');
    print('<h1>401 Требуется авторизация</h1>');
    exit();
}

// ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #2 : Обработка удаления с CSRF ==========
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    
    // Проверка CSRF (защита от межсайтовых атак)
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = '<div class="error">Недействительный CSRF-токен</div>';
    } else {
        // Строгая валидация ID (SQL Injection)
        $deleteId = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
        if ($deleteId === false || $deleteId <= 0) {
            $message = '<div class="error">Недействительный ID</div>';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Все запросы подготовленные (SQL Injection)
                $stmt = $pdo->prepare("SELECT id FROM users WHERE application_id = ?");
                $stmt->execute([$deleteId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user['id']]);
                }
                
                $stmt = $pdo->prepare("DELETE FROM application_language WHERE application_id = ?");
                $stmt->execute([$deleteId]);
                
                $stmt = $pdo->prepare("DELETE FROM application WHERE id = ?");
                $stmt->execute([$deleteId]);
                
                $pdo->commit();
                $message = '<div class="success">Запись #' . e($deleteId) . ' успешно удалена.</div>';
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log($e->getMessage()); // Только в лог, не на экран (Information Disclosure)
                $message = '<div class="error">Ошибка при удалении.</div>'; // Общее сообщение
            }
        }
    }
}

// ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #3 : Подготовленные запросы для чтения ==========
try {
    $stmt = $pdo->prepare("SELECT * FROM application ORDER BY id DESC");
    $stmt->execute();
    $applications = $stmt->fetchAll();
    
    // Добавление языков с подготовленным запросом
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
    
    // Статистика
    $stmt = $pdo->prepare("
        SELECT l.name, COUNT(al.application_id) as count
        FROM programming_language l
        LEFT JOIN application_language al ON l.id = al.language_id
        GROUP BY l.id, l.name
        ORDER BY count DESC
    ");
    $stmt->execute();
    $stats = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log($e->getMessage());
    die('Ошибка загрузки данных'); // Общее сообщение
}

// ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #4 : Весь вывод использует e() для XSS ==========
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #1a237e; }
        .success { color: #155724; background: #d4edda; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #1a237e; color: white; }
        .badge { background: #e3f2fd; color: #1a237e; padding: 2px 8px; border-radius: 11px; font-size: 11px; margin: 1px; display: inline-block; }
        .btn-del { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
        .stats { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-card { background: white; padding: 15px 25px; border-radius: 8px; text-align: center; }
        .stat-card .num { font-size: 30px; font-weight: bold; color: #1a237e; }
    </style>
</head>
<body>
<div class="container">
    <h1>Панель администратора</h1>
    <p>Вы успешно авторизовались и видите защищенные паролем данные.</p>
    
    <?php echo $message; ?>
    
    <h2>Статистика по языкам</h2>
    <div class="stats">
        <?php foreach ($stats as $s): ?>
        <div class="stat-card">
            <div class="num"><?php echo e($s['count']); ?></div>
            <div class="lbl"><?php echo e($s['name']); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <h2>Все данные (<?php echo count($applications); ?> записей)</h2>
    
    <?php if (empty($applications)): ?>
        <p>Нет данных.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>ID</th><th>ФИО</th><th>Телефон</th><th>Email</th><th>Дата рождения</th><th>Пол</th><th>Языки</th><th>Действие</th></tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $a): ?>
            <tr>
                <td><?php echo e($a['id']); ?></td>
                <td><?php echo e($a['name']); ?></td>
                <td><?php echo e($a['phone']); ?></td>
                <td><?php echo e($a['email']); ?></td>
                <td><?php echo e($a['birthdate']); ?></td>
                <td><?php echo $a['gender'] === 'male' ? 'Муж' : 'Жен'; ?></td>
                <td>
                    <?php foreach ($a['languages'] as $l): ?>
                        <span class="badge"><?php echo e($l); ?></span>
                    <?php endforeach; ?>
                </td>
                <td>
                    <!-- ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #5 : Форма с CSRF-токеном ========== -->
                    <form method="post" onsubmit="return confirm('Удалить запись #<?php echo e($a['id']); ?>?');">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="delete_id" value="<?php echo e($a['id']); ?>">
                        <button type="submit" class="btn-del">Удалить</button>
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
