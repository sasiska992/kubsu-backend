<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// Подключение к БД с вашими данными
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

// Получение списка языков из таблицы languages
function getLangs($db) {
    try {
        $stmt = $db->query("SELECT id, name FROM languages");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        die('Ошибка загрузки языков: ' . $e->getMessage());
    }
}

$allowed_lang = getLangs($db);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Обработка GET-запроса (показать форму)
    $messages = [];
    $errors = [];
    $values = [];

    $fields = ['full_name', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'languages', 'agreement'];
    
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field.'_error']);
        $values[$field] = $_COOKIE[$field.'_value'] ?? '';
    }

    // Очистка ошибок
    foreach ($fields as $field) {
        setcookie($field.'_error', '', time() - 3600);
    }

    // Загрузка данных пользователя, если он авторизован
    if (!empty($_SESSION['login'])) {
        try {
            $stmt = $db->prepare("
                SELECT a.* FROM application a
                JOIN users u ON a.id = u.application_id
                WHERE u.login = ?
            ");
            $stmt->execute([$_SESSION['login']]);
            $application = $stmt->fetch();

            if ($application) {
                $values['full_name'] = $application['full_name'];
                $values['phone'] = $application['phone'];
                $values['email'] = $application['email'];
                $values['gender'] = $application['gender'];
                $values['biography'] = $application['biography'];
                $values['agreement'] = $application['agreement'];
                
                // Разбиваем дату на компоненты
                $birthDate = new DateTime($application['birth_date']);
                $values['birth_day'] = $birthDate->format('d');
                $values['birth_month'] = $birthDate->format('m');
                $values['birth_year'] = $birthDate->format('Y');
                
                // Получаем выбранные языки
                $stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
                $stmt->execute([$application['id']]);
                $selectedLangs = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $values['languages'] = implode(',', $selectedLangs);
            }
        } catch (PDOException $e) {
            die('Ошибка загрузки данных: ' . $e->getMessage());
        }
    }

    include('form.php');
    exit();
}

// Обработка POST-запроса (отправка формы)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = FALSE;
    
    // Получение и валидация данных
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $day = trim($_POST['birth_day'] ?? '');
    $month = trim($_POST['birth_month'] ?? '');
    $year = trim($_POST['birth_year'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $languages = $_POST['languages'] ?? [];
    $agreement = isset($_POST['agreement']) ? 1 : 0;

    // Валидация данных (сохранено из вашего кода)
    // ... (ваш код валидации с setcookie для ошибок)

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    // Если ошибок нет - сохраняем данные
    try {
        $birth_date = sprintf("%04d-%02d-%02d", $year, $month, $day);
        
        if (!empty($_SESSION['login'])) {
            // Обновление существующей заявки
            $db->beginTransaction();
            
            // Обновляем основную информацию
            $stmt = $db->prepare("
                UPDATE application SET 
                full_name = ?, phone = ?, email = ?, birth_date = ?, 
                gender = ?, biography = ?, agreement = ?
                WHERE id = (SELECT application_id FROM users WHERE login = ?)
            ");
            $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, $agreement, $_SESSION['login']]);
            
            // Получаем ID заявки
            $stmt = $db->prepare("SELECT application_id FROM users WHERE login = ?");
            $stmt->execute([$_SESSION['login']]);
            $app_id = $stmt->fetchColumn();
            
            // Обновляем языки
            $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$app_id]);
            
            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languages as $lang_id) {
                $stmt->execute([$app_id, $lang_id]);
            }
            
            $db->commit();
        } else {
            // Создание новой заявки
            $db->beginTransaction();
            
            // Вставляем основную информацию
            $stmt = $db->prepare("
                INSERT INTO application 
                (full_name, phone, email, birth_date, gender, biography, agreement) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, $agreement]);
            $app_id = $db->lastInsertId();
            
            // Добавляем языки
            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languages as $lang_id) {
                $stmt->execute([$app_id, $lang_id]);
            }
            
            // Создаем пользователя
            $login = uniqid('user_');
            $password = bin2hex(random_bytes(8));
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO users (login, password_hash, application_id) VALUES (?, ?, ?)");
            $stmt->execute([$login, $pass_hash, $app_id]);
            
            $_SESSION['generated_login'] = $login;
            $_SESSION['generated_password'] = $password;
            
            $db->commit();
        }
        
        setcookie('save', '1', time() + 86400);
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        die('Ошибка сохранения: ' . $e->getMessage());
    }
}
?>