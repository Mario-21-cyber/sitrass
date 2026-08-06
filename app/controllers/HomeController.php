<?php

class HomeController extends Controller {
    public function index() {
        View::render('home', [
            'pageTitle' => 'SITRASS - Sibuyan Island Transportation',
        ]);
    }
}