<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// Подключение с вашими данными
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

// Проверка авторизации администратора
// ... (ваш код проверки авторизации)

// Функции для работы с данными
function getAllApplications($db) {
    $stmt = $db->query("
        SELECT a.*, u.login 
        FROM application a
        JOIN users u ON a.id = u.application_id
        ORDER BY a.id
    ");
    return $stmt->fetchAll();
}

function getApplicationLanguages($db, $app_id) {
    $stmt = $db->prepare("
        SELECT l.id, l.name 
        FROM application_languages al
        JOIN languages l ON al.language_id = l.id
        WHERE al.application_id = ?
    ");
    $stmt->execute([$app_id]);
    return $stmt->fetchAll();
}

function getLanguagesStatistics($db) {
    $stmt = $db->query("
        SELECT l.id, l.name, COUNT(al.application_id) as user_count
        FROM languages l
        LEFT JOIN application_languages al ON l.id = al.language_id
        GROUP BY l.id, l.name
        ORDER BY user_count DESC
    ");
    return $stmt->fetchAll();
}

// Обработка действий
// ... (ваш код обработки удаления и редактирования)

// Получение данных для отображения
$applications = getAllApplications($db);
$statistics = getLanguagesStatistics($db);
$all_languages = $db->query("SELECT id, name FROM languages")->fetchAll();

// HTML-шаблон админки
// ... (ваш HTML с добавлением классов Bootstrap)
?>