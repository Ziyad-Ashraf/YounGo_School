<?php
$grants = isset($grants) && is_array($grants) ? $grants : array();
$filters = isset($filters) && is_array($filters) ? $filters : array();
$users = isset($users) && is_array($users) ? $users : array();
$courses = isset($courses) && is_array($courses) ? $courses : array();
$plans = isset($plans) && is_array($plans) ? $plans : array();

if (!function_exists('youngo_manual_grant_date')) {
    function youngo_manual_grant_date($timestamp)
    {
        return !empty($timestamp) ? date('Y-m-d H:i', (int) $timestamp) : '-';
    }
}

if (!function_exists('youngo_manual_grant_user_label')) {
    function youngo_manual_grant_user_label($row, $prefix)
    {
        $name = trim((string) (isset($row[$prefix . '_first_name']) ? $row[$prefix . '_first_name'] : '') . ' ' . (string) (isset($row[$prefix . '_last_name']) ? $row[$prefix . '_last_name'] : ''));
        $email = isset($row[$prefix . '_email']) ? $row[$prefix . '_email'] : '';
        if ($name === '') {
            $name = $email !== '' ? $email : '-';
        }
        return $name . ($email !== '' && $email !== $name ? ' (' . $email . ')' : '');
    }
}

if (!function_exists('youngo_manual_grant_status_badge')) {
    function youngo_manual_grant_status_badge($status)
    {
        $status = trim((string) $status);
        $class = $status === 'active' ? 'badge-success-lighten' : ($status === 'revoked' ? 'badge-danger-lighten' : 'badge-secondary-lighten');
        return '<span class="badge ' . $class . '">' . html_escape(ucfirst($status !== '' ? $status : 'unknown')) . '</span>';
    }
}

if (!function_exists('youngo_manual_grant_target_label')) {
    function youngo_manual_grant_target_label($grant)
    {
        if ($grant['grant_type'] === 'course') {
            return !empty($grant['course_title']) ? $grant['course_title'] : ('Course #' . (int) $grant['course_id']);
        }

        if ($grant['grant_type'] === 'subscription') {
            $label = !empty($grant['plan_name']) ? $grant['plan_name'] : ('Plan #' . (int) $grant['plan_id']);
            return $label . (!empty($grant['plan_slug']) ? ' (' . $grant['plan_slug'] . ')' : '');
        }

        return '-';
    }
}

if (!function_exists('youngo_manual_grant_lifetime_label')) {
    function youngo_manual_grant_lifetime_label($grant)
    {
        if (!empty($grant['is_lifetime'])) {
            return 'Lifetime';
        }
        return !empty($grant['expiry_date']) ? 'Until ' . youngo_manual_grant_date($grant['expiry_date']) : '-';
    }
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title">
                    <i class="mdi mdi-account-key title_icon"></i> <?php echo html_escape($page_title); ?>
                    <a href="<?php echo site_url('admin/youngo/manual-grants/create'); ?>" class="btn btn-outline-primary btn-rounded alignToTitle">
                        <i class="mdi mdi-plus"></i> Add Manual Grant
                    </a>
                </h4>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info" role="alert">
    Manual grants create auditable YounGo access records only. They do not create checkout orders, payments, enrolment rows, coupons, or progress rows.
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('filters'); ?></h4>
                <form method="get" action="<?php echo site_url('admin/youngo/manual-grants'); ?>" class="mb-3">
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="grant_type"><?php echo get_phrase('type'); ?></label>
                            <select class="form-control" name="grant_type" id="grant_type">
                                <option value="">All</option>
                                <option value="course" <?php echo isset($filters['grant_type']) && $filters['grant_type'] === 'course' ? 'selected' : ''; ?>>Course</option>
                                <option value="subscription" <?php echo isset($filters['grant_type']) && $filters['grant_type'] === 'subscription' ? 'selected' : ''; ?>>Subscription</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="status"><?php echo get_phrase('status'); ?></label>
                            <select class="form-control" name="status" id="status">
                                <option value="">All</option>
                                <option value="active" <?php echo isset($filters['status']) && $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="revoked" <?php echo isset($filters['status']) && $filters['status'] === 'revoked' ? 'selected' : ''; ?>>Revoked</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="user_id"><?php echo get_phrase('receiver'); ?></label>
                            <select class="form-control" name="user_id" id="user_id">
                                <option value="">All users</option>
                                <?php foreach ($users as $user): ?>
                                    <?php $user_label = trim((string) $user['first_name'] . ' ' . (string) $user['last_name']); ?>
                                    <option value="<?php echo (int) $user['id']; ?>" <?php echo !empty($filters['user_id']) && (int) $filters['user_id'] === (int) $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo html_escape(($user_label !== '' ? $user_label : $user['email']) . ' (' . $user['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="course_id"><?php echo get_phrase('course'); ?></label>
                            <select class="form-control" name="course_id" id="course_id">
                                <option value="">All courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo (int) $course['id']; ?>" <?php echo !empty($filters['course_id']) && (int) $filters['course_id'] === (int) $course['id'] ? 'selected' : ''; ?>>
                                        <?php echo html_escape($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="plan_id"><?php echo get_phrase('plan'); ?></label>
                            <select class="form-control" name="plan_id" id="plan_id">
                                <option value="">All plans</option>
                                <?php foreach ($plans as $plan): ?>
                                    <option value="<?php echo (int) $plan['id']; ?>" <?php echo !empty($filters['plan_id']) && (int) $filters['plan_id'] === (int) $plan['id'] ? 'selected' : ''; ?>>
                                        <?php echo html_escape($plan['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary btn-block"><?php echo get_phrase('filter'); ?></button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive-sm">
                    <table id="basic-datatable" class="table table-striped table-centered mb-0">
                        <thead>
                            <tr>
                                <th><?php echo get_phrase('id'); ?></th>
                                <th><?php echo get_phrase('type'); ?></th>
                                <th><?php echo get_phrase('receiver'); ?></th>
                                <th><?php echo get_phrase('target'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                                <th><?php echo get_phrase('lifetime_/_expiry'); ?></th>
                                <th><?php echo get_phrase('granted_by'); ?></th>
                                <th><?php echo get_phrase('created'); ?></th>
                                <th><?php echo get_phrase('revoked'); ?></th>
                                <th><?php echo get_phrase('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grants as $grant): ?>
                            <?php $is_revoked = !empty($grant['revoked_at']) || $grant['status'] === 'revoked'; ?>
                            <tr>
                                <td><?php echo (int) $grant['id']; ?></td>
                                <td><?php echo html_escape(ucfirst($grant['grant_type'])); ?></td>
                                <td><?php echo html_escape(youngo_manual_grant_user_label($grant, 'receiver')); ?></td>
                                <td><?php echo html_escape(youngo_manual_grant_target_label($grant)); ?></td>
                                <td><?php echo youngo_manual_grant_status_badge($grant['status']); ?></td>
                                <td><?php echo html_escape(youngo_manual_grant_lifetime_label($grant)); ?></td>
                                <td><?php echo html_escape(youngo_manual_grant_user_label($grant, 'actor')); ?></td>
                                <td><?php echo youngo_manual_grant_date($grant['created_at']); ?></td>
                                <td><?php echo youngo_manual_grant_date($grant['revoked_at']); ?></td>
                                <td>
                                    <a href="<?php echo site_url('admin/youngo/manual-grants/' . (int) $grant['id']); ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    <?php if (!$is_revoked): ?>
                                        <form method="post" action="<?php echo site_url('admin/youngo/manual-grants/' . (int) $grant['id'] . '/revoke'); ?>" class="d-inline" onsubmit="return confirm('Revoke this manual grant? This will not delete progress, enrolment, payment, plan, or order rows.');">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><?php echo get_phrase('revoke'); ?></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($grants)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">No manual grants were found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
