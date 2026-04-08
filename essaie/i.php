<?php
/**
 * Réaliser la possibilité de se connecter avec un mot de passe et un identifiant
 * en utilisant une session pour modifier les données envoyées dans la tâche précédente.
 */

// Configuration de la base de données (à mettre directement ici comme dans l'exemple du prof)
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

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = array();
    $errors = array();
    $values = array();
    $isLoggedIn = false;
    
    // Initialiser tous les champs
    $fields = ['name', 'phone', 'email', 'birthdate', 'gender', 'languages', 'biography', 'contract'];
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : strip_tags($_COOKIE[$field . '_value']);
    }

    // Pour le champ languages (multiple)
    if (!empty($_COOKIE['languages_value'])) {
        $values['languages'] = explode(',', strip_tags($_COOKIE['languages_value']));
    } else {
        $values['languages'] = [];
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

    // Vérifier si l'utilisateur est connecté

//    $isLoggedIn = false;
  //  if (!empty($_COOKIE[session_name()])) {
    //    session_start();
      //  if (!empty($_SESSION['login']) && !empty($_SESSION['uid'])) {
        ///    $isLoggedIn = true;
            // Charger les données de l'utilisateur depuis la BDD
           // $stmt = $pdo->prepare("SELECT name, phone, email, birthdate, gender, languages, biography, contract FROM users WHERE id = ?");
            //$stmt->execute([$_SESSION['uid']]);
            //$userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            //if ($userData) {
              //  foreach ($fields as $field) {
                    
                //    if (!empty($userData[$field])) {
                  //      $values[$field] = strip_tags($userData[$field]);
                    //}
                //}
            //}
            //$messages[] = '<div style="color:#0c5460; padding:10px; background:#d1ecf1; margin-bottom:10px;">Вход с логином ' . strip_tags($_SESSION['login']) . '</div>';
        //}
   // }

    //verifier si l'utilisateur est connecte 
if (!empty($_SESSION['login']) && !empty($_SESSION['uid'])) {
        // Charger les données de l'utilisateur depuis la BDD
        $isLoggedIn = true;
    
        $stmt = $pdo->prepare("SELECT name, phone, email, birthdate, gender, languages, biography, contract FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            // Charger les données depuis application
            $stmt = $pdo->prepare("SELECT name, phone, email, birthdate, gender, languages, biography, contract FROM application WHERE id = ?");
            $stmt->execute([$userData['application_id']]);
            $appData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($appData) {
                foreach ($fields as $field) {
                    if ($field == 'languages') {
                        $values[$field] = !empty($appData[$field]) ? explode(',', strip_tags($appData[$field])) : [];
                    } elseif (!empty($appData[$field])) {
                        $values[$field] = strip_tags($appData[$field]);
                    }
                }
            }
        }
    
        $messages[] = '<div style="color:#0c5460; padding:10px; background:#d1ecf1; margin-bottom:10px;">Вход с логином ' . strip_tags($_SESSION['login']) . ' | <a href="login.php?logout=1">Выйти</a></div>';
}

    include('f.php');
}
else {
    // Méthode POST - validation et sauvegarde
    $errors = false;
    $isLoggedIn = false;
    $userId = null;
    
    // Validation de tous les champs
    // name
    if (empty($_POST['name'])) {
        setcookie('name_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('name_value', $_POST['name'], time() + 30 * 24 * 60 * 60);
    }
    
    // phone
    if (empty($_POST['phone']) || !preg_match('/^[0-9+\-\s]{10,}$/', $_POST['phone'])) {
        setcookie('phone_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('phone_value', $_POST['phone'], time() + 30 * 24 * 60 * 60);
    }
    
    // email
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('email_value', $_POST['email'], time() + 30 * 24 * 60 * 60);
    }
    
    // date
    if (empty($_POST['birthdate'])) {
        setcookie('birthdate_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('bithdate_value', $_POST['birthdate'], time() + 30 * 24 * 60 * 60);
    }
    
    // gender
    if (empty($_POST['gender'])) {
        setcookie('gender_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('gender_value', $_POST['gender'], time() + 30 * 24 * 60 * 60);
    }
    
    // languages
    if (empty($_POST['languages'])) {
        setcookie('languages_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        $languages = implode(',', $_POST['languages']);
        setcookie('languages_value', $languages, time() + 30 * 24 * 60 * 60);
    }
    
    // biography
    if (empty($_POST['biography']) || strlen($_POST['biography']) < 10) {
        setcookie('biography_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('biography_value', $_POST['biography'], time() + 30 * 24 * 60 * 60);
    }
    
    // contract
    if (empty($_POST['contract'])) {
        setcookie('contract_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('contract_value', $_POST['contract'], time() + 30 * 24 * 60 * 60);
    }
    
    if ($errors) {
        header('Location: i.php');
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
    $languages = isset($_POST['languages']) ? implode(',', $_POST['languages']) : '';

    if ($isLoggedIn && $userId) {
        // Utilisateur connecté - récupérer d'abord application_id
        $stmt = $pdo->prepare("SELECT application_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            // Mettre à jour les données dans application
            $stmt = $pdo->prepare("UPDATE application SET name = ?, phone = ?, email = ?, birthdate = ?, gender = ?, languages = ?, biography = ?, contract = ? WHERE id = ?");
            $stmt->execute([
                $_POST['name'], $_POST['phone'], $_POST['email'], $_POST['birthdate'],
                $_POST['gender'], $languages, $_POST['biography'], $_POST['contract'],
                $userData['application_id']
            ]);
        }
   
    } else {
        // Nouvel utilisateur - générer login et mot de passe
        // Générer login et mot de passe
            $login = generateUniqueLogin($pdo);
            $plainPassword = generateRandomPassword(8);
            $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        try {
        // Commencer une transaction
            $pdo->beginTransaction();
        
            $stmt = $pdo->prepare("INSERT INTO application (name, phone, email, birthdate, gender, languages, biography, contract) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'], $_POST['phone'], $_POST['email'], $_POST['birthdate'],
                $_POST['gender'], $languages, $_POST['biography'], $_POST['contract']
            ]);
        // Récupérer l'ID de la nouvelle application
            $applicationId = $pdo->lastInsertId();
    
        //Insérer dans la table users avec l'application_id
            $stmt = $pdo->prepare("INSERT INTO users (login, password_hash, application_id) VALUES (?, ?, ?)");
            $stmt->execute([$login, $passwordHash, $applicationId]);
        
        // Valider la transaction
            $pdo->commit();
        
        // Sauvegarder login et mot de passe dans les cookies pour l'affichage
            setcookie('login', $login, time() + 30 * 24 * 60 * 60);
            setcookie('pass', $plainPassword, time() + 30 * 24 * 60 * 60);
        
        } catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
            $pdo->rollBack();
            die("Erreur lors de l'enregistrement : " . $e->getMessage());
        }  
    }
    
    setcookie('save', '1');
    header('Location: ./');
    exit();
}
?>
