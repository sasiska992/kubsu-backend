<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: DENY');

require_once 'config.php';

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = time();
}

if ($_SESSION['login_attempts'] > 5 && time() - $_SESSION['last_attempt'] < 300) {
    die('Слишком много попыток входа. Попробуйте позже.');
}

try {
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch (PDOException $e) {
    die('Ошибка системы. Попробуйте позже.');
}

$messages = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['pass'] ?? '');

    try {
        $stmt = $db->prepare("SELECT id, login, password_hash FROM users WHERE login = ? LIMIT 1");
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['login'] = $user['login'];
            $_SESSION['uid'] = $user['id'];
            $_SESSION['login_attempts'] = 0;
            session_regenerate_id(true);
            header('Location: index.php');
            exit();
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();
            $messages[] = 'Неверный логин или пароль';
        }
    } catch (PDOException $e) {
        $messages[] = 'Ошибка системы';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Вход в систему</title>
</head>
<body>
    <div class="form-container">
        <h1 class="text-center">Вход в систему</h1>
        
        <?php if (!empty($messages)): ?>
            <div class="alert alert-danger">
                <?php foreach ($messages as $message): ?>
                    <p><?= htmlspecialchars($message) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label class="form-label">Логин:</label>
                <input type="text" name="login" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Пароль:</label>
                <input type="password" name="pass" class="form-control" required>
            </div>
            
            <div class="form-group text-center">
                <button type="submit" class="btn btn-primary">Войти</button>
            </div>
        </form>
        
        <p class="text-center mt-3">Нет аккаунта? <a href="index.php">Заполните форму</a></p>
    </div>
</body>
</html>