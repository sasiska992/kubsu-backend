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

if (empty($_POST['fullname'])) {
    $errors[] = "ФИО обязательно.";
} elseif (!preg_match("/^[a-zA-Zа-яА-Я\s]{1,150}$/u", $_POST['fullname'])) {
    $errors[] = "ФИО должно содержать только буквы и пробелы, не более 150 символов.";
}

if (count($errors) > 0) {
    foreach ($errors as $error) {
        echo "<p style='color: red;'>$error</p>";
    }
    exit;
}

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
