<?php

class View {

    public static function render($template, $data = []) {
        // Ginagawa ang bawat key sa $data na variable, hal. $data['error'] -> $error
        extract($data);

        $templateFile = __DIR__ . '/templates/' . $template . '.php';

        if (!file_exists($templateFile)) {
            die("View template not found: $template");
        }

        require $templateFile;
    }
}