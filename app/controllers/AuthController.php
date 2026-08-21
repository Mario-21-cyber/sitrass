<?php

class AuthController extends Controller {

    // Ipapakita ang login form
    public function login() {
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['role'] === 'admin') {
            header('Location: /sitrass/public/admin/dashboard');
        } elseif ($_SESSION['role'] === 'driver') {
            header('Location: /sitrass/public/driver/dashboard');
        } else {
            header('Location: /sitrass/public/auth/loggedin');
        }
        exit;
    }

    $error = $_SESSION['login_error'] ?? null;
    unset($_SESSION['login_error']);

    View::render('login', [
        'pageTitle' => 'Login - SITRASS',
        'error' => $error,
    ]);
}

    // Pinoproseso ang submitted na login form
    public function authenticate() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['login_error'] = 'Invalid o expired na session. Subukan ulit.';
        header('Location: /sitrass/public/auth/login');
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $_SESSION['login_error'] = 'Kailangan ang email at password.';
        header('Location: /sitrass/public/auth/login');
        exit;
    }

    $userModel = new User();
    $user = $userModel->getByEmail($email);

    // Hindi natin sasabihin kung "walang account" o "maling password" - pareho lang
    // ang mensahe para hindi malaman ng attacker kung aling email meron ngang account.
    if (!$user) {
        $_SESSION['login_error'] = 'Maling email o password.';
        header('Location: /sitrass/public/auth/login');
        exit;
    }

    if ($userModel->isLocked($user)) {
        $_SESSION['login_error'] = 'Na-lock muna ang account na ito dahil sa maraming maling attempt. Subukan ulit pagkalipas ng ilang minuto.';
        header('Location: /sitrass/public/auth/login');
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        $userModel->recordFailedAttempt($user['user_id']);
        $_SESSION['login_error'] = 'Maling email o password.';
        header('Location: /sitrass/public/auth/login');
        exit;
    }

    if ($user['status'] !== 'active') {
        $_SESSION['login_error'] = 'Hindi active ang account na ito.';
        header('Location: /sitrass/public/auth/login');
        exit;
    }

    // Successful login
    $userModel->resetFailedAttempts($user['user_id']);

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];

    if ($user['role'] === 'admin') {
        header('Location: /sitrass/public/admin/dashboard');
    } elseif ($user['role'] === 'driver') {
        header('Location: /sitrass/public/driver/dashboard');
    } else {
        header('Location: /sitrass/public/auth/loggedin');
    }
    exit;
}

    // Logout
    public function logout() {
        session_unset();
        session_destroy();
        header('Location: /sitrass/public/auth/login');
        exit;
    }
    public function register() {
    if (isset($_SESSION['user_id'])) {
        header('Location: /sitrass/public/admin/dashboard');
        exit;
    }

    $errors = $_SESSION['register_errors'] ?? [];
    $old = $_SESSION['register_old'] ?? [];
    unset($_SESSION['register_errors'], $_SESSION['register_old']);

    View::render('register', [
        'pageTitle' => 'Register - SITRASS',
        'errors' => $errors,
        'old' => $old,
    ]);
}

public function store() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['register_errors'] = ['Invalid o expired na session. Subukan ulit.'];
        header('Location: /sitrass/public/auth/register');
        exit;
    }

    $userModel = new User();

    $validator = new Validator($_POST);
    $validator->required('first_name', 'First name')
        ->required('last_name', 'Last name')
        ->required('email', 'Email')
        ->email('email', 'Email')
        ->required('phone', 'Phone')
        ->phone('phone', 'Phone')
        ->required('password', 'Password')
        ->minLength('password', 'Password', 8)
        ->matches('password_confirm', 'password', 'Kumpirmasyon ng password');

    if (!$validator->passes()) {
        $_SESSION['register_errors'] = $validator->getErrors();
        $_SESSION['register_old'] = $_POST;
        header('Location: /sitrass/public/auth/register');
        exit;
    }

    if ($userModel->emailExists($_POST['email'])) {
    $_SESSION['register_errors'] = ['May account na gumagamit ng email na ito.'];
    $_SESSION['register_old'] = $_POST;
    header('Location: /sitrass/public/auth/register');
    exit;
}
if ($userModel->phoneExists($_POST['phone'])) {
    $_SESSION['register_errors'] = ['May account na gumagamit ng phone number na ito.'];
    $_SESSION['register_old'] = $_POST;
    header('Location: /sitrass/public/auth/register');
    exit;
}

    $userId = $userModel->create([
        'role' => 'customer',
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'password' => $_POST['password'],
    ]);

    $customerModel = new Customer();
    $customerModel->create($userId);

    $_SESSION['login_error'] = 'Account na-create! Puwede ka nang mag-login (matapos ma-verify ng admin).';
    header('Location: /sitrass/public/auth/login');
    exit;
}
public function loggedin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /sitrass/public/auth/login');
        exit;
    }

    View::render('customer-landing', [
        'pageTitle' => 'SITRASS - Dashboard',
    ]);
}
public function forgotPassword() {
    $message = $_SESSION['forgot_message'] ?? null;
    $resetLink = $_SESSION['dev_reset_link'] ?? null;
    unset($_SESSION['forgot_message'], $_SESSION['dev_reset_link']);

    View::render('forgot-password', [
                'pageTitle' => t('title_forgot_password'),
        'message' => $message,
        'resetLink' => $resetLink,
    ]);
}

public function sendReset() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['forgot_message'] = 'Invalid o expired na session. Subukan ulit.';
        header('Location: /sitrass/public/auth/forgotPassword');
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $userModel = new User();
    $user = $userModel->getByEmail($email);

    // Parehong mensahe kahit walang account - para hindi malaman ng attacker
    // kung aling email ang meron ngang account (account enumeration).
    $_SESSION['forgot_message'] = 'Kung may account na gumagamit ng email na iyan, may reset link na ipinadala (o ipinakita sa ibaba).';

    if ($user && $user['status'] === 'active') {
        $token = $userModel->createResetToken($user['user_id']);
        $resetLink = '/sitrass/public/auth/resetPassword?token=' . $token;

        // DEV MODE: ipinapakita natin ang link dito sa halip na i-email.
        // Sa Step 13, papalitan ito ng: Mailer::send($user['email'], $resetLink);
        $_SESSION['dev_reset_link'] = $resetLink;
    }

    header('Location: /sitrass/public/auth/forgotPassword');
    exit;
}

public function resetPassword() {
    $token = $_GET['token'] ?? '';

    $userModel = new User();
    $reset = $userModel->verifyResetToken($token);

    if (!$reset) {
        View::render('reset-invalid', [
                        'pageTitle' => t('title_invalid_link'),
        ]);
        return;
    }

    $error = $_SESSION['reset_error'] ?? null;
    unset($_SESSION['reset_error']);

    View::render('reset-password', [
        'pageTitle' => 'Bagong Password - SITRASS',
        'error' => $error,
        'token' => $token,
    ]);
}

public function updatePassword() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session. <a href="/sitrass/public/auth/forgotPassword">Simulan ulit</a>');
    }

    $token = $_POST['token'] ?? '';
    $userModel = new User();
    $reset = $userModel->verifyResetToken($token);

    if (!$reset) {
        die('Invalid o expired na link. <a href="/sitrass/public/auth/forgotPassword">Hiling ng bago</a>');
    }

    $validator = new Validator($_POST);
    $validator->required('password', 'Password')
        ->minLength('password', 'Password', 8)
        ->matches('password_confirm', 'password', 'Kumpirmasyon ng password');

    if (!$validator->passes()) {
        $_SESSION['reset_error'] = $validator->firstError();
        header('Location: /sitrass/public/auth/resetPassword?token=' . urlencode($token));
        exit;
    }

    $tokenHash = hash('sha256', $token);
    $userModel->resetPassword($reset['user_id'], $_POST['password'], $tokenHash);

    $_SESSION['login_error'] = 'Na-update na ang password mo. Puwede ka nang mag-login.';
    header('Location: /sitrass/public/auth/login');
    exit;
}
public function registerDriver() {
    if (isset($_SESSION['user_id'])) {
        header('Location: /sitrass/public/admin/dashboard');
        exit;
    }

    $errors = $_SESSION['driver_register_errors'] ?? [];
    $old = $_SESSION['driver_register_old'] ?? [];
    unset($_SESSION['driver_register_errors'], $_SESSION['driver_register_old']);

    View::render('register-driver', [
                'pageTitle' => t('dreg_title'),
        'errors' => $errors,
        'old' => $old,
    ]);
}

public function storeDriver() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['driver_register_errors'] = ['Invalid o expired na session. Subukan ulit.'];
        header('Location: /sitrass/public/auth/registerDriver');
        exit;
    }

    $userModel = new User();

    $validator = new Validator($_POST);
    $validator->required('first_name', 'First name')
        ->required('last_name', 'Last name')
        ->required('email', 'Email')
        ->email('email', 'Email')
        ->required('phone', 'Phone')
        ->phone('phone', 'Phone')
        ->required('license_number', 'License number')
        ->required('license_expiry', 'License expiry')
        ->required('password', 'Password')
        ->minLength('password', 'Password', 8)
        ->matches('password_confirm', 'password', 'Kumpirmasyon ng password');

    if (!$validator->passes()) {
        $_SESSION['driver_register_errors'] = $validator->getErrors();
        $_SESSION['driver_register_old'] = $_POST;
        header('Location: /sitrass/public/auth/registerDriver');
        exit;
    }

    if ($userModel->emailExists($_POST['email'])) {
        $_SESSION['driver_register_errors'] = ['May account na gumagamit ng email na ito.'];
        $_SESSION['driver_register_old'] = $_POST;
        header('Location: /sitrass/public/auth/registerDriver');
        exit;
    }

    if ($userModel->phoneExists($_POST['phone'])) {
        $_SESSION['driver_register_errors'] = ['May account na gumagamit ng phone number na ito.'];
        $_SESSION['driver_register_old'] = $_POST;
        header('Location: /sitrass/public/auth/registerDriver');
        exit;
    }

    $driverModel = new Driver();
    if ($driverModel->licenseExists($_POST['license_number'])) {
        $_SESSION['driver_register_errors'] = ['May driver na gumagamit ng license number na ito.'];
        $_SESSION['driver_register_old'] = $_POST;
        header('Location: /sitrass/public/auth/registerDriver');
        exit;
    }

    $userId = $userModel->create([
        'role' => 'driver',
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'password' => $_POST['password'],
    ]);

    $driverModel->create($userId, [
        'license_number' => $_POST['license_number'],
        'license_expiry' => $_POST['license_expiry'],
        'years_experience' => $_POST['years_experience'] ?? 0,
    ]);

    $_SESSION['login_error'] = 'Nasumite ang application mo. Hihintayin ang pag-apruba ng admin bago makapag-login.';
    header('Location: /sitrass/public/auth/login');
    exit;
}
}