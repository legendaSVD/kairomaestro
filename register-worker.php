<?php
require_once 'config/config.php';
if (isLoggedIn()) {
    redirect('/worker/dashboard.php');
}
$error = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step < 1) $step = 1;
if ($step > 7) $step = 7;
if (!isset($_SESSION['worker_registration'])) {
    $_SESSION['worker_registration'] = ['role' => 'worker'];
}
if (isset($_GET['reset'])) {
    unset($_SESSION['worker_registration']);
    redirect('/register-worker.php?step=1');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentStepData = $_POST;
    if ($step === 1) {
        if (empty($currentStepData['first_name'])) {
            $error = 'Пожалуйста, введите имя';
        } else {
            $_SESSION['worker_registration']['first_name'] = trim($currentStepData['first_name']);
            redirect('/register-worker.php?step=2');
            exit;
        }
    } elseif ($step === 2) {
        if (empty($_SESSION['worker_registration']['first_name'])) {
            redirect('/register-worker.php?step=1');
            exit;
        }
        if (empty($currentStepData['last_name'])) {
            $error = 'Пожалуйста, введите фамилию';
        } else {
            $_SESSION['worker_registration']['last_name'] = trim($currentStepData['last_name']);
            redirect('/register-worker.php?step=3');
            exit;
        }
    } elseif ($step === 3) {
        if (empty($_SESSION['worker_registration']['first_name']) || 
            empty($_SESSION['worker_registration']['last_name'])) {
            redirect('/register-worker.php?step=1');
            exit;
        }
        $_SESSION['worker_registration']['middle_name'] = trim($currentStepData['middle_name'] ?? '');
        redirect('/register-worker.php?step=4');
        exit;
    } elseif ($step === 4) {
        if (empty($_SESSION['worker_registration']['first_name']) || 
            empty($_SESSION['worker_registration']['last_name'])) {
            redirect('/register-worker.php?step=1');
            exit;
        }
        if (empty($currentStepData['specialty'])) {
            $error = 'Пожалуйста, выберите специальность';
        } else {
            $_SESSION['worker_registration']['specialty'] = $currentStepData['specialty'];
            redirect('/register-worker.php?step=5');
            exit;
        }
    } elseif ($step === 5) {
        if (empty($_SESSION['worker_registration']['specialty'])) {
            redirect('/register-worker.php?step=4');
            exit;
        }
        $_SESSION['worker_registration']['has_car'] = $currentStepData['has_car'] ?? 0;
        $_SESSION['worker_registration']['car_brand'] = trim($currentStepData['car_brand'] ?? '');
        $_SESSION['worker_registration']['car_number'] = trim($currentStepData['car_number'] ?? '');
        redirect('/register-worker.php?step=6');
        exit;
    } elseif ($step === 6) {
        if (empty($_SESSION['worker_registration']['specialty'])) {
            redirect('/register-worker.php?step=4');
            exit;
        }
        $_SESSION['worker_registration']['hourly_rate'] = $currentStepData['hourly_rate'] ?? 0;
        $_SESSION['worker_registration']['additional_info'] = trim($currentStepData['additional_info'] ?? '');
        redirect('/register-worker.php?step=7');
        exit;
    } elseif ($step === 7 && isset($currentStepData['final_submit'])) {
        if (empty($_SESSION['worker_registration']['first_name']) || 
            empty($_SESSION['worker_registration']['last_name']) ||
            empty($_SESSION['worker_registration']['specialty'])) {
            redirect('/register-worker.php?step=1');
            exit;
        }
        $_SESSION['worker_registration']['phone'] = trim($currentStepData['phone'] ?? '');
        $_SESSION['worker_registration']['password'] = $currentStepData['password'] ?? '';
        $_SESSION['worker_registration']['password_confirm'] = $currentStepData['password_confirm'] ?? '';
        $data = $_SESSION['worker_registration'];
        if (empty($data['phone'])) {
            $error = 'Введите номер телефона';
        } elseif (empty($data['password'])) {
            $error = 'Введите пароль';
        } elseif (strlen($data['password']) < 6) {
            $error = 'Пароль должен содержать минимум 6 символов';
        } elseif ($data['password'] !== $data['password_confirm']) {
            $error = 'Пароли не совпадают';
        } else {
            $userObj = new User();
            $result = $userObj->register($data);
            if ($result['success']) {
                $userObj->login($data['phone'], $data['password']);
                unset($_SESSION['worker_registration']);
                redirect('/worker/dashboard.php');
            } else {
                $error = $result['error'];
            }
        }
    }
}
$regData = $_SESSION['worker_registration'] ?? [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация работника - KairoMaestro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-logo">
                <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Переключить тему" style="position: absolute; top: 1rem; right: 1rem;">
                    🌙
                </button>
                <h1>KairoMaestro</h1>
            </div>
            <div class="auth-card">
                <h2>Регистрация работника</h2>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo escape($error); ?></div>
                <?php endif; ?>
                <!-- Прогресс-бар -->
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo ($step / 7) * 100; ?>%"></div>
                </div>
                <p class="step-indicator">Шаг <?php echo $step; ?> из 7</p>
                <form method="POST" action="/register-worker.php?step=<?php echo $step; ?>">
                    <input type="hidden" name="role" value="worker">
                    <?php if ($step === 1): ?>
                        <div class="form-group">
                            <label for="first_name">Имя *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" 
                                   value="<?php echo escape($regData['first_name'] ?? ''); ?>" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Далее</button>
                    <?php elseif ($step === 2): ?>
                        <div class="form-group">
                            <label for="last_name">Фамилия *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" 
                                   value="<?php echo escape($regData['last_name'] ?? ''); ?>" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Далее</button>
                        <a href="/register-worker.php?step=1" class="btn btn-secondary btn-block" style="margin-top: 1rem;">Назад</a>
                    <?php elseif ($step === 3): ?>
                        <div class="form-group">
                            <label for="middle_name">Отчество</label>
                            <input type="text" id="middle_name" name="middle_name" class="form-control" 
                                   value="<?php echo escape($regData['middle_name'] ?? ''); ?>" autofocus>
                            <small>Необязательное поле, нажмите "Далее" чтобы пропустить</small>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Далее</button>
                        <a href="/register-worker.php?step=2" class="btn btn-secondary btn-block" style="margin-top: 1rem;">Назад</a>
                    <?php elseif ($step === 4): ?>
                        <div class="form-group">
                            <label for="specialty">Специальность *</label>
                            <select id="specialty" name="specialty" class="form-control" required>
                                <option value="">Выберите специальность</option>
                                <option value="Флорист" <?php echo ($regData['specialty'] ?? '') === 'Флорист' ? 'selected' : ''; ?>>Флорист</option>
                                <option value="Хелпер" <?php echo ($regData['specialty'] ?? '') === 'Хелпер' ? 'selected' : ''; ?>>Хелпер</option>
                                <option value="Монтажник" <?php echo ($regData['specialty'] ?? '') === 'Монтажник' ? 'selected' : ''; ?>>Монтажник</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Далее</button>
                        <a href="/register-worker.php?step=3" class="btn btn-secondary btn-block" style="margin-top: 1rem;">Назад</a>
                    <?php elseif ($step === 5): ?>
                        <div class="form-group">
                            <label>Есть ли автомобиль?</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="has_car" value="1" 
                                           <?php echo ($regData['has_car'] ?? '0') == '1' ? 'checked' : ''; ?> 
                                           onchange="toggleCarDetails()">
                                    Да
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="has_car" value="0" 
                                           <?php echo ($regData['has_car'] ?? '0') == '0' ? 'checked' : ''; ?> 
                                           onchange="toggleCarDetails()">
                                    Нет
                                </label>
                            </div>
                        </div>
                        <div class="car-details" id="carDetails" style="display: <?php echo ($regData['has_car'] ?? '0') == '1' ? 'block' : 'none'; ?>;">
                            <div class="form-group">
                                <label for="car_brand">Марка автомобиля</label>
                                <input type="text" id="car_brand" name="car_brand" class="form-control" 
                                       value="<?php echo escape($regData['car_brand'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="car_number">Госномер</label>
                                <input type="text" id="car_number" name="car_number" class="form-control" 
                                       value="<?php echo escape($regData['car_number'] ?? ''); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Далее</button>
                        <a href="/register-worker.php?step=4" class="btn btn-secondary btn-block" style="margin-top: 1rem;">Назад</a>
                    <?php elseif ($step === 6): ?>
                        <div class="form-group">
                            <label for="hourly_rate">Ставка за час (₽)</label>
                            <input type="number" id="hourly_rate" name="hourly_rate" class="form-control" 
                                   value="<?php echo escape($regData['hourly_rate'] ?? ''); ?>" min="0" step="50">
                            <small>Необязательное поле</small>
                        </div>
                        <div class="form-group">
                            <label for="additional_info">Дополнительная информация</label>
                            <textarea id="additional_info" name="additional_info" class="form-control" rows="4"><?php echo escape($regData['additional_info'] ?? ''); ?></textarea>
                            <small>Опыт работы, пожелания и другая информация</small>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Далее</button>
                        <a href="/register-worker.php?step=5" class="btn btn-secondary btn-block" style="margin-top: 1rem;">Назад</a>
                    <?php elseif ($step === 7): ?>
                        <div class="form-group">
                            <label for="phone">Номер телефона *</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   placeholder="+7 (___) ___-__-__" 
                                   value="<?php echo escape($regData['phone'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Пароль *</label>
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Минимум 6 символов" minlength="6" required>
                        </div>
                        <div class="form-group">
                            <label for="password_confirm">Подтвердите пароль *</label>
                            <input type="password" id="password_confirm" name="password_confirm" class="form-control" 
                                   placeholder="Повторите пароль" minlength="6" required>
                        </div>
                        <button type="submit" name="final_submit" class="btn btn-primary btn-block">Завершить регистрацию</button>
                        <a href="/register-worker.php?step=6" class="btn btn-secondary btn-block" style="margin-top: 1rem;">Назад</a>
                    <?php endif; ?>
                </form>
                <div class="auth-footer">
                    <p>Уже есть аккаунт? <a href="/login.php">Войти</a></p>
                    <p><a href="/register-organizer.php">Я организатор</a></p>
                </div>
            </div>
        </div>
    </div>
    <script src="/assets/js/theme-toggle.js"></script>
    <script src="/assets/js/phone-mask.js"></script>
    <script>
        function toggleCarDetails() {
            const hasCar = document.querySelector('input[name="has_car"]:checked');
            const carDetails = document.getElementById('carDetails');
            if (hasCar && hasCar.value === '1') {
                carDetails.style.display = 'block';
            } else {
                carDetails.style.display = 'none';
            }
        }
    </script>
</body>
</html>
