<?php

class AdminController extends Controller {

    public function __construct() {
        // Protektahan ang lahat ng admin pages - kailangan naka-login bilang admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /sitrass/public/auth/login');
            exit;
        }
    }

    public function dashboard() {
    echo '<!DOCTYPE html>
    <html>
    <head><title>SITRASS - Admin Dashboard</title></head>
    <body>
        <h2>Maligayang pagdating, ' . htmlspecialchars($_SESSION['full_name']) . '!</h2>
        <p>Role: ' . htmlspecialchars($_SESSION['role']) . '</p>
        <p><a href="/sitrass/public/admin/pending-customers">Mga Naghihintay na Account</a></p>
        <a href="/sitrass/public/auth/logout">Logout</a>
    </body>
    </html>';
}
    public function pendingCustomers() {
    $userModel = new User();
    $pending = $userModel->getPending();

    echo '<!DOCTYPE html>
    <html>
    <head><title>SITRASS - Pending Accounts</title></head>
    <body>
        <h2>Mga Naghihintay na Account</h2>
        <a href="/sitrass/public/admin/dashboard">Bumalik sa Dashboard</a>
        <hr>';

    if (empty($pending)) {
        echo '<p>Walang naghihintay na account sa ngayon.</p>';
    } else {
        echo '<table border="1" cellpadding="8">
                <tr>
                    <th>Pangalan</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Ginawa noong</th>
                    <th>Aksyon</th>
                </tr>';
        foreach ($pending as $user) {
            echo '<tr>
                    <td>' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</td>
                    <td>' . htmlspecialchars($user['email']) . '</td>
                    <td>' . htmlspecialchars($user['phone']) . '</td>
                    <td>' . htmlspecialchars($user['role']) . '</td>
                    <td>' . htmlspecialchars($user['created_at']) . '</td>
                    <td>
                        <form method="POST" action="/sitrass/public/admin/approve" style="display:inline;">
                            ' . Csrf::field() . '
                            <input type="hidden" name="user_id" value="' . (int)$user['user_id'] . '">
                            <button type="submit">Aprubahan</button>
                        </form>
                    </td>
                </tr>';
        }
        echo '</table>';
    }

    echo '</body></html>';
}

public function approve() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session. <a href="/sitrass/public/admin/pending-customers">Bumalik</a>');
    }

    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        $userModel = new User();
        $userModel->approve($userId);
    }

    header('Location: /sitrass/public/admin/pending-customers');
    exit;
}
}