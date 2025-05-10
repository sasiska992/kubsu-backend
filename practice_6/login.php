<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}

// Подключение с вашими данными
$user = 'u68763';
$pass = '7680994';
$dbname = 'u68763';

try {
    $db = new PDO("mysql:host=localhost;dbname=$dbname", $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}

$messages = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['pass'] ?? '');

    try {
        $stmt = $db->prepare("SELECT id, login, password_hash FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['login'] = $user['login'];
            $_SESSION['uid'] = $user['id'];
            header('Location: index.php');
            exit();
        } else {
            $messages[] = 'Неверный логин или пароль';
        }
    } catch (PDOException $e) {
        $messages[] = 'Ошибка при входе в систему';
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