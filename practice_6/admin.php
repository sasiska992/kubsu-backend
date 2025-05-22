<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once 'config.php';

try {
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch (PDOException $e) {
    die('Ошибка подключения к базе данных');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    die('<h1>Требуется авторизация</h1>');
}

$admin_login = $_SERVER['PHP_AUTH_USER'];
$admin_pass = $_SERVER['PHP_AUTH_PW'];

if (!($admin_login === ADMIN_LOGIN && password_verify($admin_pass, ADMIN_PASS_HASH))) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    die('<h1>Ошибка авторизации</h1><p>Неверный логин или пароль.</p>');
}

if (isset($_GET['logout'])) {
    session_regenerate_id(true);
    session_destroy();
    header('Location: index.php');
    exit();
}

function getAllApplications($db) {
    $stmt = $db->query("SELECT a.*, u.login FROM application a JOIN users u ON a.id = u.application_id ORDER BY a.id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getApplicationLanguages($db, $application_id) {
    $stmt = $db->prepare("SELECT l.id, l.name FROM application_languages al JOIN languages l ON al.language_id = l.id WHERE al.application_id = ?");
    $stmt->execute([$application_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLanguagesStatistics($db) {
    $stmt = $db->query("SELECT l.id, l.name, COUNT(al.application_id) as user_count FROM languages l LEFT JOIN application_languages al ON l.id = al.language_id GROUP BY l.id, l.name ORDER BY user_count DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllLanguages($db) {
    $stmt = $db->query("SELECT id, name FROM languages ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (isset($_GET['delete'])) {
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Недействительный CSRF-токен');
    }
    
    $id = (int)$_GET['delete'];
    
    try {
        $db->beginTransaction();
        $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->execute([$id]);
        $stmt = $db->prepare("DELETE FROM users WHERE application_id = ?");
        $stmt->execute([$id]);
        $stmt = $db->prepare("DELETE FROM application WHERE id = ?");
        $stmt->execute([$id]);
        $db->commit();
        header('Location: admin.php');
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        die('Ошибка при удалении');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_application'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Недействительный CSRF-токен');
    }
    
    $id = (int)$_POST['id'];
    $fields = [
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'patronymic' => trim($_POST['patronymic']),
        'phone' => trim($_POST['phone']),
        'email' => trim($_POST['email']),
        'dob' => $_POST['dob'],
        'gender' => $_POST['gender'],
        'bio' => trim($_POST['bio']),
        'languages' => $_POST['languages'] ?? []
    ];
    
    try {
        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE application SET first_name = ?, last_name = ?, patronymic = ?, phone = ?, email = ?, dob = ?, gender = ?, bio = ? WHERE id = ?");
        $stmt->execute(array_values(array_slice($fields, 0, 8) + [$id]));
        
        $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($fields['languages'] as $language_id) {
            $stmt->execute([$id, (int)$language_id]);
        }
        
        $db->commit();
        header("Location: admin.php");
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        die('Ошибка при обновлении');
    }
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM application WHERE id = ?");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($edit_data) {
        $edit_data['languages'] = array_column(getApplicationLanguages($db, $id), 'id');
    }
}

$applications = getAllApplications($db);
$statistics = getLanguagesStatistics($db);
$all_languages = getAllLanguages($db);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Административная панель</title>
    <style>
        .admin-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .edit-form { background-color: #f9f9f9; padding: 20px; margin-bottom: 30px; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="tel"], .form-group input[type="date"], .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .language-options { display: flex; flex-wrap: wrap; gap: 10px; }
        .language-option { display: flex; align-items: center; }
        .btn { padding: 8px 15px; border-radius: 4px; text-decoration: none; color: white; cursor: pointer; border: none; }
        .btn-primary { background-color: #0d6efd; }
        .btn-danger { background-color: #dc3545; }
        .btn-secondary { background-color: #6c757d; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Административная панель</h1>
            <a href="admin.php?logout=1" class="btn btn-danger">Выйти</a>
        </div>
        
        <?php if ($edit_data): ?>
            <div class="edit-form">
                <h2>Редактирование заявки #<?= htmlspecialchars($edit_data['id']) ?></h2>
                <form method="post">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id']) ?>">
                    <input type="hidden" name="edit_application" value="1">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="form-group">
                        <label>Имя:</label>
                        <input type="text" name="first_name" required value="<?= htmlspecialchars($edit_data['first_name']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Фамилия:</label>
                        <input type="text" name="last_name" required value="<?= htmlspecialchars($edit_data['last_name']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Отчество:</label>
                        <input type="text" name="patronymic" value="<?= htmlspecialchars($edit_data['patronymic']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Телефон:</label>
                        <input type="tel" name="phone" required value="<?= htmlspecialchars($edit_data['phone']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($edit_data['email']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Дата рождения:</label>
                        <input type="date" name="dob" required value="<?= htmlspecialchars($edit_data['dob']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Пол:</label>
                        <label><input type="radio" name="gender" value="male" <?= $edit_data['gender'] == 'male' ? 'checked' : '' ?>> Мужской</label>
                        <label><input type="radio" name="gender" value="female" <?= $edit_data['gender'] == 'female' ? 'checked' : '' ?>> Женский</label>
                    </div>
                    
                    <div class="form-group">
                        <label>Биография:</label>
                        <textarea name="bio"><?= htmlspecialchars($edit_data['bio']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Языки программирования:</label>
                        <div class="language-options">
                            <?php foreach ($all_languages as $lang): ?>
                                <div class="language-option">
                                    <input type="checkbox" name="languages[]" value="<?= $lang['id'] ?>" <?= in_array($lang['id'], $edit_data['languages']) ? 'checked' : '' ?>>
                                    <label><?= htmlspecialchars($lang['name']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                    <a href="admin.php" class="btn btn-secondary">Отмена</a>
                </form>
            </div>
        <?php endif; ?>
        
        <h2>Все заявки</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логин</th>
                        <th>ФИО</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Дата рождения</th>
                        <th>Пол</th>
                        <th>Языки</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?= htmlspecialchars($app['id']) ?></td>
                            <td><?= htmlspecialchars($app['login']) ?></td>
                            <td><?= htmlspecialchars($app['last_name'] . ' ' . $app['first_name'] . ' ' . $app['patronymic']) ?></td>
                            <td><?= htmlspecialchars($app['phone']) ?></td>
                            <td><?= htmlspecialchars($app['email']) ?></td>
                            <td><?= htmlspecialchars($app['dob']) ?></td>
                            <td><?= $app['gender'] == 'male' ? 'Мужской' : 'Женский' ?></td>
                            <td><?= htmlspecialchars(implode(', ', array_column(getApplicationLanguages($db, $app['id']), 'name'))) ?></td>
                            <td>
                                <a href="admin.php?edit=<?= $app['id'] ?>" class="btn btn-primary">Редактировать</a>
                                <a href="admin.php?delete=<?= $app['id'] ?>&csrf_token=<?= urlencode($_SESSION['csrf_token']) ?>" class="btn btn-danger" onclick="return confirm('Вы уверены, что хотите удалить эту заявку?')">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <h2>Статистика по языкам программирования</h2>
        <table>
            <thead>
                <tr>
                    <th>Язык</th>
                    <th>Количество пользователей</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statistics as $stat): ?>
                    <tr>
                        <td><?= htmlspecialchars($stat['name']) ?></td>
                        <td><?= htmlspecialchars($stat['user_count']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>