<?php
/**
 * Member Management Page.
 * Handles listing, searching, creating, editing, plan purchasing, and plan renewal.
 */

$page_title = 'Member Management';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/member.php';
require_once __DIR__ . '/../models/membership.php';

$pdo_connection = getDbConnection();
$member_model = new Member($pdo_connection);
$membership_model = new Membership($pdo_connection);

// Synchronize expired memberships against today's date
$membership_model->syncExpiredMemberships();

$status_message = '';
$status_type = '';

// Handle Plan Renewal / Purchase Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'renew_plan') {
    $renew_member_id = (int)($_POST['renew_member_id'] ?? 0);
    $plan_id         = (int)($_POST['plan_id'] ?? 0);
    $start_date      = sanitize_input($_POST['start_date'] ?? date('Y-m-d'));

    if ($renew_member_id <= 0 || $plan_id <= 0) {
        $status_message = "Please select a valid member and membership plan.";
        $status_type = "error";
    } else {
        try {
            $membership_model->assignPlan($renew_member_id, $plan_id, $start_date);
            $status_message = "Membership plan successfully assigned/renewed!";
            $status_type = "success";
        } catch (Exception $exception) {
            $status_message = "Error assigning plan: " . $exception->getMessage();
            $status_type = "error";
        }
    }
}

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    if ($member_model->delete($delete_id)) {
        $status_message = "Member successfully deleted.";
        $status_type = "success";
    } else {
        $status_message = "Failed to delete member.";
        $status_type = "error";
    }
}

// Handle Form Submission (Add or Update Member Profile)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action_type']) || $_POST['action_type'] === 'save_member')) {
    $member_id     = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null;
    $first_name    = sanitize_input($_POST['first_name'] ?? '');
    $last_name     = sanitize_input($_POST['last_name'] ?? '');
    $email         = sanitize_input($_POST['email'] ?? '');
    $phone         = sanitize_input($_POST['phone'] ?? '');
    $date_of_birth = sanitize_input($_POST['date_of_birth'] ?? '');
    $status_id     = (int)($_POST['status_id'] ?? 1);
    $joined_on     = sanitize_input($_POST['joined_on'] ?? date('Y-m-d'));
    $initial_plan  = !empty($_POST['initial_plan_id']) ? (int)$_POST['initial_plan_id'] : null;

    if (empty($first_name) || empty($last_name) || empty($email) || !validate_email($email)) {
        $status_message = "Please provide valid member details (valid email is required).";
        $status_type = "error";
    } else {
        $member_data = [
            'first_name'    => $first_name,
            'last_name'     => $last_name,
            'email'         => $email,
            'phone'         => $phone,
            'date_of_birth' => $date_of_birth,
            'status_id'     => $status_id,
            'joined_on'     => $joined_on,
        ];

        try {
            if ($member_id) {
                $member_model->update($member_id, $member_data);
                $status_message = "Member details updated successfully.";
                $status_type = "success";
            } else {
                $new_id = $member_model->create($member_data);
                
                // If an initial plan was selected during registration, activate it immediately
                if ($initial_plan) {
                    $membership_model->assignPlan($new_id, $initial_plan, $joined_on);
                    $status_message = "New member created and membership plan activated successfully!";
                } else {
                    $status_message = "New member created successfully.";
                }
                $status_type = "success";
            }
        } catch (PDOException $exception) {
            $status_message = "Database error: " . $exception->getMessage();
            $status_type = "error";
        } catch (Exception $exception) {
            $status_message = "Error: " . $exception->getMessage();
            $status_type = "error";
        }
    }
}

// Fetch member if edit mode is active
$edit_member = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_member = $member_model->getById((int)$_GET['id']);
}

// Fetch member if renew mode is active
$renew_member = null;
if (isset($_GET['action']) && $_GET['action'] === 'renew' && isset($_GET['id'])) {
    $renew_member = $member_model->getById((int)$_GET['id']);
}

// Search and list members
$search_query = sanitize_input($_GET['search'] ?? '');
$member_list  = $member_model->getAll($search_query);
$statuses     = $member_model->getAllStatuses();
$all_plans    = $membership_model->getAllPlans();
?>

<div class="page-title-row">
    <h1>Member Management</h1>
</div>

<?php if (!empty($status_message)): ?>
    <div class="alert alert-<?php echo escape($status_type); ?>">
        <?php echo escape($status_message); ?>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Form Column -->
    <div class="col-form">
        <?php if ($renew_member): ?>
            <!-- Renew / Purchase Plan Card -->
            <div class="card">
                <div class="card-header">
                    <h2>Renew / Assign Plan</h2>
                </div>
                <div class="card-body">
                    <p style="margin-bottom: 1rem;">
                        Member: <strong><?php echo escape($renew_member['first_name'] . ' ' . $renew_member['last_name']); ?></strong><br>
                        <small class="text-muted"><?php echo escape($renew_member['email']); ?></small>
                    </p>
                    <form method="POST" action="<?php echo escape(BASE_URL); ?>/pages/members.php" class="validated-form">
                        <input type="hidden" name="action_type" value="renew_plan">
                        <input type="hidden" name="renew_member_id" value="<?php echo escape($renew_member['id']); ?>">

                        <div class="form-group">
                            <label for="renew_plan_id">Select Plan *</label>
                            <select id="renew_plan_id" name="plan_id" required class="form-control">
                                <option value="">-- Choose Membership Plan --</option>
                                <?php foreach ($all_plans as $plan): ?>
                                    <option value="<?php echo escape($plan['id']); ?>">
                                        <?php echo escape($plan['plan_name'] . ' - $' . number_format((float)$plan['price'], 2) . ' (' . $plan['duration_days'] . ' days)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="renew_start_date">Start Date *</label>
                            <input type="date" id="renew_start_date" name="start_date" required class="form-control"
                                   value="<?php echo date('Y-m-d'); ?>">
                            <small class="text-muted">The expiration date will be automatically computed based on plan duration.</small>
                        </div>

                        <button type="submit" class="btn btn-primary">Activate / Renew Plan</button>
                        <a href="<?php echo escape(BASE_URL); ?>/pages/members.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Member Add / Edit Card -->
            <div class="card">
                <div class="card-header">
                    <h2><?php echo $edit_member ? 'Edit Member' : 'Add New Member'; ?></h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo escape(BASE_URL); ?>/pages/members.php" class="validated-form">
                        <input type="hidden" name="action_type" value="save_member">
                        <?php if ($edit_member): ?>
                            <input type="hidden" name="member_id" value="<?php echo escape($edit_member['id']); ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required class="form-control"
                                   value="<?php echo escape($edit_member['first_name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required class="form-control"
                                   value="<?php echo escape($edit_member['last_name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required class="form-control"
                                   value="<?php echo escape($edit_member['email'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" class="form-control"
                                   value="<?php echo escape($edit_member['phone'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                                   value="<?php echo escape($edit_member['date_of_birth'] ?? ''); ?>">
                        </div>

                        <?php if (!$edit_member): ?>
                            <!-- Initial Plan Purchase for New Members -->
                            <div class="form-group">
                                <label for="initial_plan_id">Select Membership Plan</label>
                                <select id="initial_plan_id" name="initial_plan_id" class="form-control">
                                    <option value="">-- No Plan (Register Profile Only) --</option>
                                    <?php foreach ($all_plans as $plan): ?>
                                        <option value="<?php echo escape($plan['id']); ?>">
                                            <?php echo escape($plan['plan_name'] . ' - $' . number_format((float)$plan['price'], 2) . ' (' . $plan['duration_days'] . ' days)'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Selecting a plan activates their membership immediately.</small>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="status_id">Membership Status</label>
                            <select id="status_id" name="status_id" class="form-control">
                                <?php foreach ($statuses as $status_row): ?>
                                    <option value="<?php echo escape($status_row['id']); ?>"
                                        <?php echo (isset($edit_member['status_id']) && $edit_member['status_id'] == $status_row['id']) ? 'selected' : ''; ?>>
                                        <?php echo escape($status_row['status_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="joined_on">Joined Date</label>
                            <input type="date" id="joined_on" name="joined_on" class="form-control"
                                   value="<?php echo escape($edit_member['joined_on'] ?? date('Y-m-d')); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?php echo $edit_member ? 'Update Member' : 'Save & Register Member'; ?>
                        </button>
                        <?php if ($edit_member): ?>
                            <a href="<?php echo escape(BASE_URL); ?>/pages/members.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Member Table Column -->
    <div class="col-table">
        <div class="card">
            <div class="card-header flex-header">
                <h2>Members List</h2>
                <form method="GET" action="<?php echo escape(BASE_URL); ?>/pages/members.php" class="search-form">
                    <input type="text" name="search" placeholder="Search by name, email, phone..." 
                           value="<?php echo escape($search_query); ?>" class="form-control form-control-sm">
                    <button type="submit" class="btn btn-sm btn-primary">Search</button>
                    <?php if (!empty($search_query)): ?>
                        <a href="<?php echo escape(BASE_URL); ?>/pages/members.php" class="btn btn-sm btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body">
                <?php if (empty($member_list)): ?>
                    <p class="text-muted">No members found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Active Plan</th>
                                    <th>Expires On</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($member_list as $member_row): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo escape($member_row['first_name'] . ' ' . $member_row['last_name']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo escape($member_row['email']); ?><br>
                                            <small class="text-muted"><?php echo escape($member_row['phone']); ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($member_row['plan_name'])): ?>
                                                <span class="badge <?php echo ($member_row['membership_status'] === 'Active') ? 'badge-success' : 'badge-danger'; ?>">
                                                    <?php echo escape($member_row['plan_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">No Plan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($member_row['membership_end'])): ?>
                                                <span><?php echo escape($member_row['membership_end']); ?></span>
                                                <?php if ($member_row['days_remaining'] !== null && $member_row['days_remaining'] >= 0): ?>
                                                    <div style="margin-top: 3px;"><span class="badge badge-warning"><?php echo escape($member_row['days_remaining']); ?> days left</span></div>
                                                <?php else: ?>
                                                    <div style="margin-top: 3px;"><span class="badge badge-danger">Expired</span></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $member_row['status_name'] === 'Active' ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo escape($member_row['status_name']); ?>
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <a href="<?php echo escape(BASE_URL); ?>/pages/members.php?action=renew&amp;id=<?php echo escape($member_row['id']); ?>" 
                                               class="btn btn-sm btn-primary" title="Assign or renew membership plan">Renew Plan</a>
                                            <a href="<?php echo escape(BASE_URL); ?>/pages/members.php?action=edit&amp;id=<?php echo escape($member_row['id']); ?>" 
                                               class="btn btn-sm btn-secondary">Edit</a>
                                            <a href="<?php echo escape(BASE_URL); ?>/pages/members.php?action=delete&amp;id=<?php echo escape($member_row['id']); ?>" 
                                               class="btn btn-sm btn-danger delete-btn" data-confirm="Are you sure you want to delete this member?">Delete</a>
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
