<?php

class RoutesController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /sitrass/public/auth/login');
            exit;
        }
    }

    public function index() {
        $routeModel = new Route();
        $routes = $routeModel->getAll();

        View::render('admin-routes-list', [
            'pageTitle' => 'Mga Ruta - SITRASS Admin',
            'pageHeading' => 'Mga Ruta',
            'routes' => $routes,
        ]);
    }

    public function create() {
        $locationModel = new Location();
        $locations = $locationModel->getAll();

        $errors = $_SESSION['route_errors'] ?? [];
        $old = $_SESSION['route_old'] ?? [];
        unset($_SESSION['route_errors'], $_SESSION['route_old']);

        View::render('admin-routes-create', [
            'pageTitle' => 'Magdagdag ng Ruta - SITRASS Admin',
            'pageHeading' => 'Magdagdag ng Ruta',
            'locations' => $locations,
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    public function store() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['route_errors'] = ['Invalid o expired na session. Subukan ulit.'];
            header('Location: /sitrass/public/routes/create');
            exit;
        }

        $validator = new Validator($_POST);
        $validator->required('route_code', 'Route code')
            ->required('route_name', 'Route name')
            ->required('origin_location_id', 'Origin')
            ->required('destination_location_id', 'Destination')
            ->required('road_condition', 'Kalagayan ng daan');

        if (!$validator->passes()) {
            $_SESSION['route_errors'] = $validator->getErrors();
            $_SESSION['route_old'] = $_POST;
            header('Location: /sitrass/public/routes/create');
            exit;
        }

        if ($_POST['origin_location_id'] === $_POST['destination_location_id']) {
            $_SESSION['route_errors'] = ['Hindi puwedeng magkapareho ang origin at destination.'];
            $_SESSION['route_old'] = $_POST;
            header('Location: /sitrass/public/routes/create');
            exit;
        }

        $routeModel = new Route();

        if ($routeModel->codeExists($_POST['route_code'])) {
            $_SESSION['route_errors'] = ['May route na gumagamit ng code na ito.'];
            $_SESSION['route_old'] = $_POST;
            header('Location: /sitrass/public/routes/create');
            exit;
        }

        $routeModel->create($_POST);

        header('Location: /sitrass/public/routes');
        exit;
    }
}