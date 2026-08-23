<?php

class SchedulesController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /sitrass/public/auth/login');
            exit;
        }
    }

    public function index() {
        $scheduleModel = new TripSchedule();
        $schedules = $scheduleModel->getAll();

        View::render('admin-schedules-list', [
            'pageTitle' => 'Mga Schedule - SITRASS Admin',
            'pageHeading' => t('schedules_page_title'),
            'schedules' => $schedules,
        ]);
    }

    public function create() {
        $routeModel = new Route();
        $vanModel = new Van();
        $driverModel = new Driver();

        $errors = $_SESSION['schedule_errors'] ?? [];
        $old = $_SESSION['schedule_old'] ?? [];
        unset($_SESSION['schedule_errors'], $_SESSION['schedule_old']);

        View::render('admin-schedules-create', [
                        'pageTitle' => t('schedule_create_title') . ' - SITRASS Admin',
            'pageHeading' => t('schedule_create_title'),
            'routes' => $routeModel->getAll(),
            'vans' => $vanModel->getAll(),
            'drivers' => $driverModel->getAllApproved(),
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    public function store() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['schedule_errors'] = ['Invalid o expired na session. Subukan ulit.'];
            header('Location: /sitrass/public/schedules/create');
            exit;
        }

        $validator = new Validator($_POST);
        $validator->required('route_id', 'Ruta')
            ->required('van_id', 'Van')
            ->required('departure_date', 'Petsa ng biyahe')
            ->required('departure_time', 'Oras ng alis')
            ->required('total_seats', 'Bilang ng upuan')
            ->required('fare_per_seat', 'Pamasahe')
            ->required('booking_mode', 'Booking mode');

        if (!$validator->passes()) {
            $_SESSION['schedule_errors'] = $validator->getErrors();
            $_SESSION['schedule_old'] = $_POST;
            header('Location: /sitrass/public/schedules/create');
            exit;
        }

        // Hindi puwedeng nakaraan na ang petsa
        if ($_POST['departure_date'] < date('Y-m-d')) {
            $_SESSION['schedule_errors'] = ['Hindi puwedeng nakaraang petsa ang piliin.'];
            $_SESSION['schedule_old'] = $_POST;
            header('Location: /sitrass/public/schedules/create');
            exit;
        }

        $scheduleModel = new TripSchedule();

        if ($scheduleModel->slotTaken($_POST['van_id'], $_POST['departure_date'], $_POST['departure_time'])) {
            $_SESSION['schedule_errors'] = ['May schedule na ang van na ito sa parehong petsa at oras.'];
            $_SESSION['schedule_old'] = $_POST;
            header('Location: /sitrass/public/schedules/create');
            exit;
        }

        $data = $_POST;
        $data['created_by'] = $_SESSION['user_id'];
        $scheduleModel->create($data);

        header('Location: /sitrass/public/schedules');
        exit;
    }

    public function cancel() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Cancelled by admin');

        if ($scheduleId > 0) {
            $scheduleModel = new TripSchedule();
            $scheduleModel->cancel($scheduleId, $reason);
        }

        header('Location: /sitrass/public/schedules');
        exit;
    }
}