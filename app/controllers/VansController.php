<?php

class VansController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /sitrass/public/auth/login');
            exit;
        }
    }

    public function index() {
        $vanModel = new Van();
        $vans = $vanModel->getAll();

        View::render('admin-vans-list', [
            'pageTitle' => 'Fleet - SITRASS Admin',
                        'pageHeading' => t('vans_page_title'),
            'vans' => $vans,
        ]);
    }

    public function create() {
        $errors = $_SESSION['van_errors'] ?? [];
        $old = $_SESSION['van_old'] ?? [];
        unset($_SESSION['van_errors'], $_SESSION['van_old']);

        View::render('admin-vans-create', [
                        'pageTitle' => t('van_create_title') . ' - SITRASS Admin',
            'pageHeading' => t('van_create_title'),
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    public function store() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['van_errors'] = ['Invalid o expired na session. Subukan ulit.'];
            header('Location: /sitrass/public/vans/create');
            exit;
        }

        $validator = new Validator($_POST);
        $validator->required('plate_number', 'Plate number')
            ->required('make', 'Make')
            ->required('model', 'Model')
            ->required('van_type', 'Van type')
            ->required('seating_capacity', 'Seating capacity');

        if (!$validator->passes()) {
            $_SESSION['van_errors'] = $validator->getErrors();
            $_SESSION['van_old'] = $_POST;
            header('Location: /sitrass/public/vans/create');
            exit;
        }

        $vanModel = new Van();

        if ($vanModel->plateExists($_POST['plate_number'])) {
            $_SESSION['van_errors'] = ['May van na gumagamit ng plate number na ito.'];
            $_SESSION['van_old'] = $_POST;
            header('Location: /sitrass/public/vans/create');
            exit;
        }

        $vanModel->create($_POST);

        header('Location: /sitrass/public/vans');
        exit;
    }

    public function toggleStatus() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $vanId = (int)($_POST['van_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';

        $allowed = ['active', 'maintenance', 'inactive'];
        if ($vanId > 0 && in_array($newStatus, $allowed)) {
            $vanModel = new Van();
            $vanModel->updateStatus($vanId, $newStatus);
        }

        header('Location: /sitrass/public/vans');
        exit;
    }
    public function images($vanId) {
    $vanId = (int)$vanId;
    $vanModel = new Van();
    $van = $vanModel->getById($vanId);

    if (!$van) {
        die('Van not found.');
    }

    $imageModel = new VanImage();
    $images = $imageModel->getByVanId($vanId);

    $error = $_SESSION['image_error'] ?? null;
    unset($_SESSION['image_error']);

    View::render('admin-vans-images', [
        'pageTitle' => 'Mga Larawan - ' . $van['plate_number'],
        'pageHeading' => 'Mga Larawan: ' . htmlspecialchars($van['plate_number']),
        'van' => $van,
        'images' => $images,
        'error' => $error,
    ]);
}

public function uploadImage() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['image_error'] = 'Invalid o expired na session. Subukan ulit.';
        header('Location: /sitrass/public/vans/images/' . (int)($_POST['van_id'] ?? 0));
        exit;
    }

    $vanId = (int)($_POST['van_id'] ?? 0);
    $vanModel = new Van();
    $van = $vanModel->getById($vanId);

    if (!$van) {
        die('Van not found.');
    }

    $result = ImageUpload::handle($_FILES['image'] ?? null, 'uploads/vans', 'van' . $vanId);

    if (!$result['success']) {
        $_SESSION['image_error'] = $result['error'];
        header('Location: /sitrass/public/vans/images/' . $vanId);
        exit;
    }

    $imageModel = new VanImage();

    // Kung ito ang unang larawan ng van na ito, gawin itong primary awtomatiko.
    $isPrimary = $imageModel->countByVanId($vanId) === 0;

    if ($isPrimary) {
        $imageModel->clearPrimary($vanId);
    }

    $imageModel->create($vanId, $result['path'], $result['thumbnail'], $isPrimary, $_SESSION['user_id']);

    header('Location: /sitrass/public/vans/images/' . $vanId);
    exit;
}

public function setPrimaryImage() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $imageId = (int)($_POST['image_id'] ?? 0);
    $vanId = (int)($_POST['van_id'] ?? 0);

    $imageModel = new VanImage();
    $imageModel->clearPrimary($vanId);

    $stmt = null; // ginagamit lang natin ang db directly dito para sa simpleng update
    $db = (new Van())->getById($vanId); // tinitiyak lang na valid ang van
    if ($db) {
        $conn = new PDO('mysql:host=localhost;dbname=sitrass_db;charset=utf8mb4', 'root', '');
        $stmt = $conn->prepare("UPDATE van_images SET is_primary = 1 WHERE image_id = ? AND van_id = ?");
        $stmt->execute([$imageId, $vanId]);
    }

    header('Location: /sitrass/public/vans/images/' . $vanId);
    exit;
}

public function deleteImage() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $imageId = (int)($_POST['image_id'] ?? 0);
    $vanId = (int)($_POST['van_id'] ?? 0);

    $imageModel = new VanImage();
    $image = $imageModel->getById($imageId);

    if ($image && $image['van_id'] == $vanId) {
        // Burahin ang aktwal na file sa server, hindi lang ang database record.
        $filePath = __DIR__ . '/../../public' . str_replace('/sitrass/public', '', $image['image_path']);
        $thumbPath = __DIR__ . '/../../public' . str_replace('/sitrass/public', '', $image['thumbnail_path']);

        if (file_exists($filePath)) {
            unlink($filePath);
        }
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }

        $imageModel->delete($imageId);
    }

    header('Location: /sitrass/public/vans/images/' . $vanId);
    exit;
}
}