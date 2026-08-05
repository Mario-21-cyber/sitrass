<?php

class Csrf {

    // Gumawa ng bagong token at itago sa session
    public static function token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Nagbabalik ng ready-to-use hidden input field
    public static function field() {
        $token = self::token();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    // Vine-verify kung tama ang submitted token
    public static function verify($submittedToken) {
        if (empty($_SESSION['csrf_token']) || empty($submittedToken)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $submittedToken);
    }
}