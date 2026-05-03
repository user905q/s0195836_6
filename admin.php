<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Файл admin.php загружен<br>";

require_once 'db.php';
echo "db.php подключен<br>";

// Простая проверка авторизации
if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    echo "Требуется авторизация";
    exit;
}

echo "Логин: " . htmlspecialchars($_SERVER['PHP_AUTH_USER']) . "<br>";
echo "Пароль: " . htmlspecialchars($_SERVER['PHP_AUTH_PW']) . "<br>";

// Проверка через БД
if (verifyAdmin($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
    echo "✅ Авторизация успешна!";
} else {
    echo "❌ Неверный логин или пароль";
}
?>
