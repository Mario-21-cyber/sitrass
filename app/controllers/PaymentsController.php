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
        $payment = $paymentModel->getById($paymentId);
        $paymentModel->verify($paymentId, $_SESSION['user_id']);

        $auditModel = new AuditLog();
        $auditModel->log($_SESSION['user_id'], 'payment.verified', 'payment', $paymentId);

        // Kunin ang email ng customer para sa notification
        $db = (new Model())->getConnection();
        $stmt = $db->prepare(
            "SELECT u.email, u.first_name, rs.reference_code, rs.total_amount, rs.balance_due
             FROM payments p
             JOIN reservations rs ON rs.reservation_id = p.reservation_id
             JOIN customers c ON c.customer_id = rs.customer_id
             JOIN users u ON u.user_id = c.user_id
             WHERE p.payment_id = ?"
        );
        $stmt->execute([$paymentId]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($info) {
            $balanceMessage = $info['balance_due'] > 0
                ? '<p>Natitirang balance: ₱' . number_format($info['balance_due'], 2) . '</p>'
                : '<p>Buo nang bayad ang reservation mo!</p>';

            Mailer::send(
                $info['email'],
                $info['first_name'],
                'Na-verify na ang Bayad Mo - ' . $info['reference_code'],
                '<p>Kumusta, ' . htmlspecialchars($info['first_name']) . '!</p>
                 <p>Na-verify na ng admin ang iyong bayad para sa reservation <strong>' . htmlspecialchars($info['reference_code']) . '</strong>.</p>'
                 . $balanceMessage
            );
        }
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