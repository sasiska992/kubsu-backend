<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

$user = 'u68763';
$pass = '7680994';

try {
    $db = new PDO('mysql:host=localhost;dbname=u68763', $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}

function getLangs($db) {
    try {
        $allowed_lang = [];
        $data = $db->query("SELECT id, name FROM languages")->fetchAll();
        foreach ($data as $lang) {
            $allowed_lang[$lang['id']] = $lang['name'];
        }
        return $allowed_lang;
    } catch (PDOException $e) {
        die('Ошибка: ' . $e->getMessage());
    }
}

$allowed_lang = getLangs($db);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = [];
    $errors = [];
    $values = [];

    $fields = ['first_name', 'last_name', 'patronymic', 'phone', 'email', 'dob', 'gender', 'bio', 'languages'];
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : $_COOKIE[$field . '_value'];
    }

    foreach ($fields as $field) {
        setcookie($field . '_error', '', time() - 3600);
    }

    if ($errors['first_name']) {
        $messages['first_name'] = match($_COOKIE['first_name_error']) {
            '1' => 'Имя не указано.',
            '2' => 'Имя не должно превышать 128 символов.',
            '3' => 'Имя должно содержать только буквы.',
            default => 'Некорректное имя.'
        };
    }
    
    if ($errors['last_name']) {
        $messages['last_name'] = match($_COOKIE['last_name_error']) {
            '1' => 'Фамилия не указана.',
            '2' => 'Фамилия не должна превышать 128 символов.',
            '3' => 'Фамилия должна содержать только буквы.',
            default => 'Некорректная фамилия.'
        };
    }
    
    if ($errors['patronymic']) {
        $messages['patronymic'] = match($_COOKIE['patronymic_error']) {
            '1' => 'Отчество не должно превышать 128 символов.',
            '2' => 'Отчество должно содержать только буквы.',
            default => 'Некорректное отчество.'
        };
    }
    
    if ($errors['phone']) {
        $messages['phone'] = match($_COOKIE['phone_error']) {
            '1' => 'Телефон не указан.',
            '2' => 'Телефон должен быть в формате +7XXXXXXXXXX.',
            default => 'Некорректный телефон.'
        };
    }
    
    if ($errors['email']) {
        $messages['email'] = match($_COOKIE['email_error']) {
            '1' => 'Email не указан.',
            '2' => 'Email должен быть в формате example@domain.com.',
            default => 'Некорректный email.'
        };
    }
    
    if ($errors['dob']) {
        $messages['dob'] = 'Некорректная дата рождения.';
    }
    
    if ($errors['gender']) {
        $messages['gender'] = match($_COOKIE['gender_error']) {
            '1' => 'Пол не указан.',
            '2' => 'Недопустимое значение пола.',
            default => 'Некорректный пол.'
        };
    }
    
    if ($errors['bio']) {
        $messages['bio'] = match($_COOKIE['bio_error']) {
            '1' => 'Биография не указана.',
            '2' => 'Биография не должна превышать 512 символов.',
            '3' => 'Биография содержит недопустимые символы.',
            default => 'Некорректная биография.'
        };
    }
    
    if ($errors['languages']) {
        $messages['languages'] = match($_COOKIE['languages_error']) {
            '1' => 'Не выбран язык программирования.',
            '2' => 'Выбран недопустимый язык программирования.',
            default => 'Некорректные языки программирования.'
        };
    }

    if (!empty($_SESSION['login'])) {
        try {
            $stmt = $db->prepare("SELECT a.* FROM application a JOIN users u ON a.id = u.application_id WHERE u.login = ?");
            $stmt->execute([$_SESSION['login']]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);

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
                $selected_langs = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $values['languages'] = implode(',', $selected_langs);
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
    $languages = is_array($_POST['languages'] ?? []) ? $_POST['languages'] : [];

    if (empty($first_name)) {
        setcookie('first_name_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (strlen($first_name) > 128) {
        setcookie('first_name_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ]+$/u', $first_name)) {
        setcookie('first_name_error', '3', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('first_name_value', $first_name, time() + 365 * 24 * 60 * 60);

    if (empty($last_name)) {
        setcookie('last_name_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (strlen($last_name) > 128) {
        setcookie('last_name_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ]+$/u', $last_name)) {
        setcookie('last_name_error', '3', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('last_name_value', $last_name, time() + 365 * 24 * 60 * 60);

    if (!empty($patronymic)) {
        if (strlen($patronymic) > 128) {
            setcookie('patronymic_error', '1', time() + 24 * 60 * 60);
            $errors = TRUE;
        } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ]+$/u', $patronymic)) {
            setcookie('patronymic_error', '2', time() + 24 * 60 * 60);
            $errors = TRUE;
        }
    }
    setcookie('patronymic_value', $patronymic, time() + 365 * 24 * 60 * 60);

    if (empty($phone)) {
        setcookie('phone_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (!preg_match('/^\+7\d{10}$/', $phone)) {
        setcookie('phone_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('phone_value', $phone, time() + 365 * 24 * 60 * 60);

    if (empty($email)) {
        setcookie('email_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('email_value', $email, time() + 365 * 24 * 60 * 60);

    if (empty($dob)) {
        setcookie('dob_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        setcookie('dob_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('dob_value', $dob, time() + 365 * 24 * 60 * 60);

    if (empty($gender)) {
        setcookie('gender_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (!in_array($gender, ["male", "female"])) {
        setcookie('gender_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('gender_value', $gender, time() + 365 * 24 * 60 * 60);

    if (empty($bio)) {
        setcookie('bio_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (strlen($bio) > 512) {
        setcookie('bio_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (preg_match('/[<>{}\[\]]|<script|<\?php/i', $bio)) {
        setcookie('bio_error', '3', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('bio_value', $bio, time() + 365 * 24 * 60 * 60);

    if (empty($languages)) {
        setcookie('languages_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } else {
        $invalid_langs = array_diff($languages, array_keys($allowed_lang));
        if (!empty($invalid_langs)) {
            setcookie('languages_error', '2', time() + 24 * 60 * 60);
            $errors = TRUE;
        }
    }
    setcookie('languages_value', implode(',', $languages), time() + 365 * 24 * 60 * 60);

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    $fields = ['first_name', 'last_name', 'patronymic', 'phone', 'email', 'dob', 'gender', 'bio', 'languages'];
    foreach ($fields as $field) {
        setcookie($field . '_error', '', time() - 3600);
    }

    try {
        if (!empty($_SESSION['login'])) {
            $stmt = $db->prepare("UPDATE application SET first_name = ?, last_name = ?, patronymic = ?, phone = ?, email = ?, dob = ?, gender = ?, bio = ? WHERE id = (SELECT application_id FROM users WHERE login = ?)");
            $stmt->execute([$first_name, $last_name, $patronymic, $phone, $email, $dob, $gender, $bio, $_SESSION['login']]);

            $stmt = $db->prepare("SELECT application_id FROM users WHERE login = ?");
            $stmt->execute([$_SESSION['login']]);
            $application_id = $stmt->fetchColumn();

            $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
            $stmt->execute([$application_id]);

            $stmt_insert = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languages as $language_id) {
                $stmt_insert->execute([$application_id, $language_id]);
            }
        } else {
            $stmt = $db->prepare("INSERT INTO application (first_name, last_name, patronymic, phone, email, dob, gender, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $patronymic, $phone, $email, $dob, $gender, $bio]);
            $application_id = $db->lastInsertId();

            $stmt_insert = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languages as $language_id) {
                $stmt_insert->execute([$application_id, $language_id]);
            }

            $login = uniqid('user_');
            $pass = bin2hex(random_bytes(8));
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO users (login, password_hash, application_id) VALUES (?, ?, ?)");
            $stmt->execute([$login, $pass_hash, $application_id]);

            $_SESSION['generated_login'] = $login;
            $_SESSION['generated_password'] = $pass;
        }

        setcookie('save', '1', time() + 24 * 60 * 60);
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        die('Ошибка сохранения: ' . $e->getMessage());
    }
}