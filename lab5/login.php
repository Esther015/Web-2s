<?php
/**
 * Файл login.php pour l'authentification des utilisateurs.
 */

// Configuration de la base de données
$host = 'localhost';
$dbname = 'uXXXXX';
$username = 'uXXXXX';
$password = 'XXXXXXXXXX';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

header('Content-Type: text/html; charset=UTF-8');

// Vérifier si déconnexion demandée
if (isset($_GET['logout'])) {
    session_start();
    $_SESSION = array();
    session_destroy();
    
    // Supprimer le cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    header('Location: login.php');
    exit();
}

// Démarrer la session
session_start();

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
            🔑 После отправки формы вам будут сгенерированы логин и пароль.<br>
            Сохраните их для последующего изменения данных.
        </div>
        <?php if (isset($_GET['error'])): ?>
            <div class="error">❌ Неверный логин или пароль</div>
        <?php endif; ?>
        <form action="" method="post">
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
    $login = $_POST['login'] ?? '';
    $pass = $_POST['pass'] ?? '';
    
    $stmt = $pdo->prepare("SELECT id, login, password_hash FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($pass, $user['password_hash'])) {
        $_SESSION['login'] = $user['login'];
        $_SESSION['uid'] = $user['id'];
        header('Location: ./');
        exit();
    } else {
        header('Location: login.php?error=1');
        exit();
    }
}
?>
