<?php
/**
 * Главная страница с формой
 * МОДИФИКАЦИИ БЕЗОПАСНОСТИ :
 * - Добавлен CSRF-токен в форму
 * - Проверка CSRF перед обработкой
 * - Очистка входных данных с filter_var()
 * - Использование подготовленных запросов для всех SQL-запросов
 * - Экранирование всего вывода через e()
 * - Безопасная сессия
 */

require_once 'config.php';

header('Content-Type: text/html; charset=UTF-8');

// ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #1 : Получение данных с экранированием ==========
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
            // Экранирование значений cookies (XSS)
            $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : e($_COOKIE[$field . '_value']);
        }
    }

    // Отображение сообщений об успехе с экранированием
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        setcookie('login', '', 100000);
        setcookie('pass', '', 100000);
        $messages[] = '<div style="color:green; padding:10px; background:#d4edda; margin-bottom:10px;">Спасибо, результаты сохранены.</div>';
        
        if (!empty($_COOKIE['pass'])) {
            $messages[] = sprintf('<div style="color:#155724; padding:10px; background:#d4edda; margin-bottom:10px;">Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong> и паролем <strong>%s</strong> для изменения данных.</div>',
                e($_COOKIE['login']), e($_COOKIE['pass']));
        }
    }

    // Проверка сессии пользователя
    if (!empty($_SESSION['login']) && !empty($_SESSION['uid'])) {
        $isLoggedIn = true;
        
        // Подготовленный запрос (SQL Injection)
        $stmt = $pdo->prepare("SELECT application_id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            $stmt = $pdo->prepare("SELECT name, phone, email, birthdate, gender, biography, contract FROM application WHERE id = ?");
            $stmt->execute([$userData['application_id']]);
            $appData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($appData) {
                // Экранирование значений (XSS)
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
        $messages[] = '<div style="color:#0c5460; padding:10px; background:#d1ecf1; margin-bottom:10px;">Вход с логином ' . e($_SESSION['login']) . ' | <a href="login.php?logout=1">Выйти</a></div>';
    }

    include('form.php');
}

// ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #2 : Обработка POST с CSRF и валидацией ==========
else {
    $errors = false;
    $isLoggedIn = false;
    $userId = null;
    
    // ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #3 : Проверка CSRF ==========
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die('Ошибка CSRF: недействительный запрос');
    }
    
    // ========== МОДИФИКАЦИЯ БЕЗОПАСНОСТИ #4 : Валидация и очистка входных данных ==========
    // Валидация имени
    if (empty($_POST['name'])) {
        setcookie('name_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        $cleanName = strip_tags(trim($_POST['name']));
        setcookie('name_value', $cleanName, time() + 30 * 24 * 60 * 60);
    }
    
    // Валидация телефона
    if (empty($_POST['phone']) || !preg_match('/^[0-9+\-\s]{10,}$/', $_POST['phone'])) {
        setcookie('phone_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        $cleanPhone = preg_replace('/[^0-9+\-\s]/', '', $_POST['phone']);
        setcookie('phone_value', $cleanPhone, time() + 30 * 24 * 60 * 60);
    }
    
    // Валидация email
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        $cleanEmail = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        setcookie('email_value', $cleanEmail, time() + 30 * 24 * 60 * 60);
    }
    
    // Валидация даты
    if (empty($_POST['birthdate'])) {
        setcookie('birthdate_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('birthdate_value', $_POST['birthdate'], time() + 30 * 24 * 60 * 60);
    }
    
    // Валидация пола
    if (empty($_POST['gender'])) {
        setcookie('gender_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        setcookie('gender_value', $_POST['gender'], time() + 30 * 24 * 60 * 60);
    }
    
    // Валидация языков
    if (empty($_POST['languages'])) {
        setcookie('languages_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        $cleanLanguages = array_map('intval', $_POST['languages']);
        $languagesCookie = implode(',', $cleanLanguages);
        setcookie('languages_value', $languagesCookie, time() + 30 * 24 * 60 * 60);
    }
    
    // Валидация биографии
    if (empty($_POST['biography']) || strlen($_POST['biography']) < 10) {
        setcookie('biography_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        $cleanBiography = strip_tags(trim($_POST['biography']));
        setcookie('biography_value', $cleanBiography, time() + 30 * 24 * 60 * 60);
    }
    
    // Валидация контракта
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
    
    // Удаление cookies ошибок
    $fields = ['name', 'phone', 'email', 'birthdate', 'gender', 'languages', 'biography', 'contract'];
    foreach ($fields as $field) {
        setcookie($field . '_error', '', 100000);
    }
    
    // Проверка сессии
    if (!empty($_SESSION['login']) && !empty($_SESSION['uid'])) {
        $isLoggedIn = true;
        $userId = $_SESSION['uid'];
    }
    
    $selectedLanguages = isset($_POST['languages']) ? array_map('intval', $_POST['languages']) : [];
    $contractValue = isset($_POST['contract']) ? '1' : '0';
    
    try {
        if ($isLoggedIn && $userId) {
            // Обновление с подготовленными запросами
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
            // Вставка с подготовленными запросами
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
        error_log($e->getMessage()); // Только в лог
        die("Ошибка при сохранении. Пожалуйста, попробуйте снова."); // Общее сообщение
    }
}
?>
