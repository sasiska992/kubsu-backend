<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// Подключение к БД
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
    $messages = [];
    $errors = [];
    $values = [];

    $fields = ['first_name', 'last_name', 'patronymic', 'phone', 'email', 'dob', 'gender', 'bio', 'languages'];
    
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field.'_error']);
        $values[$field] = $_COOKIE[$field.'_value'] ?? '';
    }

    foreach ($fields as $field) {
        setcookie($field.'_error', '', time() - 3600);
    }

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
                $values['first_name'] = $application['first_name'];
                $values['last_name'] = $application['last_name'];
                $values['patronymic'] = $application['patronymic'];
                $values['phone'] = $application['phone'];
                $values['email'] = $application['email'];
                $values['dob'] = $application['dob'];
                $values['gender'] = $application['gender'];
                $values['bio'] = $application['bio'];
                
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = FALSE;
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $patronymic = trim($_POST['patronymic'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $bio = trim($_POST['bio'] ?? '');
    $languages = $_POST['languages'] ?? [];

    // Валидация данных
    if (empty($first_name)) {
        setcookie('first_name_error', '1', time() + 86400);
        $errors = TRUE;
    }
    setcookie('first_name_value', $first_name, time() + 86400 * 365);

    if (empty($last_name)) {
        setcookie('last_name_error', '1', time() + 86400);
        $errors = TRUE;
    }
    setcookie('last_name_value', $last_name, time() + 86400 * 365);

    // ... (добавьте валидацию для остальных полей)

    if (empty($languages)) {
        setcookie('languages_error', '1', time() + 86400);
        $errors = TRUE;
    }
    setcookie('languages_value', implode(',', $languages), time() + 86400 * 365);

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    try {
        if (!empty($_SESSION['login'])) {
            // Обновление данных
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                UPDATE application SET 
                first_name = ?, last_name = ?, patronymic = ?, phone = ?, 
                email = ?, dob = ?, gender = ?, bio = ?
                WHERE id = (SELECT application_id FROM users WHERE login = ?)
            ");
            $stmt->execute([$first_name, $last_name, $patronymic, $phone, $email, $dob, $gender, $bio, $_SESSION['login']]);
            
            $stmt = $db->prepare("SELECT application_id FROM users WHERE login = ?");
            $stmt->execute([$_SESSION['login']]);
            $app_id = $stmt->fetchColumn();
            
            $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$app_id]);
            
            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languages as $lang_id) {
                $stmt->execute([$app_id, $lang_id]);
            }
            
            $db->commit();
        } else {
            // Новая заявка
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO application 
                (first_name, last_name, patronymic, phone, email, dob, gender, bio)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$first_name, $last_name, $patronymic, $phone, $email, $dob, $gender, $bio]);
            $app_id = $db->lastInsertId();
            
            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languages as $lang_id) {
                $stmt->execute([$app_id, $lang_id]);
            }
            
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