<?php
require_once '../config/config.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'worker') {
    redirect('/login.php');
}
$userObj = new User();
$userId = $_SESSION['user_id'];
$profile = $userObj->getWorkerProfile($userId);
if (!$profile) {
    redirect('/worker/dashboard.php');
}
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    if (!empty($_FILES['avatar']['name'])) {
        $result = $userObj->uploadAvatar($_FILES['avatar']);
        if ($result['success']) {
            $updateResult = $userObj->updateProfile($userId, ['avatar' => $result['filename']]);
            if ($updateResult['success']) {
                $success = 'Фото профиля успешно загружено';
                $profile = $userObj->getWorkerProfile($userId); 
            } else {
                $error = 'Ошибка при сохранении фото: ' . $updateResult['error'];
            }
        } else {
            $error = $result['error'];
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $data = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'middle_name' => $_POST['middle_name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'specialty' => $_POST['specialty'] ?? '',
        'has_car' => isset($_POST['has_car']) ? 1 : 0,
        'car_brand' => $_POST['car_brand'] ?? '',
        'car_number' => $_POST['car_number'] ?? '',
        'hourly_rate' => $_POST['hourly_rate'] ?? 0,
        'additional_info' => $_POST['additional_info'] ?? ''
    ];
    if (empty($data['first_name']) || empty($data['last_name'])) {
        $error = 'Имя и фамилия обязательны';
    } else {
        $result = $userObj->updateProfile($userId, $data);
        if ($result['success']) {
            $success = 'Профиль успешно обновлен';
            $profile = $userObj->getWorkerProfile($userId); 
            $_SESSION['user_name'] = $data['first_name'] . ' ' . $data['last_name'];
        } else {
            $error = $result['error'];
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Заполните все поля для смены пароля';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Новые пароли не совпадают';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } else {
        $user = $userObj->getUserById($userId);
        if (password_verify($currentPassword, $user['password'])) {
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$passwordHash, $userId])) {
                $success = 'Пароль успешно изменен';
            } else {
                $error = 'Ошибка при изменении пароля';
            }
        } else {
            $error = 'Неверный текущий пароль';
        }
    }
}
$avatarUrl = '';
if (!empty($profile['avatar'])) {
    $avatarPath = UPLOAD_DIR . 'avatars/' . $profile['avatar'];
    if (file_exists($avatarPath)) {
        $avatarUrl = UPLOAD_URL . 'avatars/' . $profile['avatar'];
    }
}
if (empty($avatarUrl)) {
    $initial = mb_strtoupper(mb_substr($profile['first_name'] ?? 'U', 0, 1));
    $avatarUrl = 'data:image/svg+xml;base64,' . base64_encode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150"><rect width="150" height="150" fill="#10b981"/><text x="50%" y="50%" font-size="60" fill="white" text-anchor="middle" dominant-baseline="central" font-family="Arial, sans-serif" font-weight="bold">' . htmlspecialchars($initial) . '</text></svg>'
    );
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - KairoMaestro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .profile-page {
            padding: 2rem 0;
        }
        .profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: var(--white);
            border-radius: 1rem;
            box-shadow: var(--shadow);
        }
        @media (min-width: 768px) {
            .profile-header {
                flex-direction: row;
                align-items: flex-start;
            }
        }
        .avatar-container {
            position: relative;
            margin-bottom: 1rem;
        }
        @media (min-width: 768px) {
            .avatar-container {
                margin-right: 2rem;
                margin-bottom: 0;
            }
        }
        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            box-shadow: var(--shadow-lg);
        }
        .avatar-upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary-color);
            color: var(--white);
            border: 3px solid var(--white);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: all 0.2s;
        }
        .avatar-upload-btn:hover {
            background: var(--secondary-color);
            transform: scale(1.1);
        }
        .profile-info h1 {
            margin-bottom: 0.5rem;
        }
        .profile-info p {
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }
        .profile-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        .profile-section h2 {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        @media (min-width: 768px) {
            .form-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .radio-group {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .radio-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        .car-details {
            display: none;
        }
        .car-details.show {
            display: block;
        }
    </style>
</head>
<body>
    <?php include '../includes/worker-header.php'; ?>
    <div class="profile-page">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo escape($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo escape($success); ?></div>
            <?php endif; ?>
            <!-- Заголовок профиля с фото -->
            <div class="profile-header">
                <div class="avatar-container">
                    <img src="<?php echo escape($avatarUrl); ?>" alt="Аватар" class="avatar-preview" id="avatarPreview">
                    <form method="POST" enctype="multipart/form-data" style="display: none;" id="avatarForm">
                        <input type="file" name="avatar" id="avatarFileInput" accept="image/jpeg,image/jpg,image/png,image/webp" onchange="uploadAvatar(this)">
                        <input type="hidden" name="upload_avatar" value="1">
                    </form>
                    <label for="avatarFileInput" class="avatar-upload-btn" title="Загрузить фото">
                        📷
                    </label>
                </div>
                <div class="profile-info">
                    <h1><?php echo escape($profile['first_name'] . ' ' . $profile['last_name']); ?></h1>
                    <p><strong>Телефон:</strong> <?php echo escape($profile['phone']); ?></p>
                    <p><strong>Специальность:</strong> <?php echo escape($profile['specialty'] ?? 'Не указана'); ?></p>
                    <?php if (!empty($profile['hourly_rate'])): ?>
                        <p><strong>Ставка за час:</strong> <?php echo number_format($profile['hourly_rate'], 0, ',', ' '); ?> ₽</p>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Основная информация -->
            <div class="profile-section">
                <h2>Основная информация</h2>
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">Имя *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" 
                                   value="<?php echo escape($profile['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Фамилия *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" 
                                   value="<?php echo escape($profile['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="middle_name">Отчество</label>
                        <input type="text" id="middle_name" name="middle_name" class="form-control" 
                               value="<?php echo escape($profile['middle_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Номер телефона</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo escape($profile['phone'] ?? ''); ?>" disabled>
                        <small>Для изменения номера телефона обратитесь в поддержку</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </form>
            </div>
            <!-- Профессиональная информация -->
            <div class="profile-section">
                <h2>Профессиональная информация</h2>
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group">
                        <label for="specialty">Специальность *</label>
                        <select id="specialty" name="specialty" class="form-control" required>
                            <option value="">Выберите специальность</option>
                            <option value="Флорист" <?php echo ($profile['specialty'] ?? '') === 'Флорист' ? 'selected' : ''; ?>>Флорист</option>
                            <option value="Хелпер" <?php echo ($profile['specialty'] ?? '') === 'Хелпер' ? 'selected' : ''; ?>>Хелпер</option>
                            <option value="Монтажник" <?php echo ($profile['specialty'] ?? '') === 'Монтажник' ? 'selected' : ''; ?>>Монтажник</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="hourly_rate">Ставка за час (₽)</label>
                        <input type="number" id="hourly_rate" name="hourly_rate" class="form-control" 
                               value="<?php echo escape($profile['hourly_rate'] ?? 0); ?>" min="0" step="50">
                    </div>
                    <div class="form-group">
                        <label>Есть ли автомобиль?</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="has_car" value="1" 
                                       <?php echo ($profile['has_car'] ?? 0) == 1 ? 'checked' : ''; ?> 
                                       onchange="toggleCarDetails()">
                                Да
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="has_car" value="0" 
                                       <?php echo ($profile['has_car'] ?? 0) == 0 ? 'checked' : ''; ?> 
                                       onchange="toggleCarDetails()">
                                Нет
                            </label>
                        </div>
                    </div>
                    <div class="form-row car-details <?php echo ($profile['has_car'] ?? 0) == 1 ? 'show' : ''; ?>" id="carDetails">
                        <div class="form-group">
                            <label for="car_brand">Марка автомобиля</label>
                            <input type="text" id="car_brand" name="car_brand" class="form-control" 
                                   value="<?php echo escape($profile['car_brand'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="car_number">Госномер</label>
                            <input type="text" id="car_number" name="car_number" class="form-control" 
                                   value="<?php echo escape($profile['car_number'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="additional_info">Дополнительная информация</label>
                        <textarea id="additional_info" name="additional_info" class="form-control" rows="4"><?php echo escape($profile['additional_info'] ?? ''); ?></textarea>
                        <small>Опыт работы, пожелания и другая информация</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </form>
            </div>
            <!-- Смена пароля -->
            <div class="profile-section">
                <h2>Смена пароля</h2>
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    <div class="form-group">
                        <label for="current_password">Текущий пароль *</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">Новый пароль *</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" 
                                   minlength="6" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Подтвердите новый пароль *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   minlength="6" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Изменить пароль</button>
                </form>
            </div>
        </div>
    </div>
    <?php include '../includes/worker-footer.php'; ?>
    <script>
        function toggleCarDetails() {
            const hasCar = document.querySelector('input[name="has_car"]:checked').value === '1';
            const carDetails = document.getElementById('carDetails');
            if (hasCar) {
                carDetails.classList.add('show');
            } else {
                carDetails.classList.remove('show');
            }
        }
        function uploadAvatar(input) {
            if (input.files && input.files[0]) {
                if (input.files[0].size > 5 * 1024 * 1024) {
                    alert('Файл слишком большой. Максимальный размер: 5MB');
                    input.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
                setTimeout(function() {
                    document.getElementById('avatarForm').submit();
                }, 100);
            }
        }
    </script>
</body>
</html>