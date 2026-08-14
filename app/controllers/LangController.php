<?php

class LangController extends Controller {

    public function set($lang) {
        Lang::set($lang);

        // Bumalik sa pahinang pinanggalingan, o sa home kung wala
        $referer = $_SERVER['HTTP_REFERER'] ?? '/sitrass/public/';
        header('Location: ' . $referer);
        exit;
    }
}