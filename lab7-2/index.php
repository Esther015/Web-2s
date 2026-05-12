<?php
/**
 * Page principale avec formulaire
 * MODIFICATIONS DE SÉCURITÉ :
 * - Ajout du token CSRF dans le formulaire
 * - Validation CSRF avant traitement
 * - Nettoyage des entrées avec filter_var()
 * - Utilisation de requêtes préparées pour toutes les requêtes SQL
 * - Échappement de toutes les sorties avec e()
 * - Session sécurisée
 */

require_once 'config.php';

header('Content-Type: text/html; charset=UTF-8');

// ========== MODIF SÉCURITÉ #1 : Récupération des données avec échappement ==========
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = array();
    $errors = array();
    $values = array();
    $isLoggedIn = false;

    $fields = ['name', 'phone', 'email', 'birthdate', 'gender', 'languages', 'biography', 'contract'];
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        if ($field == 'languages') {
            $values[$field] = empty($_COOKIE[$field . '_value']) ? [] : explode(',', strip_tags($_COOKIE[$field . '_value']));
        } else {
            // Échappement des valeurs des cookies (XSS)
            $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : e($_COOKIE[$field . '_value']);
        }
    }

    // Affichage des messages de succès avec échappement
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        setcookie('login', '', 100000);
        setcookie('pass', '', 100000);
        $messages[] = '<div style="color:green; padding:10px; background:#d4edda; margin-bottom:10px;">Merci, résultats enregistrés.</div>';
        
        if (!empty($_COOKIE['pass'])) {
            $messages[] = sprintf('<div style="color:#155724; padding:10px; background:#d4edda; margin-bottom:10px;">Vous pouvez <a href="login.php">vous connecter</a> avec le login <strong>%s</strong> et le mot de passe <strong>%s</strong> pour modifier vos données.</div>',
                e($_COOKIE['login']), e($_COOKIE['pass']));
        }
    }

    // Vérification session utilisateur
    if (!empty($_SESSION['login']) && !empty($_SESSION['uid'])) {
        $isLoggedIn = true;
        
        // Requête préparée (SQL Injection)
        $stmt = $pdo->prepare("SELECT application_id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            $stmt = $pdo->prepare("SELECT name, phone, email, birthdate, gender, biography, contract FROM application WHERE id = ?");
            $stmt->execute([$userData['application_id']]);
            $appData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($appData) {
                // Échappement des valeurs (XSS)
                $values['name'] = e($appData['name']);
                $values['phone'] = e($appData['phone']);
                $values['email'] = e($appData['email']);
                $values['birthdate'] = e($appData['birthdate']);
                $values['gender'] = e($appData['gender']);
                $values['biography'] = e($appData['biography']);
                $values['contract'] = $appData['contract'];
            }
            
            $stmt = $pdo->prepare("SELECT language_id FROM application_language WHERE application_id = ?");
            $stmt->execute([$userData['application_id']]);
            $langData = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $values['languages'] = $langData;
        }
        $messages[] = '<div style="color:#0c5460; padding:10px; background:#d1ecf1; margin-bottom:10px;">Connecté avec ' . e($_SESSION['login']) . ' | <a href="login.php?logout=1">Déconnexion</a></div>';
    }

    include('form.php');
}

// ========== MODIF SÉCURITÉ #2 : Traitement POST avec CSRF et validation ==========
else {
    $errors = false;
    $isLoggedIn = false;
    $userId = null;
    
    // ========== MODIF SÉCURITÉ #3 : Vérification CSRF ==========
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die('Erreur CSRF : requête invalide');
    }
    
    // ========== MODIF SÉCURITÉ #4 : Validation et nettoyage des entrées ==========
    // Validation du nom
    if (empty($_POST['name'])) {
        setcookie('name_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        // Nettoyage du nom (XSS)
        $cleanName = strip_tags(trim($_POST['name']));
        setcookie('name_value', $cleanName, time() + 30 * 24 * 60 * 60);
    }
    
    // Validation du téléphone
    if (empty($_POST['phone']) || !preg_match('/^[0-9+\-\s]{10,}$/', $_POST['phone'])) {
        setcookie('phone_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        // Nettoyage du téléphone
        $cleanPhone = preg_replace('/[^0-9+\-\s]/', '', $_POST['phone']);
        setcookie('phone_value', $cleanPhone, time() + 30 * 24 * 60 * 60);
    }
    
    // Validation de l'email
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        $cleanEmail = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        setcookie('email_value', $cleanEmail, time() + 30 * 24 * 60 * 60);
    }
    
    // Validation de la date
    if (empty($_POST['birthdate'])) {
        setcookie('birthdate_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('birthdate_value', $_POST['birthdate'], time() + 30 * 24 * 60 * 60);
    }
    
    // Validation du genre
    if (empty($_POST['gender'])) {
        setcookie('gender_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('gender_value', $_POST['gender'], time() + 30 * 24 * 60 * 60);
    }
    
    // Validation des langages
    if (empty($_POST['languages'])) {
        setcookie('languages_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        // Nettoyage des langages (ne garder que les entiers)
        $cleanLanguages = array_map('intval', $_POST['languages']);
        $languagesCookie = implode(',', $cleanLanguages);
        setcookie('languages_value', $languagesCookie, time() + 30 * 24 * 60 * 60);
    }
    
    // Validation de la biographie
    if (empty($_POST['biography']) || strlen($_POST['biography']) < 10) {
        setcookie('biography_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        $cleanBiography = strip_tags(trim($_POST['biography']));
        setcookie('biography_value', $cleanBiography, time() + 30 * 24 * 60 * 60);
    }
    
    // Validation du contrat
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
    
    // Supprimer les cookies d'erreur
    $fields = ['name', 'phone', 'email', 'birthdate', 'gender', 'languages', 'biography', 'contract'];
    foreach ($fields as $field) {
        setcookie($field . '_error', '', 100000);
    }
    
    // Vérification de session
    if (!empty($_SESSION['login']) && !empty($_SESSION['uid'])) {
        $isLoggedIn = true;
        $userId = $_SESSION['uid'];
    }
    
    $selectedLanguages = isset($_POST['languages']) ? array_map('intval', $_POST['languages']) : [];
    $contractValue = isset($_POST['contract']) ? '1' : '0';
    
    try {
        if ($isLoggedIn && $userId) {
            // Mise à jour avec requêtes préparées
            $stmt = $pdo->prepare("SELECT application_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                $applicationId = $userData['application_id'];
                
                $stmt = $pdo->prepare("UPDATE application SET name = ?, phone = ?, email = ?, birthdate = ?, gender = ?, biography = ?, contract = ? WHERE id = ?");
                $stmt->execute([
                    $cleanName, $cleanPhone, $cleanEmail, $_POST['birthdate'],
                    $_POST['gender'], $cleanBiography, $contractValue, $applicationId
                ]);
                
                $stmt = $pdo->prepare("DELETE FROM application_language WHERE application_id = ?");
                $stmt->execute([$applicationId]);
                
                foreach ($selectedLanguages as $langId) {
                    $stmt = $pdo->prepare("INSERT INTO application_language (application_id, language_id) VALUES (?, ?)");
                    $stmt->execute([$applicationId, $langId]);
                }
            }
        } else {
            // Insertion avec requêtes préparées
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO application (name, phone, email, birthdate, gender, biography, contract) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $cleanName, $cleanPhone, $cleanEmail, $_POST['birthdate'],
                $_POST['gender'], $cleanBiography, $contractValue
            ]);
            $applicationId = $pdo->lastInsertId();
            
            foreach ($selectedLanguages as $langId) {
                $stmt = $pdo->prepare("INSERT INTO application_language (application_id, language_id) VALUES (?, ?)");
                $stmt->execute([$applicationId, $langId]);
            }
            
            $login = generateUniqueLogin($pdo);
            $plainPassword = generateRandomPassword(8);
            $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (login, password_hash, application_id) VALUES (?, ?, ?)");
            $stmt->execute([$login, $passwordHash, $applicationId]);
            
            $pdo->commit();
            
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
        error_log($e->getMessage()); // Log seulement
        die("Erreur lors de l'enregistrement. Veuillez réessayer."); // Message générique
    }
}
?>
