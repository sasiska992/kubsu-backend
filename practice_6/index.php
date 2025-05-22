<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: DENY');

require_once 'config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch (PDOException $e) {
    die('Ошибка системы. Попробуйте позже.');
}

function getLangs($db) {
    try {
        $stmt = $db->query("SELECT id, name FROM languages");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        die('Ошибка загрузки языков');
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
            $stmt = $db->prepare("SELECT a.* FROM application a JOIN users u ON a.id = u.application_id WHERE u.login = ?");
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
            die('Ошибка загрузки данных');
        }
    }

    include('form.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Недействительный запрос');
    }

    $errors = FALSE;
    $fields = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'patronymic' => trim($_POST['patronymic'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'dob' => trim($_POST['dob'] ?? ''),
        'gender' => $_POST['gender'] ?? '',
        'bio' => trim($_POST['bio'] ?? ''),
        'languages' => $_POST['languages'] ?? []
    ];

    if (empty($fields['first_name'])) {
        setcookie('first_name_error', '1', time() + 86400);
        $errors = TRUE;
    }
    setcookie('first_name_value', $fields['first_name'], time() + 86400 * 365);

    if (empty($fields['last_name'])) {
        setcookie('last_name_error', '1', time() + 86400);
        $errors = TRUE;
    }
    setcookie('last_name_value', $fields['last_name'], time() + 86400 * 365);

    if (!empty($fields['patronymic']) && strlen($fields['patronymic']) > 128) {
        setcookie('patronymic_error', '1', time() + 86400);
        $errors = TRUE;
    }
    setcookie('patronymic_value', $fields['patronymic'], time() + 86400 * 365);

    if (empty($fields['phone'])) {
        setcookie('phone_error', '1', time() + 86400);
        $errors = TRUE;
    } elseif (!preg_match('/^\+7\d{10}$/', $fields['phone'])) {
        setcookie('phone_error', '2', time() + 86400);
        $errors = TRUE;
    }
    setcookie('phone_value', $fields['phone'], time() + 86400 * 365);

    if (empty($fields['email'])) {
        setcookie('email_error', '1', time() + 86400);
        $errors = TRUE;
    } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '2', time() + 86400);
        $errors = TRUE;
    }
    setcookie('email_value', $fields['email'], time() + 86400 * 365);

    if (empty($fields['dob'])) {
        setcookie('dob_error', '1', time() + 86400);
        $errors = TRUE;
    }
    setcookie('dob_value', $fields['dob'], time() + 86400 * 365);

    if (empty($fields['gender'])) {
        setcookie('gender_error', '1', time() + 86400);
        $errors = TRUE;
    } elseif (!in_array($fields['gender'], ['male', 'female'])) {
        setcookie('gender_error', '2', time() + 86400);
        $errors = TRUE;
    }
    setcookie('gender_value', $fields['gender'], time() + 86400 * 365);

    if (empty($fields['bio'])) {
        setcookie('bio_error', '1', time() + 86400);
        $errors = TRUE;
    } elseif (strlen($fields['bio']) > 512) {
        setcookie('bio_error', '2', time() + 86400);
        $errors = TRUE;
    }
    setcookie('bio_value', $fields['bio'], time() + 86400 * 365);

    if (empty($fields['languages'])) {
        setcookie('languages_error', '1', time() + 86400);
        $errors = TRUE;
    }
    setcookie('languages_value', implode(',', $fields['languages']), time() + 86400 * 365);

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    try {
        if (!empty($_SESSION['login'])) {
            $db->beginTransaction();
            
            $stmt = $db->prepare("UPDATE application SET first_name = ?, last_name = ?, patronymic = ?, phone = ?, email = ?, dob = ?, gender = ?, bio = ? WHERE id = (SELECT application_id FROM users WHERE login = ?)");
            $stmt->execute([$fields['first_name'], $fields['last_name'], $fields['patronymic'], $fields['phone'], $fields['email'], $fields['dob'], $fields['gender'], $fields['bio'], $_SESSION['login']]);
            
            $stmt = $db->prepare("SELECT application_id FROM users WHERE login = ?");
            $stmt->execute([$_SESSION['login']]);
            $app_id = $stmt->fetchColumn();
            
            $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$app_id]);
            
            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($fields['languages'] as $lang_id) {
                $stmt->execute([$app_id, $lang_id]);
            }
            
            $db->commit();
        } else {
            $db->beginTransaction();
            
            $stmt = $db->prepare("INSERT INTO application (first_name, last_name, patronymic, phone, email, dob, gender, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fields['first_name'], $fields['last_name'], $fields['patronymic'], $fields['phone'], $fields['email'], $fields['dob'], $fields['gender'], $fields['bio']]);
            $app_id = $db->lastInsertId();
            
            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($fields['languages'] as $lang_id) {
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
        die('Ошибка сохранения');
    }
}
?>