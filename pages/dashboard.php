<?php
/**
 * Dashboard Page.
 * Displays key summary statistics: active members count, expiring memberships, and today's classes.
 */

$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/member.php';
require_once __DIR__ . '/../models/membership.php';
require_once __DIR__ . '/../models/gym_class.php';

$pdo_connection = getDbConnection();

$member_model = new Member($pdo_connection);
$membership_model = new Membership($pdo_connection);
$class_model = new GymClass($pdo_connection);

// Automatically synchronize any expired plans based on today's date
$membership_model->syncExpiredMemberships();

$active_members_count = $member_model->getActiveCount();
$expiring_memberships = $membership_model->getExpiringSoon(7);
$today_classes = $class_model->getTodayClasses();
$gym_title = get_setting($pdo_connection, 'gym_name', 'Gym Management System');
?>

<div class="dashboard-header">
    <h1>Welcome to <?php echo escape($gym_title); ?></h1>
    <p class="subtitle">Overview and daily activity tracker</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo escape($active_members_count); ?></div>
        <div class="stat-label">Active Members</div>
    </div>
    <div class="stat-card stat-warning">
        <div class="stat-number"><?php echo count($expiring_memberships); ?></div>
        <div class="stat-label">Expiring in 7 Days</div>
    </div>
    <div class="stat-card stat-info">
        <div class="stat-number"><?php echo count($today_classes); ?></div>
        <div class="stat-label">Classes Scheduled Today</div>
    </div>
</div>

<div class="dashboard-sections">
    <section class="card mb-4">
        <div class="card-header">
            <h2>Today's Classes (<?php echo date('Y-m-d'); ?>)</h2>
            <a href="<?php echo escape(BASE_URL); ?>/pages/schedule.php" class="btn btn-sm btn-secondary">View Schedule</a>
        </div>
        <div class="card-body">
            <?php if (empty($today_classes)): ?>
                <p class="text-muted">No classes scheduled for today.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Instructor</th>
                            <th>Time</th>
                            <th>Room</th>
                            <th>Capacity</th>
                            <th>Enrolled</th>
                            <th>Remaining Seats</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($today_classes as $class_row): ?>
                            <tr>
                                <td><strong><?php echo escape($class_row['class_name']); ?></strong></td>
                                <td><?php echo escape($class_row['instructor_name']); ?></td>
                                <td><?php echo escape(substr($class_row['start_time'], 0, 5)); ?></td>
                                <td><?php echo escape($class_row['room']); ?></td>
                                <td><?php echo escape($class_row['capacity']); ?></td>
                                <td><?php echo escape($class_row['registered_count']); ?></td>
                                <td>
                                    <span class="badge <?php echo $class_row['seats_remaining'] > 0 ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo escape($class_row['seats_remaining']); ?> available
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <h2>Memberships Expiring Soon (Next 7 Days)</h2>
            <a href="<?php echo escape(BASE_URL); ?>/pages/members.php" class="btn btn-sm btn-secondary">Manage Members</a>
        </div>
        <div class="card-body">
            <?php if (empty($expiring_memberships)): ?>
                <p class="text-muted">No memberships are expiring within the next 7 days.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Email</th>
                            <th>Plan</th>
                            <th>End Date</th>
                            <th>Days Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expiring_memberships as $membership_row): ?>
                            <tr>
                                <td><?php echo escape($membership_row['first_name'] . ' ' . $membership_row['last_name']); ?></td>
                                <td><?php echo escape($membership_row['email']); ?></td>
                                <td><?php echo escape($membership_row['plan_name']); ?></td>
                                <td><?php echo escape($membership_row['end_date']); ?></td>
                                <td>
                                    <span class="badge badge-warning">
                                        <?php echo escape($membership_row['days_remaining']); ?> days left
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
