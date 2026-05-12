<?php
/**
 * Page de connexion
 * MODIFICATIONS DE SÉCURITÉ :
 * - Nettoyage des entrées login/password
 * - Utilisation de requêtes préparées
 * - Session sécurisée avec régénération d'ID
 * - Protection contre les sessions fixes
 */

require_once 'config.php';

header('Content-Type: text/html; charset=UTF-8');

// ========== MODIF SÉCURITÉ #1 : Gestion sécurisée de la déconnexion ==========
if (isset($_GET['logout'])) {
    session_start();
    
    // Régénérer l'ID de session avant destruction (sécurité)
    session_regenerate_id(true);
    
    $_SESSION = array();
    
    // Supprimer le cookie de session
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

// Si déjà connecté, redirection
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
    <title>Connexion</title>
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
        <h2>Connexion</h2>
        <div class="info">
            Après l'envoi du formulaire, un login et mot de passe vous seront générés.
            Conservez-les pour modifier vos données ultérieurement.
        </div>
        <?php if (isset($_GET['error'])): ?>
            <div class="error">Login ou mot de passe incorrect</div>
        <?php endif; ?>
        
        <!-- ========== MODIF SÉCURITÉ #2 : Ajout token CSRF pour login ========== -->
        <form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="text" name="login" placeholder="Login" required autofocus />
            <input type="password" name="pass" placeholder="Mot de passe" required />
            <input type="submit" value="Se connecter" />
        </form>
    </div>
</body>
</html>
<?php
}
else {
    // ========== MODIF SÉCURITÉ #3 : Vérification CSRF ==========
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die('Erreur CSRF : requête invalide');
    }
    
    // ========== MODIF SÉCURITÉ #4 : Nettoyage des entrées ==========
    $login = isset($_POST['login']) ? trim(strip_tags($_POST['login'])) : '';
    $pass = isset($_POST['pass']) ? $_POST['pass'] : '';
    
    // Validation simple
    if (empty($login) || empty($pass)) {
        header('Location: login.php?error=1');
        exit();
    }
    
    // ========== MODIF SÉCURITÉ #5 : Requête préparée ==========
    $stmt = $pdo->prepare("SELECT id, login, password_hash FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vérification du mot de passe avec password_verify()
    if ($user && password_verify($pass, $user['password_hash'])) {
        // ========== MODIF SÉCURITÉ #6 : Régénération ID session ==========
        session_regenerate_id(true);
        
        $_SESSION['login'] = $user['login'];
        $_SESSION['uid'] = $user['id'];
        
        // Option : définir un timeout de session
        $_SESSION['last_activity'] = time();
        
        header('Location: ./');
        exit();
    } else {
        header('Location: login.php?error=1');
        exit();
    }
}
?>
