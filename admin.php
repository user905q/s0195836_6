<?php

require_once 'db.php';

function checkAdminAuth() {
    if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
        return false;
    }
    
    return verifyAdmin($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
}

if (!checkAdminAuth()) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    print('<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 Требуется авторизация</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #f0f2f5;
        }
        .error-container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 500px;
        }
        h1 {
            color: #dc3545;
            font-size: 48px;
            margin: 0 0 20px 0;
        }
        p {
            color: #666;
            font-size: 18px;
            margin: 0;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="icon">🔒</div>
        <h1>401</h1>
        <p>Требуется авторизация</p>
        <p style="font-size: 14px; margin-top: 15px; color: #999;">Доступ запрещен. Введите логин и пароль администратора.</p>
    </div>
</body>
</html>');
    exit();
}

$pdo = getDBConnection();

$message = '';
$message_type = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if (deleteApplication($pdo, $id)) {
        $message = "Заявка №{$id} успешно удалена.";
        $message_type = 'success';
    } else {
        $message = "Ошибка при удалении заявки №{$id}.";
        $message_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $data = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'birth_date' => $_POST['birth_date'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'languages' => $_POST['languages'] ?? [],
        'biography' => trim($_POST['biography'] ?? ''),
        'contract_agreed' => isset($_POST['contract_agreed'])
    ];
    
    // Простая валидация
    $errors = [];
    if (empty($data['full_name'])) $errors[] = 'ФИО обязательно';
    if (empty($data['phone'])) $errors[] = 'Телефон обязателен';
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный email';
    if (empty($data['birth_date'])) $errors[] = 'Дата рождения обязательна';
    
    if (empty($errors)) {
        if (updateApplicationById($pdo, $id, $data)) {
            $message = "Заявка №{$id} успешно обновлена.";
            $message_type = 'success';
        } else {
            $message = "Ошибка при обновлении заявки №{$id}.";
            $message_type = 'error';
        }
    } else {
        $message = "Ошибки валидации: " . implode(', ', $errors);
        $message_type = 'error';
    }
}

$applications = getAllApplications($pdo);
$statistics = getLanguageStatistics($pdo);
$all_languages = getAllLanguages($pdo);

$edit_application = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_application = getApplicationById($pdo, (int)$_GET['id']);
}

function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1, h2 {
            color: #1a1a1a;
            margin-bottom: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: white;
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info span {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .stat-card .language-name {
            color: #667eea;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .stat-card .count {
            font-size: 36px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge.male {
            background: #cce5ff;
            color: #004085;
        }
        
        .badge.female {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge.agreed {
            background: #d4edda;
            color: #155724;
        }
        
        .badge.not_agreed {
            background: #fff3cd;
            color: #856404;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-edit {
            background: #007bff;
            color: white;
        }
        
        .btn-edit:hover {
            background: #0056b3;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            margin-left: 10px;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .btn-save {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            margin-top: 15px;
        }
        
        .btn-save:hover {
            background: #218838;
        }
        
        .edit-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .edit-form h2 {
            margin-bottom: 25px;
            color: #333;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="date"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .language-checkboxes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 8px;
        }
        
        .language-checkboxes label {
            font-weight: normal;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
        }
        
        .radio-group label {
            font-weight: normal;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        
        .total-count {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Административная панель</h1>
            <div class="user-info">
                <span>👤 <?php echo h($_SERVER['PHP_AUTH_USER']); ?></span>
                <a href="admin.php?logout=1" class="logout-btn">Выйти</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo h($message); ?>
            </div>
        <?php endif; ?>
        
        <h2>📊 Статистика по языкам программирования</h2>
        <div class="stats-grid">
            <?php foreach ($statistics as $stat): ?>
                <div class="stat-card">
                    <div class="language-name"><?php echo h($stat['name']); ?></div>
                    <div class="count"><?php echo $stat['users_count']; ?></div>
                    <div class="label">пользователей</div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="total-count">
            📋 Всего заявок: <?php echo count($applications); ?>
        </div>
        
        <?php if ($edit_application): ?>
            <div class="edit-form">
                <h2>✏️ Редактирование заявки №<?php echo $edit_application['id']; ?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo $edit_application['id']; ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>ФИО:</label>
                            <input type="text" name="full_name" value="<?php echo h($edit_application['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Телефон:</label>
                            <input type="text" name="phone" value="<?php echo h($edit_application['phone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email" value="<?php echo h($edit_application['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Дата рождения:</label>
                            <input type="date" name="birth_date" value="<?php echo $edit_application['birth_date']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Пол:</label>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="gender" value="male" <?php echo $edit_application['gender'] === 'male' ? 'checked' : ''; ?> required> 
                                    Мужской
                                </label>
                                <label>
                                    <input type="radio" name="gender" value="female" <?php echo $edit_application['gender'] === 'female' ? 'checked' : ''; ?>> 
                                    Женский
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Языки программирования:</label>
                            <div class="language-checkboxes">
                                <?php 
                                $selected_languages = explode(',', $edit_application['language_ids'] ?? '');
                                foreach ($all_languages as $lang): 
                                ?>
                                    <label>
                                        <input type="checkbox" name="languages[]" value="<?php echo $lang['id']; ?>"
                                               <?php echo in_array($lang['id'], $selected_languages) ? 'checked' : ''; ?>>
                                        <?php echo h($lang['name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Биография:</label>
                            <textarea name="biography" rows="5" required><?php echo h($edit_application['biography']); ?></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>
                                <input type="checkbox" name="contract_agreed" value="1" <?php echo $edit_application['contract_agreed'] ? 'checked' : ''; ?>>
                                С контрактом ознакомлен
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-save">💾 Сохранить изменения</button>
                    <a href="admin.php" class="btn btn-cancel">Отмена</a>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Таблица заявок -->
        <div class="table-container">
            <h2>📋 Список заявок</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Дата рождения</th>
                        <th>Пол</th>
                        <th>Языки</th>
                        <th>Контракт</th>
                        <th>Логин</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr>
                            <td colspan="11" class="no-data">
                                📭 Нет заявок для отображения
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><strong>#<?php echo $app['id']; ?></strong></td>
                                <td><?php echo h($app['full_name']); ?></td>
                                <td><?php echo h($app['phone']); ?></td>
                                <td><?php echo h($app['email']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($app['birth_date'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $app['gender']; ?>">
                                        <?php echo $app['gender'] === 'male' ? '👨 Мужской' : '👩 Женский'; ?>
                                    </span>
                                </td>
                                <td style="max-width: 250px;">
                                    <?php echo h($app['languages'] ?? 'Не выбраны'); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $app['contract_agreed'] ? 'agreed' : 'not_agreed'; ?>">
                                        <?php echo $app['contract_agreed'] ? '✅ Да' : '❌ Нет'; ?>
                                    </span>
                                </td>
                                <td><?php echo h($app['user_login'] ?? '-'); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($app['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="admin.php?action=edit&id=<?php echo $app['id']; ?>" 
                                           class="btn btn-edit" title="Редактировать">✏️</a>
                                        <a href="admin.php?action=delete&id=<?php echo $app['id']; ?>" 
                                           class="btn btn-delete" 
                                           title="Удалить"
                                           onclick="return confirm('Вы уверены, что хотите удалить заявку №<?php echo $app['id']; ?>?\n\nФИО: <?php echo h($app['full_name']); ?>\n\nЭто действие нельзя отменить!');">
                                            🗑️
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>