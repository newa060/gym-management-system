<?php
/**
 * Membership Plans Management Page.
 * CRUD interface for gym membership plans (durations, pricing).
 */

$page_title = 'Membership Plans';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/membership.php';

$pdo_connection = getDbConnection();
$membership_model = new Membership($pdo_connection);

$status_message = '';
$status_type = '';

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $plan_id = (int)$_GET['id'];
    try {
        if ($membership_model->deletePlan($plan_id)) {
            $status_message = "Membership plan deleted successfully.";
            $status_type = "success";
        }
    } catch (PDOException $exception) {
        $status_message = "Cannot delete plan: it is assigned to existing memberships.";
        $status_type = "error";
    }
}

// Handle Form Submission (Add or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id       = !empty($_POST['plan_id']) ? (int)$_POST['plan_id'] : null;
    $plan_name     = sanitize_input($_POST['plan_name'] ?? '');
    $duration_days = (int)($_POST['duration_days'] ?? 0);
    $price         = (float)($_POST['price'] ?? 0.0);

    if (empty($plan_name) || $duration_days <= 0 || $price < 0) {
        $status_message = "Please provide a valid plan name, positive duration in days, and valid price.";
        $status_type = "error";
    } else {
        $plan_data = [
            'plan_name'     => $plan_name,
            'duration_days' => $duration_days,
            'price'         => $price,
        ];

        try {
            if ($plan_id) {
                $membership_model->updatePlan($plan_id, $plan_data);
                $status_message = "Membership plan updated successfully.";
                $status_type = "success";
            } else {
                $membership_model->createPlan($plan_data);
                $status_message = "New membership plan created successfully.";
                $status_type = "success";
            }
        } catch (PDOException $exception) {
            $status_message = "Database error: " . $exception->getMessage();
            $status_type = "error";
        }
    }
}

// Fetch plan if edit mode requested
$edit_plan = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_plan = $membership_model->getPlanById((int)$_GET['id']);
}

$all_plans = $membership_model->getAllPlans();
?>

<div class="page-title-row">
    <h1>Membership Plans</h1>
</div>

<?php if (!empty($status_message)): ?>
    <div class="alert alert-<?php echo escape($status_type); ?>">
        <?php echo escape($status_message); ?>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Plan Form Card -->
    <div class="col-form">
        <div class="card">
            <div class="card-header">
                <h2><?php echo $edit_plan ? 'Edit Plan' : 'Add New Plan'; ?></h2>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo escape(BASE_URL); ?>/pages/plans.php" class="validated-form">
                    <?php if ($edit_plan): ?>
                        <input type="hidden" name="plan_id" value="<?php echo escape($edit_plan['id']); ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="plan_name">Plan Name *</label>
                        <input type="text" id="plan_name" name="plan_name" required class="form-control"
                               value="<?php echo escape($edit_plan['plan_name'] ?? ''); ?>" placeholder="e.g. Monthly Standard">
                    </div>

                    <div class="form-group">
                        <label for="duration_days">Duration (Days) *</label>
                        <input type="number" id="duration_days" name="duration_days" min="1" required class="form-control"
                               value="<?php echo escape($edit_plan['duration_days'] ?? '30'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="price">Price ($) *</label>
                        <input type="number" step="0.01" min="0" id="price" name="price" required class="form-control"
                               value="<?php echo escape($edit_plan['price'] ?? '0.00'); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_plan ? 'Update Plan' : 'Create Plan'; ?>
                    </button>
                    <?php if ($edit_plan): ?>
                        <a href="<?php echo escape(BASE_URL); ?>/pages/plans.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Plans Table Card -->
    <div class="col-table">
        <div class="card">
            <div class="card-header">
                <h2>Available Plans</h2>
            </div>
            <div class="card-body">
                <?php if (empty($all_plans)): ?>
                    <p class="text-muted">No membership plans created yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Plan Name</th>
                                    <th>Duration</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_plans as $plan_row): ?>
                                    <tr>
                                        <td><strong><?php echo escape($plan_row['plan_name']); ?></strong></td>
                                        <td><?php echo escape($plan_row['duration_days']); ?> days</td>
                                        <td>$<?php echo escape(number_format((float)$plan_row['price'], 2)); ?></td>
                                        <td class="table-actions">
                                            <a href="<?php echo escape(BASE_URL); ?>/pages/plans.php?action=edit&amp;id=<?php echo escape($plan_row['id']); ?>" 
                                               class="btn btn-sm btn-secondary">Edit</a>
                                            <a href="<?php echo escape(BASE_URL); ?>/pages/plans.php?action=delete&amp;id=<?php echo escape($plan_row['id']); ?>" 
                                               class="btn btn-sm btn-danger delete-btn" data-confirm="Are you sure you want to delete this plan?">Delete</a>
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
