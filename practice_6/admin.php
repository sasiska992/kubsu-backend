// В функциях получения данных:
function getAllApplications($db) {
    $stmt = $db->query("
        SELECT a.*, u.login 
        FROM application a
        JOIN users u ON a.id = u.application_id
        ORDER BY a.id
    ");
    return $stmt->fetchAll();
}

// В форме редактирования:
$stmt = $db->prepare("
    SELECT a.* 
    FROM application a
    WHERE a.id = ?
");
$stmt->execute([$id]);
$edit_data = $stmt->fetch();

// В SQL для обновления:
$stmt = $db->prepare("
    UPDATE application 
    SET first_name = ?, last_name = ?, patronymic = ?, phone = ?, 
        email = ?, dob = ?, gender = ?, bio = ?
    WHERE id = ?
");
$stmt->execute([$first_name, $last_name, $patronymic, $phone, $email, $dob, $gender, $bio, $id]);