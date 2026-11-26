<?php
/**
 * Class Registration Form Page.
 * Allows assigning a member to a class with duplicate check and capacity verification.
 */

$page_title = 'Class Registration';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/member.php';
require_once __DIR__ . '/../models/gym_class.php';
require_once __DIR__ . '/../models/registration.php';

$pdo_connection = getDbConnection();
$member_model = new Member($pdo_connection);
$class_model = new GymClass($pdo_connection);
$registration_model = new Registration($pdo_connection);

$status_message = '';
$status_type = '';

// Handle Cancellation / Deletion of Registration
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    $cancel_id = (int)$_GET['id'];
    if ($registration_model->delete($cancel_id)) {
        $status_message = "Registration successfully cancelled.";
        $status_type = "success";
    } else {
        $status_message = "Failed to cancel registration.";
        $status_type = "error";
    }
}

// Handle Enrollment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $class_id  = (int)($_POST['class_id'] ?? 0);

    if ($member_id <= 0 || $class_id <= 0) {
        $status_message = "Please select both a valid member and class.";
        $status_type = "error";
    } else {
        try {
            $registration_model->create([
                'member_id' => $member_id,
                'class_id'  => $class_id,
                'attendance_status' => 'Registered',
            ]);
            $status_message = "Member successfully registered for the class!";
            $status_type = "success";
        } catch (Exception $exception) {
            // Catches duplicate registration exception or class full exception
            $status_message = $exception->getMessage();
            $status_type = "error";
        }
    }
}

$all_members = $member_model->getAll();
$all_classes = $class_model->getAll();
$recent_registrations = $registration_model->getAll();
?>

<div class="page-title-row">
    <h1>Class Registration</h1>
</div>

<?php if (!empty($status_message)): ?>
    <div class="alert alert-<?php echo escape($status_type); ?>">
        <?php echo escape($status_message); ?>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Registration Form Card -->
    <div class="col-form">
        <div class="card">
            <div class="card-header">
                <h2>Enroll Member into Class</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo escape(BASE_URL); ?>/pages/registration_form.php" class="validated-form">
                    <div class="form-group">
                        <label for="member_id">Select Member *</label>
                        <select id="member_id" name="member_id" required class="form-control">
                            <option value="">-- Choose Member --</option>
                            <?php foreach ($all_members as $member_row): ?>
                                <option value="<?php echo escape($member_row['id']); ?>">
                                    <?php echo escape($member_row['first_name'] . ' ' . $member_row['last_name'] . ' (' . $member_row['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="class_id">Select Class *</label>
                        <select id="class_id" name="class_id" required class="form-control">
                            <option value="">-- Choose Scheduled Class --</option>
                            <?php foreach ($all_classes as $class_row): ?>
                                <?php $is_full = ($class_row['seats_remaining'] <= 0); ?>
                                <option value="<?php echo escape($class_row['id']); ?>" <?php echo $is_full ? 'disabled' : ''; ?>>
                                    <?php echo escape($class_row['class_name'] . ' - ' . $class_row['class_date'] . ' @ ' . substr($class_row['start_time'], 0, 5) . ' (' . $class_row['seats_remaining'] . ' seats left)'); ?>
                                    <?php if ($is_full) echo ' [FULL]'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Complete Registration</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Current Registrations Table Card -->
    <div class="col-table">
        <div class="card">
            <div class="card-header">
                <h2>Recent Registrations</h2>
            </div>
            <div class="card-body">
                <?php if (empty($recent_registrations)): ?>
                    <p class="text-muted">No class registrations found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Class</th>
                                    <th>Date &amp; Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_registrations as $reg_row): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo escape($reg_row['first_name'] . ' ' . $reg_row['last_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo escape($reg_row['member_email']); ?></small>
                                        </td>
                                        <td><?php echo escape($reg_row['class_name']); ?></td>
                                        <td>
                                            <?php echo escape($reg_row['class_date']); ?>
                                            <small class="text-muted"><?php echo escape(substr($reg_row['start_time'], 0, 5)); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-success"><?php echo escape($reg_row['attendance_status']); ?></span>
                                        </td>
                                        <td class="table-actions">
                                            <a href="<?php echo escape(BASE_URL); ?>/pages/registration_form.php?action=cancel&amp;id=<?php echo escape($reg_row['id']); ?>" 
                                               class="btn btn-sm btn-danger delete-btn" data-confirm="Are you sure you want to cancel this registration?">Cancel</a>
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
