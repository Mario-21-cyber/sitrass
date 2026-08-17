<?php

class ProfileController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /sitrass/public/auth/login');
            exit;
        }
    }

    public function edit() {
        $userModel = new User();
        $user = $userModel->getById($_SESSION['user_id']);

        $driver = null;
        if ($_SESSION['role'] === 'driver') {
            $driverModel = new Driver();
            $driver = $driverModel->getByUserId($_SESSION['user_id']);
        }

        $message = $_SESSION['profile_message'] ?? null;
        $error = $_SESSION['profile_error'] ?? null;
        unset($_SESSION['profile_message'], $_SESSION['profile_error']);

        View::render('profile-edit', [
            'pageTitle' => 'Aking Profile - SITRASS',
            'user' => $user,
            'driver' => $driver,
            'message' => $message,
            'error' => $error,
        ]);
    }

    public function update() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['profile_error'] = 'Invalid o expired na session.';
            header('Location: /sitrass/public/profile/edit');
            exit;
        }

        $validator = new Validator($_POST);
        $validator->required('first_name', 'First name')
            ->required('last_name', 'Last name')
            ->required('phone', 'Phone')
            ->phone('phone', 'Phone');

        if (!$validator->passes()) {
            $_SESSION['profile_error'] = $validator->firstError();
            header('Location: /sitrass/public/profile/edit');
            exit;
        }

        $userModel = new User();

        // I-check kung ginagamit na ng ibang account ang bagong phone number
        $currentUser = $userModel->getById($_SESSION['user_id']);
        if ($_POST['phone'] !== $currentUser['phone'] && $userModel->phoneExists($_POST['phone'])) {
            $_SESSION['profile_error'] = 'May account na gumagamit ng phone number na ito.';
            header('Location: /sitrass/public/profile/edit');
            exit;
        }

        $userModel->updateProfile($_SESSION['user_id'], $_POST);
        $_SESSION['full_name'] = $_POST['first_name'] . ' ' . $_POST['last_name'];

        $_SESSION['profile_message'] = 'Na-update ang profile.';
        header('Location: /sitrass/public/profile/edit');
        exit;
    }

    public function uploadPicture() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['profile_error'] = 'Invalid o expired na session.';
            header('Location: /sitrass/public/profile/edit');
            exit;
        }

        $result = ImageUpload::handle($_FILES['picture'] ?? null, 'uploads/profiles', 'user' . $_SESSION['user_id']);

        if (!$result['success']) {
            $_SESSION['profile_error'] = $result['error'];
            header('Location: /sitrass/public/profile/edit');
            exit;
        }

        $userModel = new User();
        $userModel->updateProfilePicture($_SESSION['user_id'], $result['thumbnail']);

        $_SESSION['profile_message'] = 'Na-update ang profile picture.';
        header('Location: /sitrass/public/profile/edit');
        exit;
    }

    public function uploadLicense() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['profile_error'] = 'Invalid o expired na session.';
            header('Location: /sitrass/public/profile/edit');
            exit;
        }

        if ($_SESSION['role'] !== 'driver') {
            die('Para lang ito sa driver.');
        }

        $result = ImageUpload::handle($_FILES['license'] ?? null, 'uploads/licenses', 'lic' . $_SESSION['user_id']);

        if (!$result['success']) {
            $_SESSION['profile_error'] = $result['error'];
            header('Location: /sitrass/public/profile/edit');
            exit;
        }

        $driverModel = new Driver();
        $driver = $driverModel->getByUserId($_SESSION['user_id']);
        $driverModel->updateLicenseImage($driver['driver_id'], $result['path']);

        $_SESSION['profile_message'] = 'Na-upload ang larawan ng lisensya.';
        header('Location: /sitrass/public/profile/edit');
        exit;
    }

    public function changePassword() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['profile_error'] = 'Invalid o expired na session.';
            header('Location: /sitrass/public/profile/edit');
            exit;
        }

        $userModel = new User();

        if (!$userModel->verifyCurrentPassword($_SESSION['user_id'], $_POST['current_password'] ?? '')) {
            $_SESSION['profile_error'] = 'Mali ang kasalukuyang password.';
            header('Location: /sitrass/public/profile/edit');
            exit;
        }

        $validator = new Validator($_POST);
        $validator->required('new_password', 'Bagong password')
            ->minLength('new_password', 'Bagong password', 8)
            ->matches('new_password_confirm', 'new_password', 'Kumpirmasyon');

        if (!$validator->passes()) {
            $_SESSION['profile_error'] = $validator->firstError();
            header('Location: /sitrass/public/profile/edit');
            exit;
        }

        $userModel->changePassword($_SESSION['user_id'], $_POST['new_password']);

        $_SESSION['profile_message'] = 'Na-update ang password.';
        header('Location: /sitrass/public/profile/edit');
        exit;
    }
    public function setLanguage() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        header('Location: /sitrass/public/profile/edit');
        exit;
    }
    Lang::set($_POST['lang'] ?? 'tl');
    header('Location: /sitrass/public/profile/edit');
    exit;
}
}