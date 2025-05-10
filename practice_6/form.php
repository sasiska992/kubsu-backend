<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Форма заявки</title>
</head>
<body>
    <div class="auth-buttons">
        <?php if (!empty($_SESSION['login'])): ?>
            <a href="logout.php" class="btn btn-secondary">Выйти</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-secondary">Войти</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION['generated_login']) && !empty($_SESSION['generated_password']) && empty($_SESSION['login'])): ?>
        <div class="alert alert-info">
            <h3>Ваши учетные данные:</h3>
            <p><strong>Логин:</strong> <?= htmlspecialchars($_SESSION['generated_login']) ?></p>
            <p><strong>Пароль:</strong> <?= htmlspecialchars($_SESSION['generated_password']) ?></p>
            <p>Используйте их для входа в следующий раз.</p>
        </div>
        <?php 
            unset($_SESSION['generated_login']);
            unset($_SESSION['generated_password']);
        ?>
    <?php endif; ?>

    <form action="index.php" method="POST" class="form-container">
        <h1 class="text-center">Форма заявки</h1>

        <!-- Поля формы с классами Bootstrap -->
        <div class="form-group">
            <label class="form-label">ФИО:</label>
            <input type="text" name="full_name" class="form-control <?= !empty($errors['full_name']) ? 'is-invalid' : '' ?>" 
                   value="<?= htmlspecialchars($values['full_name'] ?? '') ?>" required>
            <?php if (!empty($messages['full_name'])): ?>
                <div class="error-message"><?= $messages['full_name'] ?></div>
            <?php endif; ?>
        </div>

        <!-- Остальные поля формы аналогично -->

        <div class="form-group text-center">
            <button type="submit" class="btn btn-primary">
                <?= !empty($_SESSION['login']) ? 'Обновить данные' : 'Сохранить' ?>
            </button>
        </div>
    </form>
</body>
</html>