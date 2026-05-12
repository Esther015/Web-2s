<?php
/**
 * Страница входа
 * МОДИФИКАЦИИ БЕЗОПАСНОСТИ :
 * - Очистка входных данных login/password
 * - Использование подготовленных запросов
 * - Безопасная сессия с регенерацией ID
 * - Защита от фиксации сессии
 */

require_once 'config.php';

header('Content-Type: text/html; charset=UTF-8');

// ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #1 : Безопасный выход ==========
if (isset($_GET['logout'])) {
    session_start();
    
    // Регенерация ID сессии перед уничтожением
    session_regenerate_id(true);
    
    $_SESSION = array();
    
    // Удаление cookie сессии
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    header('Location: login.php');
    exit();
}

session_start();

// Если уже вошли, перенаправление
if (!empty($_SESSION['login'])) {
    header('Location: ./');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
        }
        .container {
            width: 400px;
            margin: 100px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin: 10px 0;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 4px;
        }
        .info {
            background-color: #e3f2fd;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Вход в систему</h2>
        <div class="info">
            После отправки формы вам будут сгенерированы логин и пароль.
            Сохраните их для последующего изменения данных.
        </div>
        <?php if (isset($_GET['error'])): ?>
            <div class="error">Неверный логин или пароль</div>
        <?php endif; ?>
        
        <!-- ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #2 : Добавление CSRF-токена ========== -->
        <form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="text" name="login" placeholder="Логин" required autofocus />
            <input type="password" name="pass" placeholder="Пароль" required />
            <input type="submit" value="Войти" />
        </form>
    </div>
</body>
</html>
<?php
}
else {
    // ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #3 : Проверка CSRF ==========
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die('Ошибка CSRF: недействительный запрос');
    }
    
    // ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #4 : Очистка входных данных ==========
    $login = isset($_POST['login']) ? trim(strip_tags($_POST['login'])) : '';
    $pass = isset($_POST['pass']) ? $_POST['pass'] : '';
    
    // Простая валидация
    if (empty($login) || empty($pass)) {
        header('Location: login.php?error=1');
        exit();
    }
    
    // ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #5 : Подготовленный запрос ==========
    $stmt = $pdo->prepare("SELECT id, login, password_hash FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Проверка пароля с password_verify()
    if ($user && password_verify($pass, $user['password_hash'])) {
        // ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #6 : Регенерация ID сессии ==========
        session_regenerate_id(true);
        
        $_SESSION['login'] = $user['login'];
        $_SESSION['uid'] = $user['id'];
        
        // Опционально: установка таймаута сессии
        $_SESSION['last_activity'] = time();
        
        header('Location: ./');
        exit();
    } else {
        header('Location: login.php?error=1');
        exit();
    }
}
?>
