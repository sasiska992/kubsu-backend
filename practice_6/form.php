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
        </div>
        <?php 
            unset($_SESSION['generated_login']);
            unset($_SESSION['generated_password']);
        ?>
    <?php endif; ?>

    <form action="index.php" method="POST" class="form-container">
        <h1 class="text-center">Форма заявки</h1>

        <div class="form-group">
            <label class="form-label">Имя:</label>
            <input type="text" name="first_name" class="form-control <?= !empty($errors['first_name']) ? 'is-invalid' : '' ?>" 
                   value="<?= htmlspecialchars($values['first_name'] ?? '') ?>" required>
            <?php if (!empty($messages['first_name'])): ?>
                <div class="error-message"><?= $messages['first_name'] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label">Фамилия:</label>
            <input type="text" name="last_name" class="form-control <?= !empty($errors['last_name']) ? 'is-invalid' : '' ?>" 
                   value="<?= htmlspecialchars($values['last_name'] ?? '') ?>" required>
            <?php if (!empty($messages['last_name'])): ?>
                <div class="error-message"><?= $messages['last_name'] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label">Отчество:</label>
            <input type="text" name="patronymic" class="form-control <?= !empty($errors['patronymic']) ? 'is-invalid' : '' ?>" 
                   value="<?= htmlspecialchars($values['patronymic'] ?? '') ?>">
            <?php if (!empty($messages['patronymic'])): ?>
                <div class="error-message"><?= $messages['patronymic'] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label">Телефон:</label>
            <input type="tel" name="phone" class="form-control <?= !empty($errors['phone']) ? 'is-invalid' : '' ?>" 
                   value="<?= htmlspecialchars($values['phone'] ?? '') ?>" placeholder="+71234567890" required>
            <?php if (!empty($messages['phone'])): ?>
                <div class="error-message"><?= $messages['phone'] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label">Email:</label>
            <input type="email" name="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" 
                   value="<?= htmlspecialchars($values['email'] ?? '') ?>" required>
            <?php if (!empty($messages['email'])): ?>
                <div class="error-message"><?= $messages['email'] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label">Дата рождения:</label>
            <input type="date" name="dob" class="form-control <?= !empty($errors['dob']) ? 'is-invalid' : '' ?>" 
                   value="<?= htmlspecialchars($values['dob'] ?? '') ?>" required>
            <?php if (!empty($messages['dob'])): ?>
                <div class="error-message"><?= $messages['dob'] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label">Пол:</label>
            <div class="gender-options">
                <label>
                    <input type="radio" name="gender" value="male" 
                           <?= ($values['gender'] ?? '') === 'male' ? 'checked' : '' ?>>
                    Мужской
                </label>
                <label>
                    <input type="radio" name="gender" value="female" 
                           <?= ($values['gender'] ?? '') === 'female' ? 'checked' : '' ?>>
                    Женский
                </label>
            </div>
            <?php if (!empty($messages['gender'])): ?>
                <div class="error-message"><?= $messages['gender'] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label">Любимые языки программирования:</label>
            <select name="languages[]" multiple class="form-control <?= !empty($errors['languages']) ? 'is-invalid' : '' ?>">
                <?php foreach ($allowed_lang as $id => $name): ?>
                    <option value="<?= $id ?>"
                        <?= in_array($id, explode(',', $values['languages'] ?? '')) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($messages['languages'])): ?>
                <div class="error-message"><?= $messages['languages'] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label">Биография:</label>
            <textarea name="bio" class="form-control <?= !empty($errors['bio']) ? 'is-invalid' : '' ?>" 
                      rows="5"><?= htmlspecialchars($values['bio'] ?? '') ?></textarea>
            <?php if (!empty($messages['bio'])): ?>
                <div class="error-message"><?= $messages['bio'] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group text-center">
            <button type="submit" class="btn btn-primary">
                <?= !empty($_SESSION['login']) ? 'Обновить данные' : 'Сохранить' ?>
            </button>
        </div>
    </form>
</body>
</html>