<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db.php';

$pdo = getDBConnection();

// Проверяем существование таблицы
$stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
if ($stmt->rowCount() == 0) {
    echo "Создаем таблицу admins...<br>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        login VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ Таблица создана<br>";
}

// Удаляем старого админа если есть
$pdo->exec("DELETE FROM admins WHERE login = 'admin'");

// Создаем новый хеш пароля
$password = '123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Новый хеш для пароля '123':<br>";
echo "<strong>" . $hash . "</strong><br><br>";

// Добавляем админа с новым хешем
$stmt = $pdo->prepare("INSERT INTO admins (login, password_hash) VALUES (:login, :hash)");
$stmt->execute([
    ':login' => 'admin',
    ':hash' => $hash
]);

echo "✅ Администратор создан:<br>";
echo "Логин: <strong>admin</strong><br>";
echo "Пароль: <strong>123</strong><br><br>";

// Проверяем
echo "<hr>";
echo "Проверка пароля:<br>";

$stmt = $pdo->prepare("SELECT * FROM admins WHERE login = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin && password_verify('123', $admin['password_hash'])) {
    echo "✅ Пароль ПРАВИЛЬНЫЙ! Можете заходить в admin.php<br>";
    echo "Логин: admin<br>";
    echo "Пароль: 123<br>";
} else {
    echo "❌ Что-то пошло не так<br>";
}
?>
