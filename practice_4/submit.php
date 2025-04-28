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

$errors = [];

// Проверка ФИО
if (empty($_POST['fullname'])) {
    $errors[] = "ФИО обязательно.";
} elseif (!preg_match("/^[a-zA-Zа-яА-ЯёЁ\s-]{1,150}$/u", $_POST['fullname'])) {
    $errors[] = "ФИО должно содержать только буквы, пробелы и дефисы, не более 150 символов.";
}

// Проверка телефона (пример: +7(123)456-78-90 или 81234567890)
if (empty($_POST['phone'])) {
    $errors[] = "Телефон обязателен.";
} elseif (!preg_match("/^(\+?\d{1,3})?[\(\)\-\d\s]{7,20}$/", $_POST['phone'])) {
    $errors[] = "Некорректный формат телефона.";
}

// Проверка email
if (empty($_POST['email'])) {
    $errors[] = "Email обязателен.";
} elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Некорректный формат email.";
}

// Проверка даты рождения (формат YYYY-MM-DD и возраст >= 18 лет)
if (empty($_POST['dob'])) {
    $errors[] = "Дата рождения обязательна.";
} elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $_POST['dob'])) {
    $errors[] = "Некорректный формат даты рождения (требуется YYYY-MM-DD).";
} else {
    $dob = new DateTime($_POST['dob']);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
    if ($age < 18) {
        $errors[] = "Возраст должен быть 18 лет и более.";
    }
}

// Проверка пола
if (empty($_POST['gender']) || !in_array($_POST['gender'], ['male', 'female', 'other'])) {
    $errors[] = "Укажите корректный пол.";
}

// Проверка языков программирования
if (empty($_POST['languages']) || !is_array($_POST['languages'])) {
    $errors[] = "Выберите хотя бы один язык программирования.";
} else {
    foreach ($_POST['languages'] as $language) {
        if (!preg_match("/^[a-zA-Zа-яА-ЯёЁ\s\+#]{1,50}$/u", $language)) {
            $errors[] = "Некорректное название языка программирования.";
            break;
        }
    }
}

// Проверка биографии (не обязательно, но если есть - проверяем)
if (!empty($_POST['bio']) && !preg_match("/^[a-zA-Zа-яА-ЯёЁ0-9\s.,!?\-]{0,500}$/u", $_POST['bio'])) {
    $errors[] = "Биография содержит недопустимые символы или слишком длинная (макс. 500 символов).";
}

if (count($errors) > 0) {
    foreach ($errors as $error) {
        echo "<p style='color: red;'>$error</p>";
    }
    exit;
}

// Обработка ФИО
$fullname = trim($_POST['fullname']);
$nameParts = preg_split('/\s+/', $fullname);
$last_name = $nameParts[0] ?? '';
$first_name = $nameParts[1] ?? '';
$patronymic = $nameParts[2] ?? null;

// Очистка телефона от лишних символов
$phone = preg_replace('/[^0-9+]/', '', $_POST['phone']);

try {
    $stmt = $db->prepare("INSERT INTO application (first_name, last_name, patronymic, phone, email, dob, gender, bio) 
                          VALUES (:first_name, :last_name, :patronymic, :phone, :email, :dob, :gender, :bio)");
    $stmt->execute([
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':patronymic' => $patronymic,
        ':phone' => $phone,
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

    echo "<p style='color: green;'>Данные успешно сохранены!</p>";

} catch (PDOException $e) {
    die("Ошибка при сохранении данных: " . $e->getMessage());
}

try {
    $stmt = $db->query("SELECT a.id, a.first_name, a.last_name, a.patronymic, a.email, GROUP_CONCAT(l.name SEPARATOR ', ') AS languages 
                        FROM application a 
                        LEFT JOIN application_languages al ON a.id = al.application_id 
                        LEFT JOIN languages l ON al.language_id = l.id 
                        GROUP BY a.id");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($applications) > 0) {
        echo "<h2>Список заявок</h2>";
        echo "<table border='1' cellpadding='10' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Фамилия</th><th>Имя</th><th>Отчество</th><th>Email</th><th>Языки</th></tr>";
        foreach ($applications as $app) {
            echo "<tr>";
            echo "<td>{$app['id']}</td>";
            echo "<td>{$app['last_name']}</td>";
            echo "<td>{$app['first_name']}</td>";
            echo "<td>{$app['patronymic']}</td>";
            echo "<td>{$app['email']}</td>";
            echo "<td>{$app['languages']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Заявок нет.</p>";
    }
} catch (PDOException $e) {
    die("Ошибка получения данных: " . $e->getMessage());
}
?>