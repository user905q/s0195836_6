<?php

function getDBConnection() {
    $db_host = 'localhost';
    $db_name = 'u82327';
    $db_user = 'u82327';
    $db_pass = '2458481';
    
    try {
        $pdo = new PDO(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
            $db_user,
            $db_pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Ошибка подключения к БД: " . $e->getMessage());
    }
}

function getAllApplications($pdo) {
    $stmt = $pdo->query("
        SELECT 
            a.id,
            a.full_name,
            a.phone,
            a.email,
            a.birth_date,
            a.gender,
            a.biography,
            a.contract_agreed,
            a.created_at,
            u.login as user_login,
            GROUP_CONCAT(pl.name ORDER BY pl.name SEPARATOR ', ') as languages
        FROM applications a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN application_languages al ON a.id = al.application_id
        LEFT JOIN programming_languages pl ON al.language_id = pl.id
        GROUP BY a.id
        ORDER BY a.created_at DESC
    ");
    return $stmt->fetchAll();
}

function getApplicationById($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            GROUP_CONCAT(al.language_id) as language_ids
        FROM applications a
        LEFT JOIN application_languages al ON a.id = al.application_id
        WHERE a.id = :id
        GROUP BY a.id
    ");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function deleteApplication($pdo, $id) {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("DELETE FROM application_languages WHERE application_id = :id");
        $stmt->execute([':id' => $id]);
        
       
        $stmt = $pdo->prepare("UPDATE users SET application_id = NULL WHERE application_id = :id");
        $stmt->execute([':id' => $id]);
        
        $stmt = $pdo->prepare("DELETE FROM applications WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

function updateApplicationById($pdo, $id, $data) {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            UPDATE applications 
            SET full_name = :full_name, 
                phone = :phone, 
                email = :email, 
                birth_date = :birth_date, 
                gender = :gender, 
                biography = :biography, 
                contract_agreed = :contract_agreed 
            WHERE id = :id
        ");
        $stmt->execute([
            ':full_name' => $data['full_name'],
            ':phone' => $data['phone'],
            ':email' => $data['email'],
            ':birth_date' => $data['birth_date'],
            ':gender' => $data['gender'],
            ':biography' => $data['biography'],
            ':contract_agreed' => isset($data['contract_agreed']) ? 1 : 0,
            ':id' => $id
        ]);
        
        // Обновляем языки
        $stmt = $pdo->prepare("DELETE FROM application_languages WHERE application_id = :id");
        $stmt->execute([':id' => $id]);
        
        if (!empty($data['languages']) && is_array($data['languages'])) {
            $stmt_lang = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($data['languages'] as $lang_id) {
                $stmt_lang->execute([$id, $lang_id]);
            }
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

function getLanguageStatistics($pdo) {
    $stmt = $pdo->query("
        SELECT 
            pl.id,
            pl.name,
            COUNT(al.application_id) as users_count
        FROM programming_languages pl
        LEFT JOIN application_languages al ON pl.id = al.language_id
        GROUP BY pl.id, pl.name
        ORDER BY users_count DESC, pl.name
    ");
    return $stmt->fetchAll();
}

function getAllLanguages($pdo) {
    $stmt = $pdo->query("SELECT id, name FROM programming_languages ORDER BY name");
    return $stmt->fetchAll();
}


function verifyAdmin($login, $password) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE login = :login");
    $stmt->execute([':login' => $login]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        return true;
    }
    return false;
}
?>