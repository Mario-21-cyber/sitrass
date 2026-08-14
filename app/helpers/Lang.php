<?php

class Lang {
    protected static $strings = null;

    public static function current() {
        if (isset($_SESSION['lang'])) {
            return $_SESSION['lang'];
        }
        if (isset($_COOKIE['sitrass_lang'])) {
            return $_COOKIE['sitrass_lang'];
        }
        return 'tl'; // default
    }

    public static function set($lang) {
        $lang = in_array($lang, ['tl', 'en']) ? $lang : 'tl';
        $_SESSION['lang'] = $lang;
        setcookie('sitrass_lang', $lang, time() + (60 * 60 * 24 * 365), '/');
    }

    public static function load() {
        if (self::$strings === null) {
            $lang = self::current();
            $file = __DIR__ . '/../lang/' . $lang . '.php';
            self::$strings = file_exists($file) ? require $file : [];
        }
        return self::$strings;
    }
}

// Global helper function - t('key') sa halip na Lang::load()['key']
function t($key) {
    $strings = Lang::load();
    return $strings[$key] ?? $key;
}