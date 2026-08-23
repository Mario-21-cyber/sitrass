<?php

class LocationsController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /sitrass/public/auth/login');
            exit;
        }
    }

    public function index() {
        $locationModel = new Location();
        $locations = $locationModel->getAll();

        View::render('admin-locations-list', [
            'pageTitle' => 'Mga Lokasyon - SITRASS Admin',
                        'pageHeading' => t('locations_page_title'),
            'locations' => $locations,
        ]);
    }

    public function create() {
        $errors = $_SESSION['location_errors'] ?? [];
        $old = $_SESSION['location_old'] ?? [];
        unset($_SESSION['location_errors'], $_SESSION['location_old']);

        View::render('admin-locations-create', [
                        'pageTitle' => t('location_create_title') . ' - SITRASS Admin',
            'pageHeading' => t('location_create_title'),
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    public function store() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['location_errors'] = ['Invalid o expired na session. Subukan ulit.'];
            header('Location: /sitrass/public/locations/create');
            exit;
        }

        $validator = new Validator($_POST);
        $validator->required('name', 'Pangalan')
            ->required('location_type', 'Tipo')
            ->required('category', 'Kategorya')
            ->required('municipality', 'Munisipyo')
            ->required('latitude', 'Latitude')
            ->required('longitude', 'Longitude');

        if (!$validator->passes()) {
            $_SESSION['location_errors'] = $validator->getErrors();
            $_SESSION['location_old'] = $_POST;
            header('Location: /sitrass/public/locations/create');
            exit;
        }

        $locationModel = new Location();
        $locationModel->create($_POST);

        header('Location: /sitrass/public/locations');
        exit;
    }
}