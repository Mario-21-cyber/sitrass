<?php

class FeedbackController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /sitrass/public/auth/login');
            exit;
        }
    }

    public function index() {
        $feedbackModel = new Feedback();
        $items = $feedbackModel->getAll();

        View::render('admin-feedback-list', [
            'pageTitle' => 'Feedback - SITRASS Admin',
            'pageHeading' => 'Mga Feedback',
            'items' => $items,
        ]);
    }

    public function respond() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $feedbackId = (int)($_POST['feedback_id'] ?? 0);
        $response = trim($_POST['response'] ?? '');

        if ($feedbackId > 0 && $response) {
            $feedbackModel = new Feedback();
            $feedbackModel->respond($feedbackId, $response, $_SESSION['user_id']);

            $auditModel = new AuditLog();
            $auditModel->log($_SESSION['user_id'], 'feedback.resolved', 'feedback', $feedbackId);
        }

        header('Location: /sitrass/public/feedback');
        exit;
    }

    public function markInReview() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $feedbackId = (int)($_POST['feedback_id'] ?? 0);
        if ($feedbackId > 0) {
            $feedbackModel = new Feedback();
            $feedbackModel->updateStatus($feedbackId, 'in_review');
        }

        header('Location: /sitrass/public/feedback');
        exit;
    }
}