<?php

class AuthController extends Controller {

    // Ipapakita ang login form
    public function login() {
        // Kung naka-login na, diretso na sa dashboard
        if (isset($_SESSION['user_id'])) {
            header('Location: /sitrass/public/admin/dashboard');
            exit;
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        echo '<!DOCTYPE html>
        <html>
        <head><title>SITRASS - Login</title></head>
        <body>
            <h2>SITRASS Admin Login</h2>';

        if ($error) {
            echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>';
        }

        echo '<form method="POST" action="/sitrass/public/auth/authenticate">
        ' . Csrf::field() . '
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>';
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

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['login_error'] = 'Maling email o password.';
            header('Location: /sitrass/public/auth/login');
            exit;
        }

        if ($user['status'] !== 'active') {
            $_SESSION['login_error'] = 'Hindi active ang account na ito.';
            header('Location: /sitrass/public/auth/login');
            exit;
        }

        // Successful login - itago sa session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];

        header('Location: /sitrass/public/admin/dashboard');
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

    echo '<!DOCTYPE html>
    <html>
    <head><title>SITRASS - Register</title></head>
    <body>
        <h2>Gumawa ng Account (Customer)</h2>';

    if (!empty($errors)) {
        echo '<ul style="color:red;">';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
    }

    echo '<form method="POST" action="/sitrass/public/auth/store">
            ' . Csrf::field() . '
            <label>First Name:</label><br>
            <input type="text" name="first_name" value="' . htmlspecialchars($old['first_name'] ?? '') . '" required><br><br>

            <label>Last Name:</label><br>
            <input type="text" name="last_name" value="' . htmlspecialchars($old['last_name'] ?? '') . '" required><br><br>

            <label>Email:</label><br>
            <input type="email" name="email" value="' . htmlspecialchars($old['email'] ?? '') . '" required><br><br>

            <label>Phone (+639XXXXXXXXX):</label><br>
            <input type="text" name="phone" value="' . htmlspecialchars($old['phone'] ?? '') . '" required><br><br>

            <label>Password:</label><br>
            <input type="password" name="password" required><br><br>

            <label>Confirm Password:</label><br>
            <input type="password" name="password_confirm" required><br><br>

            <button type="submit">Register</button>
        </form>
        <p>May account na? <a href="/sitrass/public/auth/login">Login dito</a></p>
    </body>
    </html>';
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
}