<?php
$users = isset($users) && is_array($users) ? $users : array();
$capability_status = isset($capability_status) && is_array($capability_status) ? $capability_status : array();

if (!function_exists('youngo_role_assignment_badge')) {
    function youngo_role_assignment_badge($enabled, $label)
    {
        $class = $enabled ? 'badge-success-lighten' : 'badge-secondary-lighten';
        return '<span class="badge ' . $class . '">' . html_escape($label) . '</span>';
    }
}

if (!function_exists('youngo_role_assignment_user_name')) {
    function youngo_role_assignment_user_name($user)
    {
        $name = trim((string) (isset($user['first_name']) ? $user['first_name'] : '') . ' ' . (string) (isset($user['last_name']) ? $user['last_name'] : ''));
        return $name !== '' ? $name : (isset($user['email']) ? $user['email'] : 'User #' . (int) $user['id']);
    }
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title">
                    <i class="mdi mdi-account-switch title_icon"></i> <?php echo html_escape($page_title); ?>
                </h4>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info" role="alert">
    Role assignments bridge YounGo roles with the legacy admin permission keys needed for the current CMS. Root Admin is protected and cannot be changed here.
</div>

<?php if (in_array(false, $capability_status, true)): ?>
<div class="alert alert-warning" role="alert">
    One or more expected YounGo capabilities are missing. Updates that require a missing capability will be rejected by the server.
</div>
<?php endif; ?>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('users'); ?></h4>
                <div class="table-responsive-sm">
                    <table id="basic-datatable" class="table table-striped table-centered mb-0">
                        <thead>
                            <tr>
                                <th><?php echo get_phrase('user'); ?></th>
                                <th><?php echo get_phrase('system_role_/_role_toggles'); ?></th>
                                <th><?php echo get_phrase('legacy_bridge'); ?></th>
                                <th><?php echo get_phrase('action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <?php
                                $user_id = (int) $user['id'];
                                $is_root = !empty($user['is_protected_root']);
                                $avatar_url = $this->user_model->get_user_image_url($user_id);
                                $legacy_permissions = isset($user['legacy_permissions']) && is_array($user['legacy_permissions']) ? $user['legacy_permissions'] : array();
                                $form_id = 'youngo-role-form-' . $user_id;
                            ?>
                            <tr class="youngo-role-assignment-row<?php echo $is_root ? ' table-warning' : ''; ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo html_escape($avatar_url); ?>" alt="" class="rounded-circle mr-2" width="42" height="42">
                                        <div>
                                            <strong><?php echo html_escape(youngo_role_assignment_user_name($user)); ?></strong>
                                            <?php if ($is_root): ?>
                                                <span class="badge badge-warning-lighten ml-1"><?php echo get_phrase('protected_root_admin'); ?></span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted"><?php echo html_escape($user['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <form id="<?php echo $form_id; ?>" method="post" action="<?php echo site_url('admin/youngo/role-assignments/update'); ?>" class="youngo-role-assignment-form">
                                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                        <div class="d-flex flex-wrap align-items-center">
                                            <div class="mr-3 mb-2">
                                                <input type="checkbox" id="root-role-<?php echo $user_id; ?>" data-switch="bool" <?php echo $is_root ? 'checked' : ''; ?> disabled>
                                                <label for="root-role-<?php echo $user_id; ?>" data-on-label="Root" data-off-label="Root"></label>
                                                <small class="d-block text-muted"><?php echo get_phrase('root_admin'); ?></small>
                                            </div>
                                            <div class="mr-3 mb-2">
                                                <input type="checkbox" id="admin-role-<?php echo $user_id; ?>" name="roles[]" value="admin" class="youngo-role-toggle youngo-admin-role-toggle" data-role-toggle="admin" data-switch="bool" <?php echo !empty($user['toggle_admin']) ? 'checked' : ''; ?> <?php echo $is_root ? 'disabled' : ''; ?>>
                                                <label for="admin-role-<?php echo $user_id; ?>" data-on-label="On" data-off-label="Off"></label>
                                                <small class="d-block text-muted"><?php echo get_phrase('admin'); ?></small>
                                            </div>
                                            <div class="mr-3 mb-2">
                                                <input type="checkbox" id="content-role-<?php echo $user_id; ?>" name="roles[]" value="content" class="youngo-role-toggle youngo-granular-role-toggle" data-role-toggle="content" data-switch="bool" <?php echo !empty($user['toggle_content']) ? 'checked' : ''; ?> <?php echo $is_root ? 'disabled' : ''; ?>>
                                                <label for="content-role-<?php echo $user_id; ?>" data-on-label="On" data-off-label="Off"></label>
                                                <small class="d-block text-muted"><?php echo get_phrase('content'); ?></small>
                                            </div>
                                            <div class="mr-3 mb-2">
                                                <input type="checkbox" id="course-role-<?php echo $user_id; ?>" name="roles[]" value="course" class="youngo-role-toggle youngo-granular-role-toggle" data-role-toggle="course" data-switch="bool" <?php echo !empty($user['toggle_course']) ? 'checked' : ''; ?> <?php echo $is_root ? 'disabled' : ''; ?>>
                                                <label for="course-role-<?php echo $user_id; ?>" data-on-label="On" data-off-label="Off"></label>
                                                <small class="d-block text-muted"><?php echo get_phrase('course'); ?></small>
                                            </div>
                                            <div class="mr-3 mb-2">
                                                <input type="checkbox" id="instructor-role-<?php echo $user_id; ?>" name="roles[]" value="instructor" class="youngo-role-toggle youngo-granular-role-toggle" data-role-toggle="instructor" data-switch="bool" <?php echo !empty($user['toggle_instructor']) ? 'checked' : ''; ?> <?php echo $is_root ? 'disabled' : ''; ?>>
                                                <label for="instructor-role-<?php echo $user_id; ?>" data-on-label="On" data-off-label="Off"></label>
                                                <small class="d-block text-muted"><?php echo get_phrase('instructor'); ?></small>
                                            </div>
                                        </div>
                                        <?php if ($is_root): ?>
                                            <small class="text-muted">Protected root account. Role assignment updates are disabled server-side and in the UI.</small>
                                        <?php else: ?>
                                            <small class="text-muted">Admin is mutually exclusive. Content, Course, and Instructor can be combined.</small>
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <td>
                                    <?php echo youngo_role_assignment_badge((int) $user['role_id'] === 1, 'Admin area role_id=' . (int) $user['role_id']); ?>
                                    <?php echo youngo_role_assignment_badge((int) $user['is_instructor'] === 1, 'Instructor flag'); ?>
                                    <br>
                                    <small class="text-muted">
                                        Legacy permissions:
                                        <?php echo !empty($legacy_permissions) ? html_escape(implode(', ', $legacy_permissions)) : 'none'; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (!$is_root): ?>
                                        <button type="submit" form="<?php echo $form_id; ?>" class="btn btn-sm btn-outline-primary"><?php echo get_phrase('save'); ?></button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled><?php echo get_phrase('protected'); ?></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No users were found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
"use strict";
(function() {
    var forms = document.querySelectorAll('.youngo-role-assignment-form');
    forms.forEach(function(form) {
        var adminToggle = form.querySelector('[data-role-toggle="admin"]');
        var granularToggles = form.querySelectorAll('.youngo-granular-role-toggle');
        if (!adminToggle) {
            return;
        }

        adminToggle.addEventListener('change', function() {
            if (adminToggle.checked) {
                granularToggles.forEach(function(toggle) {
                    toggle.checked = false;
                });
            }
        });

        granularToggles.forEach(function(toggle) {
            toggle.addEventListener('change', function() {
                if (toggle.checked) {
                    adminToggle.checked = false;
                }
            });
        });
    });
})();
</script>
