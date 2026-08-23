<?php

class RatingsController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /sitrass/public/auth/login');
            exit;
        }
    }

    public function index() {
        $ratingModel = new Rating();
        $ratings = $ratingModel->getAll();

        View::render('admin-ratings-list', [
            'pageTitle' => 'Mga Rating - SITRASS Admin',
                        'pageHeading' => t('ratings_page_title'),
            'ratings' => $ratings,
        ]);
    }

    public function hide() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $ratingId = (int)($_POST['rating_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Hindi angkop na nilalaman');

        if ($ratingId > 0) {
            $ratingModel = new Rating();
            $ratingModel->toggleVisibility($ratingId, true, $reason);

            $auditModel = new AuditLog();
            $auditModel->log($_SESSION['user_id'], 'rating.hidden', 'rating', $ratingId);
        }

        header('Location: /sitrass/public/ratings');
        exit;
    }

    public function unhide() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $ratingId = (int)($_POST['rating_id'] ?? 0);
        if ($ratingId > 0) {
            $ratingModel = new Rating();
            $ratingModel->toggleVisibility($ratingId, false);
        }

        header('Location: /sitrass/public/ratings');
        exit;
    }
}