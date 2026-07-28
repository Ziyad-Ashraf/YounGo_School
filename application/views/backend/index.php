<?php
    $system_name = $this->db->get_where('settings' , array('key'=>'system_name'))->row()->value;
    $system_title = $this->db->get_where('settings' , array('key'=>'system_title'))->row()->value;
    $user_details = $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();
    $logged_in_user_role = strtolower($this->session->userdata('role'));

    if (file_exists(APPPATH . 'helpers/youngo_admin_language_helper.php')) {
        $this->load->helper('youngo_admin_language');
    }
    $admin_active_language = function_exists('youngo_admin_active_language') ? youngo_admin_active_language() : 'english';
    $admin_html_lang = function_exists('youngo_admin_html_lang') ? youngo_admin_html_lang($admin_active_language) : 'en';
    $admin_html_dir = function_exists('youngo_admin_html_dir') ? youngo_admin_html_dir($admin_active_language) : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?php echo html_escape($admin_html_lang); ?>" dir="<?php echo html_escape($admin_html_dir); ?>">
<head>
    <title><?php echo get_phrase($page_title); ?> | <?php echo $system_title; ?></title>
    <!-- all the meta tags -->
    <?php include 'metas.php'; ?>
    <!-- all the css files -->
    <?php include 'includes_top.php'; ?>
</head>
<body data-layout="detached" class="youngo-admin-dir-<?php echo html_escape($admin_html_dir); ?>">
    <!-- HEADER -->
    <?php include 'header.php'; ?>
    <div class="container-fluid">
        <div class="wrapper">
            <!-- BEGIN CONTENT -->
            <!-- SIDEBAR -->
            <?php include $logged_in_user_role.'/'.'navigation.php' ?>
            <!-- PAGE CONTAINER-->
            <div class="content-page">
                <div class="content">
                    <!-- BEGIN PlACE PAGE CONTENT HERE -->
                    <?php include $logged_in_user_role.'/'.$page_name.'.php';?>
                    <!-- END PLACE PAGE CONTENT HERE -->
                </div>
            </div>
            <!-- END CONTENT -->
        </div>
    </div>
    <!-- all the js files -->
    <?php include 'includes_bottom.php'; ?>
    <?php include 'modal.php'; ?>
    <?php include 'common_scripts.php'; ?>
</body>
</html>
