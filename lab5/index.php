<?php
/**
 * Réaliser la possibilité de se connecter avec un mot de passe et un identifiant
 * en utilisant une session pour modifier les données envoyées dans la tâche précédente.
 */

// Configuration de la base de données
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

// Fonctions utilitaires
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

function generateUniqueLogin($pdo) {
    do {
        $login = 'user_' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $exists = $stmt->fetch();
    } while ($exists);
    return $login;
}

// Envoie l'encodage correct au navigateur
header('Content-Type: text/html; charset=UTF-8');

// Démarrer la session
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = array();
    $errors = array();
    $values = array();
    $isLoggedIn = false;

    // Initialiser tous les champs
    $fields = ['name', 'phone', 'email', 'birthdate', 'gender', 'languages', 'biography', 'contract'];
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        if ($field == 'languages') {
            $values[$field] = empty($_COOKIE[$field . '_value']) ? [] : explode(',', strip_tags($_COOKIE[$field . '_value']));
        } else {
            $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : strip_tags($_COOKIE[$field . '_value']);
        }
    }

    // Vérifier les cookies de sauvegarde
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        setcookie('login', '', 100000);
        setcookie('pass', '', 100000);
        $messages[] = '<div style="color:green; padding:10px; background:#d4edda; margin-bottom:10px;">Спасибо, результаты сохранены.</div>';
        
        if (!empty($_COOKIE['pass'])) {
            $messages[] = sprintf('<div style="color:#155724; padding:10px; background:#d4edda; margin-bottom:10px;">Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong> и паролем <strong>%s</strong> для изменения данных.</div>',
                strip_tags($_COOKIE['login']),
                strip_tags($_COOKIE['pass']));
        }
    }

    // Afficher les messages d'erreur pour chaque champ
    $errorMessages = [
        'name' => 'Заполните имя.',
        'phone' => 'Заполните телефон (10 цифр минимум).',
        'email' => 'Заполните корректный email.',
        'birthdate' => 'Заполните дату.',
        'gender' => 'Выберите пол.',
        'languages' => 'Выберите язык.',
        'biography' => 'Заполните биографию (минимум 10 символов).',
        'contract' => 'Примите условия контракта.'
    ];

    foreach ($fields as $field) {
        if (!empty($_COOKIE[$field . '_error'])) {
            setcookie($field . '_error', '', 100000);
            $messages[] = '<div class="error" style="color:red; padding:5px;">' . $errorMessages[$field] . '</div>';
        }
    }

    // PARTIE GET - Vérifier si l'utilisateur est connecté et charger ses données
    if (!empty($_SESSION['login']) && !empty($_SESSION['uid'])) {
        $isLoggedIn = true;
        
        // Récupérer application_id depuis users
        $stmt = $pdo->prepare("SELECT application_id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            // Charger les données depuis application
            $stmt = $pdo->prepare("SELECT name, phone, email, birthdate, gender, biography, contract FROM application WHERE id = ?");
            $stmt->execute([$userData['application_id']]);
            $appData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($appData) {
                $values['name'] = strip_tags($appData['name']);
                $values['phone'] = strip_tags($appData['phone']);
                $values['email'] = strip_tags($appData['email']);
                $values['birthdate'] = strip_tags($appData['birthdate']);
                $values['gender'] = strip_tags($appData['gender']);
                $values['biography'] = strip_tags($appData['biography']);
                $values['contract'] = strip_tags($appData['contract']);
            }
            
            // Charger les langages depuis application_language
            $stmt = $pdo->prepare("SELECT programming_language_id FROM application_language WHERE application_id = ?");
            $stmt->execute([$userData['application_id']]);
            $langData = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $values['languages'] = $langData;
        }
        $messages[] = '<div style="color:#0c5460; padding:10px; background:#d1ecf1; margin-bottom:10px;">Вход с логином ' . strip_tags($_SESSION['login']) . ' | <a href="login.php?logout=1">Выйти</a></div>';
    }

    include('form.php');
}
else {
    // Méthode POST - validation et sauvegarde
    $errors = false;
    $isLoggedIn = false;
    $userId = null;
    
    // Validation de tous les champs
    if (empty($_POST['name'])) {
        setcookie('name_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('name_value', $_POST['name'], time() + 30 * 24 * 60 * 60);
    }
    
    if (empty($_POST['phone']) || !preg_match('/^[0-9+\-\s]{10,}$/', $_POST['phone'])) {
        setcookie('phone_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('phone_value', $_POST['phone'], time() + 30 * 24 * 60 * 60);
    }
    
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('email_value', $_POST['email'], time() + 30 * 24 * 60 * 60);
    }
    
    if (empty($_POST['birthdate'])) {
        setcookie('birthdate_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('birthdate_value', $_POST['birthdate'], time() + 30 * 24 * 60 * 60);
    }
    
    if (empty($_POST['gender'])) {
        setcookie('gender_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('gender_value', $_POST['gender'], time() + 30 * 24 * 60 * 60);
    }
    
    if (empty($_POST['languages'])) {
        setcookie('languages_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        $languagesCookie = implode(',', $_POST['languages']);
        setcookie('languages_value', $languagesCookie, time() + 30 * 24 * 60 * 60);
    }
    
    if (empty($_POST['biography']) || strlen($_POST['biography']) < 10) {
        setcookie('biography_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('biography_value', $_POST['biography'], time() + 30 * 24 * 60 * 60);
    }
    
    if (empty($_POST['contract'])) {
        setcookie('contract_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('contract_value', $_POST['contract'], time() + 30 * 24 * 60 * 60);
    }
    
    if ($errors) {
        header('Location: index.php');
        exit();
    }
    
    // Supprimer tous les cookies d'erreur
    $fields = ['name', 'phone', 'email', 'birthdate', 'gender', 'languages', 'biography', 'contract'];
    foreach ($fields as $field) {
        setcookie($field . '_error', '', 100000);
    }
    
    // Vérifier si l'utilisateur est connecté
    if (!empty($_SESSION['login']) && !empty($_SESSION['uid'])) {
        $isLoggedIn = true;
        $userId = $_SESSION['uid'];
    }
    
    // Préparer les données
    $selectedLanguages = isset($_POST['languages']) ? $_POST['languages'] : [];
    $contractValue = isset($_POST['contract']) ? $_POST['contract'] : 'no';
    
    try {
        if ($isLoggedIn && $userId) {
            // Utilisateur connecté - récupérer application_id
            $stmt = $pdo->prepare("SELECT application_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                $applicationId = $userData['application_id'];
                
                // Mettre à jour application
                $stmt = $pdo->prepare("UPDATE application SET name = ?, phone = ?, email = ?, birthdate = ?, gender = ?, biography = ?, contract = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['name'], 
                    $_POST['phone'], 
                    $_POST['email'], 
                    $_POST['birthdate'],
                    $_POST['gender'], 
                    $_POST['biography'], 
                    $contractValue,
                    $applicationId
                ]);
                
                // Mettre à jour les langages : supprimer les anciens puis ajouter les nouveaux
                $stmt = $pdo->prepare("DELETE FROM application_language WHERE application_id = ?");
                $stmt->execute([$applicationId]);
                
                foreach ($selectedLanguages as $langId) {
                    $stmt = $pdo->prepare("INSERT INTO application_language (application_id, programming_language_id) VALUES (?, ?)");
                    $stmt->execute([$applicationId, $langId]);
                }
            }
        } else {
            // Nouvel utilisateur
            $pdo->beginTransaction();
            
            // 1. Insérer dans application
            $stmt = $pdo->prepare("INSERT INTO application (name, phone, email, birthdate, gender, biography, contract) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'], 
                $_POST['phone'], 
                $_POST['email'], 
                $_POST['birthdate'],
                $_POST['gender'], 
                $_POST['biography'], 
                $contractValue
            ]);
            $applicationId = $pdo->lastInsertId();
            
            // 2. Insérer les langages dans application_language
            foreach ($selectedLanguages as $langId) {
                $stmt = $pdo->prepare("INSERT INTO application_language (application_id, programming_language_id) VALUES (?, ?)");
                $stmt->execute([$applicationId, $langId]);
            }
            
            // 3. Générer login et mot de passe
            $login = generateUniqueLogin($pdo);
            $plainPassword = generateRandomPassword(8);
            $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
            
            // 4. Insérer dans users
            $stmt = $pdo->prepare("INSERT INTO users (login, password_hash, application_id) VALUES (?, ?, ?)");
            $stmt->execute([$login, $passwordHash, $applicationId]);
            
            $pdo->commit();
            
            // Sauvegarder login et mot de passe dans les cookies
            setcookie('login', $login, time() + 30 * 24 * 60 * 60);
            setcookie('pass', $plainPassword, time() + 30 * 24 * 60 * 60);
        }
        
        setcookie('save', '1', time() + 30 * 24 * 60 * 60);
        header('Location: ./');
        exit();
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Erreur lors de l'enregistrement : " . $e->getMessage());
    }
}
?>
