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
            <a href="/sitrass/public/auth/logout">Logout</a>
        </body>
        </html>';
    }
}