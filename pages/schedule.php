<?php
/**
 * Class Schedule Management Page.
 * Displays weekly schedule, handles class creation, editing, deletion, and remaining seat tracking.
 */

$page_title = 'Class Schedule';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/gym_class.php';

$pdo_connection = getDbConnection();
$class_model = new GymClass($pdo_connection);

$status_message = '';
$status_type = '';

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    if ($class_model->delete($delete_id)) {
        $status_message = "Class successfully removed.";
        $status_type = "success";
    } else {
        $status_message = "Failed to remove class.";
        $status_type = "error";
    }
}

// Handle Form Submission (Add or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id      = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;
    $class_name    = sanitize_input($_POST['class_name'] ?? '');
    $instructor_id = (int)($_POST['instructor_id'] ?? 0);
    $class_date    = sanitize_input($_POST['class_date'] ?? '');
    $start_time    = sanitize_input($_POST['start_time'] ?? '');
    $capacity      = (int)($_POST['capacity'] ?? 15);
    $room          = sanitize_input($_POST['room'] ?? '');

    if (empty($class_name) || empty($instructor_id) || empty($class_date) || empty($start_time) || $capacity <= 0) {
        $status_message = "Please fill in all required fields and enter a valid capacity.";
        $status_type = "error";
    } else {
        $class_data = [
            'class_name'    => $class_name,
            'instructor_id' => $instructor_id,
            'class_date'    => $class_date,
            'start_time'    => $start_time,
            'capacity'      => $capacity,
            'room'          => $room,
        ];

        try {
            if ($class_id) {
                $class_model->update($class_id, $class_data);
                $status_message = "Class schedule updated successfully.";
                $status_type = "success";
            } else {
                $class_model->create($class_data);
                $status_message = "New class scheduled successfully.";
                $status_type = "success";
            }
        } catch (PDOException $exception) {
            $status_message = "Database error: " . $exception->getMessage();
            $status_type = "error";
        }
    }
}

// Fetch class for editing
$edit_class = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_class = $class_model->getById((int)$_GET['id']);
}

$default_capacity = (int)get_setting($pdo_connection, 'default_class_capacity', '15');
$all_classes = $class_model->getAll();
$instructors = $class_model->getInstructors();
?>

<div class="page-title-row">
    <h1>Class Schedule</h1>
</div>

<?php if (!empty($status_message)): ?>
    <div class="alert alert-<?php echo escape($status_type); ?>">
        <?php echo escape($status_message); ?>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Schedule / Add Form Card -->
    <div class="col-form">
        <div class="card">
            <div class="card-header">
                <h2><?php echo $edit_class ? 'Edit Class' : 'Schedule New Class'; ?></h2>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo escape(BASE_URL); ?>/pages/schedule.php" class="validated-form">
                    <?php if ($edit_class): ?>
                        <input type="hidden" name="class_id" value="<?php echo escape($edit_class['id']); ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="class_name">Class Name *</label>
                        <input type="text" id="class_name" name="class_name" required class="form-control"
                               value="<?php echo escape($edit_class['class_name'] ?? ''); ?>" placeholder="e.g. Spin Cycle">
                    </div>

                    <div class="form-group">
                        <label for="instructor_id">Instructor *</label>
                        <select id="instructor_id" name="instructor_id" required class="form-control">
                            <option value="">Select an Instructor</option>
                            <?php foreach ($instructors as $instructor_row): ?>
                                <option value="<?php echo escape($instructor_row['id']); ?>"
                                    <?php echo (isset($edit_class['instructor_id']) && $edit_class['instructor_id'] == $instructor_row['id']) ? 'selected' : ''; ?>>
                                    <?php echo escape($instructor_row['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="class_date">Date *</label>
                        <input type="date" id="class_date" name="class_date" required class="form-control"
                               value="<?php echo escape($edit_class['class_date'] ?? date('Y-m-d')); ?>">
                    </div>

                    <div class="form-group">
                        <label for="start_time">Start Time *</label>
                        <input type="time" id="start_time" name="start_time" required class="form-control"
                               value="<?php echo escape($edit_class['start_time'] ?? '09:00'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="capacity">Capacity (Seats) *</label>
                        <input type="number" id="capacity" name="capacity" min="1" required class="form-control"
                               value="<?php echo escape($edit_class['capacity'] ?? $default_capacity); ?>">
                    </div>

                    <div class="form-group">
                        <label for="room">Studio / Room</label>
                        <input type="text" id="room" name="room" class="form-control"
                               value="<?php echo escape($edit_class['room'] ?? 'Studio A'); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_class ? 'Update Class' : 'Schedule Class'; ?>
                    </button>
                    <?php if ($edit_class): ?>
                        <a href="<?php echo escape(BASE_URL); ?>/pages/schedule.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Class List / Schedule Card -->
    <div class="col-table">
        <div class="card">
            <div class="card-header">
                <h2>Scheduled Classes</h2>
            </div>
            <div class="card-body">
                <?php if (empty($all_classes)): ?>
                    <p class="text-muted">No classes scheduled.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date &amp; Time</th>
                                    <th>Class</th>
                                    <th>Instructor</th>
                                    <th>Room</th>
                                    <th>Capacity</th>
                                    <th>Seats Left</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_classes as $class_row): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo escape($class_row['class_date']); ?></strong><br>
                                            <small class="text-muted"><?php echo escape(substr($class_row['start_time'], 0, 5)); ?></small>
                                        </td>
                                        <td><?php echo escape($class_row['class_name']); ?></td>
                                        <td><?php echo escape($class_row['instructor_name']); ?></td>
                                        <td><?php echo escape($class_row['room']); ?></td>
                                        <td><?php echo escape($class_row['registered_count'] . ' / ' . $class_row['capacity']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $class_row['seats_remaining'] > 0 ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo escape($class_row['seats_remaining']); ?> seats
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <a href="<?php echo escape(BASE_URL); ?>/pages/schedule.php?action=edit&amp;id=<?php echo escape($class_row['id']); ?>" 
                                               class="btn btn-sm btn-secondary">Edit</a>
                                            <a href="<?php echo escape(BASE_URL); ?>/pages/schedule.php?action=delete&amp;id=<?php echo escape($class_row['id']); ?>" 
                                               class="btn btn-sm btn-danger delete-btn" data-confirm="Are you sure you want to remove this class?">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
