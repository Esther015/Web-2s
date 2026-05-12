<?php
/**
 * Administration sécurisée
 * MODIFICATIONS DE SÉCURITÉ :
 * - Utilisation de password_hash() pour le mot de passe admin
 * - Ajout de la validation CSRF pour la suppression
 * - Ajout de htmlspecialchars() pour tous les affichages (XSS)
 * - Validation stricte des types avec filter_input()
 * - Messages d'erreur génériques (Information Disclosure)
 * - Utilisation de requêtes préparées PDO (SQL Injection)
 */

require_once 'config.php';

// ========== MODIF SÉCURITÉ #1 : Authentification admin sécurisée ==========
// Utilisation de password_hash() au lieu de md5()
// Pour générer le hash : password_hash('123', PASSWORD_DEFAULT)
if (empty($_SERVER['PHP_AUTH_USER']) ||
    empty($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] != 'admin' ||
    !password_verify($_SERVER['PHP_AUTH_PW'], '$2y$10$ExempleHashQueVousDevezGenerer')) { // Remplacez par votre hash
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Administration"');
    print('<h1>401 Authentification requise</h1>');
    exit();
}

// ========== MODIF SÉCURITÉ #2 : Traitement suppression avec CSRF ==========
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    
    // Vérification CSRF (protection contre les attaques cross-site)
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = '<div class="error">Token CSRF invalide</div>';
    } else {
        // Validation stricte du type de l'ID (SQL Injection)
        $deleteId = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
        if ($deleteId === false || $deleteId <= 0) {
            $message = '<div class="error">ID invalide</div>';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Toutes les requêtes sont préparées (SQL Injection)
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
                $message = '<div class="success">Enregistrement #' . e($deleteId) . ' supprimé avec succès.</div>';
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log($e->getMessage()); // Log uniquement, pas d'affichage (Information Disclosure)
                $message = '<div class="error">Erreur lors de la suppression.</div>'; // Message générique
            }
        }
    }
}

// ========== MODIF SÉCURITÉ #3 : Requêtes préparées pour la lecture ==========
try {
    // Requête préparée (SQL Injection)
    $stmt = $pdo->prepare("SELECT * FROM application ORDER BY id DESC");
    $stmt->execute();
    $applications = $stmt->fetchAll();
    
    // Ajout des langages avec requête préparée
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
    
    // Statistiques
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
    die('Erreur lors du chargement des données'); // Message générique
}

// ========== MODIF SÉCURITÉ #4 : Tous les affichages utilisent e() pour XSS ==========
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrateur</title>
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
    <h1>Panel Administrateur</h1>
    
    <?php echo $message; ?>
    
    <h2>Statistiques par langage</h2>
    <div class="stats">
        <?php foreach ($stats as $s): ?>
        <div class="stat-card">
            <div class="num"><?php echo e($s['count']); ?></div>  <!-- e() pour XSS -->
            <div class="lbl"><?php echo e($s['name']); ?></div>    <!-- e() pour XSS -->
        </div>
        <?php endforeach; ?>
    </div>
    
    <h2>Toutes les données (<?php echo count($applications); ?> enregistrements)</h2>
    
    <?php if (empty($applications)): ?>
        <p>Aucune donnée.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>ID</th><th>Nom</th><th>Téléphone</th><th>Email</th><th>Date naiss.</th><th>Genre</th><th>Langages</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $a): ?>
            <tr>
                <td><?php echo e($a['id']); ?></td>      <!-- e() pour XSS -->
                <td><?php echo e($a['name']); ?></td>    <!-- e() pour XSS -->
                <td><?php echo e($a['phone']); ?></td>   <!-- e() pour XSS -->
                <td><?php echo e($a['email']); ?></td>   <!-- e() pour XSS -->
                <td><?php echo e($a['birthdate']); ?></td> <!-- e() pour XSS -->
                <td><?php echo $a['gender'] === 'male' ? 'Homme' : 'Femme'; ?></td>
                <td>
                    <?php foreach ($a['languages'] as $l): ?>
                        <span class="badge"><?php echo e($l); ?></span>  <!-- e() pour XSS -->
                    <?php endforeach; ?>
                </td>
                <td>
                    <!-- ========== MODIF SÉCURITÉ #5 : Formulaire avec token CSRF ========== -->
                    <form method="post" onsubmit="return confirm('Supprimer #<?php echo e($a['id']); ?> ?');">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="delete_id" value="<?php echo e($a['id']); ?>">
                        <button type="submit" class="btn-del">Supprimer</button>
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
