<?php
$user = 'u68763';
$pass = '7680994';

try {
    $db = new PDO('mysql:host=localhost;dbname=u68763', $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Функция для валидации данных
function validateData(&$errors) {
    $fields = [
        'fullname' => [
            'pattern' => '/^[a-zA-Zа-яА-ЯёЁ\s-]{1,150}$/u',
            'message' => 'ФИО должно содержать только буквы, пробелы и дефисы, не более 150 символов'
        ],
        'phone' => [
            'pattern' => '/^\+?\d{10,15}$/',
            'message' => 'Телефон должен содержать от 10 до 15 цифр, может начинаться с +'
        ],
        'email' => [
            'pattern' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'message' => 'Введите корректный email'
        ],
        'dob' => [
            'pattern' => '/^\d{4}-\d{2}-\d{2}$/',
            'message' => 'Дата должна быть в формате ГГГГ-ММ-ДД'
        ],
        'bio' => [
            'pattern' => '/^[\s\S]{1,500}$/',
            'message' => 'Биография не должна превышать 500 символов'
        ]
    ];

    foreach ($fields as $field => $rules) {
        if (empty($_POST[$field])) {
            $errors[$field] = "Поле обязательно для заполнения";
        } elseif (!preg_match($rules['pattern'], $_POST[$field])) {
            $errors[$field] = $rules['message'];
        }
    }

    if (empty($_POST['gender'])) {
        $errors['gender'] = "Укажите пол";
    }

    if (empty($_POST['languages'])) {
        $errors['languages'] = "Выберите хотя бы один язык";
    }

    if (empty($_POST['contract'])) {
        $errors['contract'] = "Необходимо согласие с контрактом";
    }
}

$errors = [];
validateData($errors);

if (count($errors) > 0) {
    // Сохраняем ошибки и введенные данные в cookies
    setcookie('form_errors', json_encode($errors), 0, '/');
    setcookie('form_data', json_encode($_POST), 0, '/');
    header('Location: index.php');
    exit;
}

// Если ошибок нет, сохраняем данные в БД
$fullname = trim($_POST['fullname']);
$nameParts = explode(' ', $fullname);
$last_name = $nameParts[0] ?? '';
$first_name = $nameParts[1] ?? '';
$patronymic = $nameParts[2] ?? null;

try {
    $stmt = $db->prepare("INSERT INTO application (first_name, last_name, patronymic, phone, email, dob, gender, bio) 
                          VALUES (:first_name, :last_name, :patronymic, :phone, :email, :dob, :gender, :bio)");
    $stmt->execute([
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':patronymic' => $patronymic,
        ':phone' => $_POST['phone'],
        ':email' => $_POST['email'],
        ':dob' => $_POST['dob'],
        ':gender' => $_POST['gender'],
        ':bio' => $_POST['bio']
    ]);

    $applicationId = $db->lastInsertId();

    foreach ($_POST['languages'] as $language) {
        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) 
                              VALUES (:application_id, (SELECT id FROM languages WHERE name = :language))");
        $stmt->execute([
            ':application_id' => $applicationId,
            ':language' => $language
        ]);
    }

    // Сохраняем данные в cookies на год
    setcookie('form_data', json_encode($_POST), time() + 60*60*24*365, '/');
    // Удаляем ошибки, если они были
    setcookie('form_errors', '', time() - 3600, '/');
    
    header('Location: index.php?success=1');
    exit;

} catch (PDOException $e) {
    die("Ошибка при сохранении данных: " . $e->getMessage());
}
?>