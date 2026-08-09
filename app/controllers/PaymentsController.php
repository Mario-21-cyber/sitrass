<?php

class PaymentsController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /sitrass/public/auth/login');
            exit;
        }
    }

    public function index() {
        $paymentModel = new Payment();
        $pending = $paymentModel->getPending();

        View::render('admin-payments-list', [
            'pageTitle' => 'Mga Pending Payment - SITRASS Admin',
            'pageHeading' => 'Mga Pending Payment',
            'payments' => $pending,
        ]);
    }

    public function verify() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $paymentId = (int)($_POST['payment_id'] ?? 0);
    if ($paymentId > 0) {
        $paymentModel = new Payment();
        $paymentModel->verify($paymentId, $_SESSION['user_id']);

        $auditModel = new AuditLog();
        $auditModel->log($_SESSION['user_id'], 'payment.verified', 'payment', $paymentId);
    }

    header('Location: /sitrass/public/payments');
    exit;
}

    public function reject() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Hindi ma-verify ang payment');

        if ($paymentId > 0) {
            $paymentModel = new Payment();
            $paymentModel->reject($paymentId, $reason);
        }

        header('Location: /sitrass/public/payments');
        exit;
    }
    public function methods() {
    $methodModel = new PaymentMethod();

    $saved = $_SESSION['method_saved'] ?? false;
    unset($_SESSION['method_saved']);

    View::render('admin-payment-methods', [
        'pageTitle' => 'Mga Paraan ng Bayad - SITRASS Admin',
        'pageHeading' => 'Mga Paraan ng Bayad',
        'methods' => $methodModel->getActive(),
        'saved' => $saved,
    ]);
}

public function updateMethod() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $methodId = (int)($_POST['method_id'] ?? 0);
    if ($methodId > 0) {
        $methodModel = new PaymentMethod();
        $methodModel->update($methodId, $_POST);
    }

    $_SESSION['method_saved'] = true;
    header('Location: /sitrass/public/payments/methods');
    exit;
}
}