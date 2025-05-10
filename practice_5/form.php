<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Задание 5</title>
</head>
<body>
    <!-- Кнопки авторизации в правом верхнем углу -->
    <div class="auth-buttons">
        <?php if (!empty($_SESSION['login'])): ?>
            <input type="button" value="Выйти" onclick="location.href='logout.php'" class="auth-btn">
        <?php else: ?>
            <input type="button" value="Войти" onclick="location.href='login.php'" class="auth-btn">
        <?php endif; ?>
    </div>

    <!-- Блок сгенерированных учетных данных (показывается один раз после регистрации) -->
    <?php if (!empty($_SESSION['generated_login']) && !empty($_SESSION['generated_password']) && empty($_SESSION['login'])): ?>
        <div class="credentials">
            <h3>Ваши учетные данные:</h3>
            <p><strong>Логин:</strong> <?php echo htmlspecialchars($_SESSION['generated_login']); ?></p>
            <p><strong>Пароль:</strong> <?php echo htmlspecialchars($_SESSION['generated_password']); ?></p>
            <p>Используйте их для входа в следующий раз.</p>
        </div>
        <?php 
            unset($_SESSION['generated_login']);
            unset($_SESSION['generated_password']);
        ?>
    <?php endif; ?>

    <!-- Основная форма -->
    <form action="index.php" method="POST">
        <h1>Форма</h1>

        <!-- Поле Имя -->
        <div class="form-group">
            <label for="first_name">Имя:</label>
            <input type="text" id="first_name" name="first_name" 
                   placeholder="Введите ваше имя" 
                   required maxlength="128"
                   value="<?php echo htmlspecialchars($values['first_name'] ?? ''); ?>"
                   <?php if (!empty($errors['first_name'])) echo 'class="error"'; ?>>
            <?php if (!empty($messages['first_name'])): ?>
                <div class="error-message"><?php echo $messages['first_name']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Фамилия -->
        <div class="form-group">
            <label for="last_name">Фамилия:</label>
            <input type="text" id="last_name" name="last_name" 
                   placeholder="Введите вашу фамилию" 
                   required maxlength="128"
                   value="<?php echo htmlspecialchars($values['last_name'] ?? ''); ?>"
                   <?php if (!empty($errors['last_name'])) echo 'class="error"'; ?>>
            <?php if (!empty($messages['last_name'])): ?>
                <div class="error-message"><?php echo $messages['last_name']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Отчество -->
        <div class="form-group">
            <label for="patronymic">Отчество:</label>
            <input type="text" id="patronymic" name="patronymic" 
                   placeholder="Введите ваше отчество" 
                   maxlength="128"
                   value="<?php echo htmlspecialchars($values['patronymic'] ?? ''); ?>"
                   <?php if (!empty($errors['patronymic'])) echo 'class="error"'; ?>>
            <?php if (!empty($messages['patronymic'])): ?>
                <div class="error-message"><?php echo $messages['patronymic']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Телефон -->
        <div class="form-group">
            <label for="phone">Телефон:</label>
            <input type="tel" id="phone" name="phone" 
                   placeholder="+7XXXXXXXXXX" 
                   required
                   value="<?php echo htmlspecialchars($values['phone'] ?? ''); ?>"
                   <?php if (!empty($errors['phone'])) echo 'class="error"'; ?>>
            <?php if (!empty($messages['phone'])): ?>
                <div class="error-message"><?php echo $messages['phone']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Email -->
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" 
                   placeholder="example@domain.com" 
                   required
                   value="<?php echo htmlspecialchars($values['email'] ?? ''); ?>"
                   <?php if (!empty($errors['email'])) echo 'class="error"'; ?>>
            <?php if (!empty($messages['email'])): ?>
                <div class="error-message"><?php echo $messages['email']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Дата рождения -->
        <div class="form-group">
            <label for="dob">Дата рождения:</label>
            <input type="date" id="dob" name="dob" 
                   required
                   value="<?php echo htmlspecialchars($values['dob'] ?? ''); ?>"
                   <?php if (!empty($errors['dob'])) echo 'class="error"'; ?>>
            <?php if (!empty($messages['dob'])): ?>
                <div class="error-message"><?php echo $messages['dob']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Пол -->
        <div class="form-group">
            <label>Пол:</label>
            <div class="gender-options">
                <label>
                    <input type="radio" name="gender" value="male" 
                           <?php if (($values['gender'] ?? '') === 'male') echo 'checked'; ?>
                           <?php if (!empty($errors['gender'])) echo 'class="error"'; ?>>
                    Мужской
                </label>
                <label>
                    <input type="radio" name="gender" value="female" 
                           <?php if (($values['gender'] ?? '') === 'female') echo 'checked'; ?>
                           <?php if (!empty($errors['gender'])) echo 'class="error"'; ?>>
                    Женский
                </label>
            </div>
            <?php if (!empty($messages['gender'])): ?>
                <div class="error-message"><?php echo $messages['gender']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Языки программирования -->
        <div class="form-group">
            <label for="languages">Любимые языки программирования:</label>
            <select id="languages" name="languages[]" multiple 
                    <?php if (!empty($errors['languages'])) echo 'class="error"'; ?>>
                <?php foreach ($allowed_lang as $id => $name): ?>
                    <option value="<?php echo $id; ?>"
                        <?php if (in_array($id, explode(',', $values['languages'] ?? ''))) echo 'selected'; ?>>
                        <?php echo $name; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($messages['languages'])): ?>
                <div class="error-message"><?php echo $messages['languages']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Биография -->
        <div class="form-group">
            <label for="bio">Биография:</label>
            <textarea id="bio" name="bio" 
                      placeholder="Расскажите о себе..."
                      <?php if (!empty($errors['bio'])) echo 'class="error"'; ?>><?php 
                echo htmlspecialchars($values['bio'] ?? ''); 
            ?></textarea>
            <?php if (!empty($messages['bio'])): ?>
                <div class="error-message"><?php echo $messages['bio']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Кнопка отправки формы -->
        <div class="form-actions">
            <input type="submit" value="<?php 
                echo !empty($_SESSION['login']) ? 'Обновить данные' : 'Сохранить'; 
            ?>">
        </div>
    </form>
</body>
</html>