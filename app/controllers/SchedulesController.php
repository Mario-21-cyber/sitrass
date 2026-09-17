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

        // 14 seats lang ang cap kada schedule ( Requirement)
        if ((int)($_POST['total_seats'] ?? 0) > 14) {
            $_SESSION['schedule_errors'] = ['Hanggang 14 seats lang ang maaaring i-set kada schedule.'];
            $_SESSION['schedule_old'] = $_POST;
            header('Location: /sitrass/public/schedules/create');
            exit;
        }

        // Per-seat na lang ang booking mode - ang whole-van rental ay
        // hiwalay nang feature (Rent a Van sa customer side).
        if (($_POST['booking_mode'] ?? '') !== 'seat') {
            $_SESSION['schedule_errors'] = ['Per-seat na lang ang booking mode sa mga schedule.'];
            $_SESSION['schedule_old'] = $_POST;
            header('Location: /sitrass/public/schedules/create');
            exit;
        }

                $scheduleModel = new TripSchedule();

        // Kunin ang tantiyang tagal ng biyahe batay sa napiling ruta - dito
        // natin ikukumpara kung nag-o-overlap ang bagong schedule sa iba.
        $db = (new Model())->getConnection();
        $stmt = $db->prepare("SELECT estimated_duration_minutes FROM routes WHERE route_id = ?");
        $stmt->execute([$_POST['route_id']]);
        $routeDuration = (int)$stmt->fetchColumn() ?: 60;

        if ($scheduleModel->hasVanConflict($_POST['van_id'], $_POST['departure_date'], $_POST['departure_time'], $routeDuration)) {
            $_SESSION['schedule_errors'] = [t('error_van_conflict')];
            $_SESSION['schedule_old'] = $_POST;
            header('Location: /sitrass/public/schedules/create');
            exit;
        }

        if (!empty($_POST['driver_id']) && $scheduleModel->hasDriverConflict($_POST['driver_id'], $_POST['departure_date'], $_POST['departure_time'], $routeDuration)) {
            $_SESSION['schedule_errors'] = [t('error_driver_conflict')];
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

            // May bayad nang booking dito? Bawal i-cancel ng admin -
            // may pera nang kasama ang biyaheng ito.
            if ($scheduleModel->hasPaidBooking($scheduleId)) {
                $_SESSION['schedule_cancel_error'] = t('error_schedule_paid_booking');
                header('Location: /sitrass/public/schedules');
                exit;
            }

            $scheduleModel->cancel($scheduleId, $reason);
        }

        header('Location: /sitrass/public/schedules');
        exit;
    }
}