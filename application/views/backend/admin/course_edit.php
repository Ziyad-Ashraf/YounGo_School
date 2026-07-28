<?php
$course_details = $this->crud_model->get_course_by_id($course_id)->row_array();
$can_manage_youngo_course_access = isset($can_manage_youngo_course_access) && $can_manage_youngo_course_access === true;
$youngo_access_mode = isset($course_details['youngo_access_mode']) ? $course_details['youngo_access_mode'] : 'subscription_only';
if (!in_array($youngo_access_mode, array('subscription_only', 'subscription_and_purchase', 'purchase_only'), true)) {
    $youngo_access_mode = 'subscription_only';
}
$youngo_purchase_access_type = isset($course_details['youngo_purchase_access_type']) ? $course_details['youngo_purchase_access_type'] : 'lifetime';
if ($youngo_purchase_access_type == 'time_limited') {
    $youngo_purchase_access_type = 'timed';
}
if (!in_array($youngo_purchase_access_type, array('lifetime', 'timed'), true)) {
    $youngo_purchase_access_type = 'lifetime';
}
$youngo_purchase_duration_days = isset($course_details['youngo_purchase_duration_days']) && (int) $course_details['youngo_purchase_duration_days'] > 0 ? (int) $course_details['youngo_purchase_duration_days'] : '';
$CI = &get_instance();
$CI->load->model('Youngo_translation_model', 'youngo_translation_model');
$youngo_course_english_translation = $CI->youngo_translation_model->get_course_translation_with_fallback((int) $course_id, 'english');
$youngo_course_arabic_translation = $CI->youngo_translation_model->get_course_translation((int) $course_id, 'arabic');
$youngo_course_english = !empty($youngo_course_english_translation) && is_array($youngo_course_english_translation) ? $youngo_course_english_translation : array();
$youngo_course_arabic = !empty($youngo_course_arabic_translation) && is_array($youngo_course_arabic_translation) ? $youngo_course_arabic_translation : array();
$youngo_course_english_title = isset($youngo_course_english['title']) && $youngo_course_english['title'] !== '' ? $youngo_course_english['title'] : $course_details['title'];
$youngo_course_english_slug = isset($youngo_course_english['slug']) ? $youngo_course_english['slug'] : '';
$youngo_course_arabic_title = isset($youngo_course_arabic['title']) ? $youngo_course_arabic['title'] : '';
$youngo_course_arabic_slug = isset($youngo_course_arabic['slug']) ? $youngo_course_arabic['slug'] : '';
$youngo_course_arabic_faqs = !empty($youngo_course_arabic['faqs']) ? json_decode($youngo_course_arabic['faqs'], true) : array();
$youngo_course_arabic_faqs = is_array($youngo_course_arabic_faqs) ? $youngo_course_arabic_faqs : array();
$youngo_course_arabic_requirements = !empty($youngo_course_arabic['requirements']) ? json_decode($youngo_course_arabic['requirements'], true) : array();
$youngo_course_arabic_requirements = is_array($youngo_course_arabic_requirements) ? $youngo_course_arabic_requirements : array();
$youngo_course_arabic_outcomes = !empty($youngo_course_arabic['outcomes']) ? json_decode($youngo_course_arabic['outcomes'], true) : array();
$youngo_course_arabic_outcomes = is_array($youngo_course_arabic_outcomes) ? $youngo_course_arabic_outcomes : array();
$youngo_course_english_faqs = !empty($youngo_course_english['faqs']) ? json_decode($youngo_course_english['faqs'], true) : array();
$youngo_course_english_faqs = is_array($youngo_course_english_faqs) ? $youngo_course_english_faqs : array();
$youngo_course_english_requirements = !empty($youngo_course_english['requirements']) ? json_decode($youngo_course_english['requirements'], true) : array();
$youngo_course_english_requirements = is_array($youngo_course_english_requirements) ? $youngo_course_english_requirements : array();
$youngo_course_english_outcomes = !empty($youngo_course_english['outcomes']) ? json_decode($youngo_course_english['outcomes'], true) : array();
$youngo_course_english_outcomes = is_array($youngo_course_english_outcomes) ? $youngo_course_english_outcomes : array();
?>
<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i> <?php echo get_phrase('update') . ': ' . $course_details['title']; ?></h4>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <!--ajax page loader-->
            <div class="ajax_loader w-100">
                <div class="ajax_loaderBar"></div>
            </div>
            <!--end ajax page loader-->
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="header-title my-1"><?php echo get_phrase('course_manager'); ?></h4>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo site_url('admin/preview/' . $course_id); ?>" class="alignToTitle btn btn-outline-secondary btn-rounded btn-sm ml-1 my-1" target="_blank"><?php echo get_phrase('view_on_frontend'); ?> <i class="mdi mdi-arrow-right"></i> </a>

                        <a href="<?php echo site_url('admin/courses'); ?>" class="alignToTitle btn btn-outline-secondary btn-rounded btn-sm my-1"> <i class=" mdi mdi-keyboard-backspace"></i> <?php echo get_phrase('back_to_course_list'); ?></a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <?php if (!empty($can_view_youngo_entitlement_summary) && !empty($youngo_course_entitlement_summary)): ?>
                            <?php $this->load->view('backend/admin/youngo_course_entitlement_summary'); ?>
                        <?php endif; ?>

                        <form class="required-form" action="<?php echo site_url('admin/course_actions/edit/' . $course_id); ?>" method="post" enctype="multipart/form-data" autocomplete="off">
                            <div class="scrollable-tab-section" id="basicwizard">

                                <button type="button" class="scrollable-tab-btn-left"><i class="mdi mdi-arrow-left"></i></button>

                                <div class="scrollable-tab">
                                    <ul class="nav nav-pills nav-justified form-wizard-header">
                                        <li class="nav-item">
                                            <a href="#curriculum" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                <i class="mdi mdi-account-circle"></i>
                                                <span class=""><?php echo get_phrase('curriculum'); ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#enrol_list" onclick="enrol_list('<?php echo $course_details['id']; ?>')" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                <i class="mdi mdi-chart-bar-stacked"></i>
                                                <span class=""><?php echo get_phrase('Enrol list'); ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#academic_progress" onclick="student_academic_progress('<?php echo $course_details['id']; ?>')" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                <i class="mdi mdi-chart-bar-stacked"></i>
                                                <span class=""><?php echo get_phrase('Academic progress'); ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#bbb-live-class" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                <i class="mdi mdi-video-account"></i>
                                                <span class=""><?php echo get_phrase('BBB live class'); ?></span>
                                            </a>
                                        </li>
                                        <?php if (addon_status('live-class')) : ?>
                                            <li class="nav-item">
                                                <a href="#live-class" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                    <i class="mdi mdi-video-account"></i>
                                                    <span class=""><?php echo get_phrase('zoom_live_class'); ?></span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (addon_status('jitsi-live-class')) : ?>
                                            <li class="nav-item jitsiLiveClassNavItem">
                                                <a href="#jitsi-live-class" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                    <i class="mdi mdi-video-account"></i>
                                                    <span class=""><?php echo get_phrase('jitsi_live_class'); ?></span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (addon_status('assignment')) : ?>
                                            <li class="nav-item">
                                                <a href="#assignment" onclick="load_assignment_list()" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                    <i class="dripicons-document"></i>
                                                    <span class=""><?php echo get_phrase('assignment'); ?></span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (addon_status('noticeboard')) : ?>
                                            <li class="nav-item">
                                                <a href="#noticeboard" onclick="load_notic_list()" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                    <i class="mdi mdi-clipboard-text-outline"></i>
                                                    <span class=""><?php echo get_phrase('noticeboard'); ?></span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (addon_status('course_analytics')) : ?>
                                            <li class="nav-item">
                                                <a href="#course_analytics" onclick="load_analytics_chart()" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                    <i class="mdi mdi-chart-bar"></i>
                                                    <span class=""><?php echo get_phrase('analytics'); ?></span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <li class="nav-item">
                                            <a href="#basic" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                <i class="mdi mdi-fountain-pen-tip"></i>
                                                <span class=""><?php echo get_phrase('basic'); ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#info" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                <i class="mdi mdi-information-outline"></i>
                                                <span class=""><?php echo get_phrase('info'); ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#pricing" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                <i class="mdi mdi-currency-cny"></i>
                                                <span class=""><?php echo get_phrase('pricing'); ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#media" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                <i class="mdi mdi-library-video"></i>
                                                <span class=""><?php echo get_phrase('media'); ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#seo" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                <i class="mdi mdi-tag-multiple"></i>
                                                <span class=""><?php echo get_phrase('seo'); ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#customField" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                <i class="mdi mdi-file-check"></i>
                                                <span class=""><?php echo get_phrase('Custom Field'); ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#finish" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                <i class="mdi mdi-checkbox-marked-circle-outline"></i>
                                                <span class=""><?php echo get_phrase('finish'); ?></span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <button type="button" class="scrollable-tab-btn-right"><i class="mdi mdi-arrow-right"></i></button>

                                <div class="tab-content b-0 mb-0">
                                    <div class="tab-pane" id="curriculum">
                                        <?php
                                        if ($course_details['course_type'] == 'general') :
                                            include 'curriculum.php';
                                        elseif ($course_details['course_type'] == 'scorm' && addon_status('scorm_course') == true) :
                                            include 'scorm_curriculum.php';
                                        elseif ($course_details['course_type'] == 'h5p' && addon_status('h5p') == true) :
                                            include 'h5p_curriculum.php';
                                        else : ?>
                                            <?php if ($course_details['course_type'] == 'scorm_course') : ?>
                                                <div class="row justify-content-center">
                                                    <div class="col-md-6">
                                                        <div class="alert alert-warning" role="alert">
                                                            <h4 class="alert-heading"><?= get_phrase('heads_up'); ?>!</h4>
                                                            <p><?= get_phrase('currently_the_scorm_course_addon_is_deactivate'); ?>. <?= get_phrase('please_activate_the_scorm_course_addon_to_use_it'); ?>.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($course_details['course_type'] == 'h5p') : ?>
                                                <div class="row justify-content-center">
                                                    <div class="col-md-6">
                                                        <div class="alert alert-warning" role="alert">
                                                            <h4 class="alert-heading"><?= get_phrase('heads_up'); ?>!</h4>
                                                            <p><?= get_phrase('currently_the_h5p_course_addon_is_deactivate'); ?>. <?= get_phrase('please_activate_the_h5p_course_addon_to_use_it'); ?>.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="tab-pane" id="enrol_list"></div>

                                    <div class="tab-pane" id="academic_progress"></div>

                                    <div class="tab-pane" id="bbb-live-class">
                                        <?php include "bbb_live_class.php"; ?>
                                    </div>

                                    <!-- LIVE CLASS CODE BASE -->
                                    <?php if (addon_status('live-class')) : ?>
                                        <?php include 'live_class.php'; ?>
                                    <?php endif; ?>

                                    <!-- Jitsi live class CODE BASE -->
                                    <?php if (addon_status('jitsi-live-class')) : ?>
                                        <div class="tab-pane" id="jitsi-live-class">
                                            <?php include 'jitsi_live_class.php'; ?>
                                        </div>
                                    <?php endif; ?>
                                    <!-- LIVE CLASS CODE BASE -->

                                    <!-- ASSIGNMENT CODE BASE -->
                                    <?php if (addon_status('assignment')) : ?>
                                        <div class="tab-pane" id="assignment">
                                            <?php include 'assignment.php'; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- NOTICEBOARD CODE BASE -->
                                    <?php if (addon_status('noticeboard')) : ?>
                                        <div class="tab-pane" id="noticeboard">
                                            <?php include 'noticeboard.php'; ?>
                                        </div>
                                    <?php endif; ?>
                                    <!-- NOTICEBOARD CODE BASE -->

                                    <!-- COURSE ANALYTICS CODE BASE -->
                                    <?php if (addon_status('course_analytics')) : ?>
                                        <div class="tab-pane" id="course_analytics">
                                            <?php include 'course_analytics.php'; ?>
                                        </div>
                                    <?php endif; ?>
                                    <!-- COURSE ANALYTICS CODE BASE -->

                                    <div class="tab-pane" id="basic">
                                        <div class="row justify-content-center">
                                            <div class="col-xl-8">
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="course_type"><?php echo get_phrase('course_type'); ?></label>
                                                    <div class="col-md-10">
                                                        <div class="alert alert-light" role="alert">
                                                            <h4 class="alert-heading"><?= get_phrase($course_details['course_type']); ?></h4>
                                                            <hr class="m-1">
                                                            <p class="mb-0"><?= get_phrase('the_course_type_can_not_be_editable'); ?>.</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="existing_instructors"><?php echo get_phrase('instructor_of_this_course'); ?></label>
                                                    <div class="col-md-10">
                                                        <?php if ($course_details['multi_instructor']) :
                                                            $instructor_ids = explode(',', $course_details['user_id']);
                                                        ?>
                                                            <?php foreach ($instructor_ids as $instructor_id) :
                                                            ?>
                                                                <?php $instructor_details = $this->user_model->get_instructor($instructor_id)->row_array(); ?>
                                                                <div class="m-2">
                                                                    <img class="rounded-circle" src="<?php echo $this->user_model->get_user_image_url($instructor_details['id']);; ?>" height="30px" alt="">
                                                                    <span style="font-weight: 700; font-size: 15px; vertical-align: sub; margin-left: 6px;">
                                                                        <?php echo html_escape($instructor_details['first_name'] . ' ' . $instructor_details['last_name']); ?>
                                                                    </span>
                                                                    <?php if (count($instructor_ids) > 1 && $course_details['creator'] != $instructor_id) : ?>
                                                                        <a class="btn text-danger mt-1" href="javascript:void(0)" onclick="confirm_modal('<?php echo site_url('admin/remove_an_instructor/' . $course_details['id'] . '/' . $instructor_details['id']); ?>');"> <i class="mdi mdi-delete"></i> <?php echo get_phrase('Remove'); ?></a>
                                                                    <?php else : ?>
                                                                        <a class="btn text-danger mt-1" href="javascript:void(0)" onclick="showAjaxModal('<?php echo site_url('admin/change_course_author/' . $course_details['id']); ?>', '<?php echo get_phrase('Change Course Author') ?>')"> <i class="mdi mdi-pencil"></i> <?php echo get_phrase('Change Course author'); ?></a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else : ?>
                                                            <?php $instructor_details = $this->user_model->get_instructor($course_details['user_id'])->row_array(); ?>
                                                            <div>
                                                                <img class="rounded-circle" src="<?php echo $this->user_model->get_user_image_url($instructor_details['id']);; ?>" height="30px" alt="">
                                                                <span style="font-weight: 700; font-size: 15px; vertical-align: sub; margin-left: 6px;">
                                                                    <?php echo html_escape($instructor_details['first_name'] . ' ' . $instructor_details['last_name']); ?>
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="new_instructor"><?php echo get_phrase('add_new_instructor'); ?></label>
                                                    <div class="col-md-10">
                                                        <select class="select2 form-control select2-multiple" data-toggle="select2" multiple="multiple" data-placeholder="<?php echo get_phrase('choose_...'); ?>" name="new_instructors[]">
                                                            <?php $instructors = $this->user_model->get_instructor()->result_array(); ?>
                                                            <?php foreach ($instructors as $key => $instructor) : ?>
                                                                <option value="<?php echo html_escape($instructor['id']); ?>"><?php echo html_escape($instructor['first_name'] . ' ' . $instructor['last_name']); ?> ( <?php echo html_escape($instructor['email']); ?> )</option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="border rounded p-3 mb-3">
                                                    <h5 class="mb-3"><?php echo get_phrase('bilingual_course_content'); ?></h5>
                                                    <ul class="nav nav-tabs mb-3" role="tablist">
                                                        <li class="nav-item">
                                                            <a class="nav-link active" data-toggle="tab" href="#course-edit-english-content" role="tab"><?php echo get_phrase('english'); ?></a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link" data-toggle="tab" href="#course-edit-arabic-content" role="tab"><?php echo get_phrase('arabic'); ?></a>
                                                        </li>
                                                    </ul>
                                                    <div class="tab-content">
                                                        <div class="tab-pane active" id="course-edit-english-content" role="tabpanel">
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="english_course_title"><?php echo get_phrase('course_title'); ?><span class="required">*</span></label>
                                                                <div class="col-md-10">
                                                                    <input type="text" class="form-control" id="english_course_title" name="english_title" placeholder="<?php echo get_phrase('enter_course_title'); ?>" value="<?php echo html_escape($youngo_course_english_title); ?>" required>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="english_course_slug"><?php echo get_phrase('english_slug'); ?></label>
                                                                <div class="col-md-10">
                                                                    <input type="text" class="form-control" id="english_course_slug" name="english_slug" value="<?php echo html_escape($youngo_course_english_slug); ?>" placeholder="<?php echo get_phrase('generated_from_english_title_if_blank'); ?>">
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="english_short_description"><?php echo get_phrase('short_description'); ?></label>
                                                                <div class="col-md-10">
                                                                    <textarea name="english_short_description" id="english_short_description" class="form-control"><?php echo isset($youngo_course_english['short_description']) ? html_escape($youngo_course_english['short_description']) : html_escape($course_details['short_description']); ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="description"><?php echo get_phrase('description'); ?></label>
                                                                <div class="col-md-10">
                                                                    <textarea name="english_description" id="description" class="form-control"><?php echo isset($youngo_course_english['description']) ? $youngo_course_english['description'] : $course_details['description']; ?></textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="tab-pane" id="course-edit-arabic-content" role="tabpanel">
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="arabic_course_title"><?php echo get_phrase('arabic_title'); ?></label>
                                                                <div class="col-md-10">
                                                                    <input type="text" class="form-control" id="arabic_course_title" name="arabic_title" value="<?php echo html_escape($youngo_course_arabic_title); ?>" dir="rtl">
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="arabic_course_slug"><?php echo get_phrase('arabic_slug'); ?></label>
                                                                <div class="col-md-10">
                                                                    <input type="text" class="form-control" id="arabic_course_slug" name="arabic_slug" value="<?php echo html_escape($youngo_course_arabic_slug); ?>" dir="rtl" placeholder="<?php echo get_phrase('generated_from_arabic_title_if_blank'); ?>">
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="arabic_short_description"><?php echo get_phrase('arabic_short_description'); ?></label>
                                                                <div class="col-md-10">
                                                                    <textarea name="arabic_short_description" id="arabic_short_description" class="form-control" dir="rtl"><?php echo isset($youngo_course_arabic['short_description']) ? html_escape($youngo_course_arabic['short_description']) : ''; ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="arabic_description"><?php echo get_phrase('arabic_description'); ?></label>
                                                                <div class="col-md-10">
                                                                    <textarea name="arabic_description" id="arabic_description" class="form-control" dir="rtl"><?php echo isset($youngo_course_arabic['description']) ? $youngo_course_arabic['description'] : ''; ?></textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="sub_category_id"><?php echo get_phrase('category'); ?><span class="required">*</span></label>
                                                    <div class="col-md-10">
                                                        <select class="form-control select2" data-toggle="select2" name="sub_category_id" id="sub_category_id" required>
                                                            <option value=""><?php echo get_phrase('select_a_category'); ?></option>
                                                            <?php foreach ($categories->result_array() as $category) : ?>
                                                                <optgroup label="<?php echo $category['name']; ?>">
                                                                    <?php $sub_categories = $this->crud_model->get_sub_categories($category['id']);
                                                                    foreach ($sub_categories as $sub_category) : ?>
                                                                        <option value="<?php echo $sub_category['id']; ?>" <?php if ($sub_category['id'] == $course_details['sub_category_id']) echo 'selected'; ?>><?php echo $sub_category['name']; ?></option>
                                                                    <?php endforeach; ?>
                                                                </optgroup>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <small class="text-muted"><?php echo get_phrase('select_sub_category'); ?></small>
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="level"><?php echo get_phrase('level'); ?></label>
                                                    <div class="col-md-10">
                                                        <select class="form-control select2" data-toggle="select2" name="level" id="level">
                                                            <option value="beginner" <?php if ($course_details['level'] == "beginner") echo 'selected'; ?>><?php echo get_phrase('beginner'); ?></option>
                                                            <option value="advanced" <?php if ($course_details['level'] == "advanced") echo 'selected'; ?>><?php echo get_phrase('advanced'); ?></option>
                                                            <option value="intermediate" <?php if ($course_details['level'] == "intermediate") echo 'selected'; ?>><?php echo get_phrase('intermediate'); ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="language_made_in"><?php echo get_phrase('language_made_in'); ?></label>
                                                    <div class="col-md-10">
                                                        <select class="form-control select2" data-toggle="select2" name="language_made_in" id="language_made_in">
                                                            <?php $course_language_marker = isset($course_details['language']) ? $course_details['language'] : 'english'; ?>
                                                            <option value="english" <?php if ($course_language_marker == 'english') echo 'selected'; ?>>English</option>
                                                            <option value="arabic" <?php if ($course_language_marker == 'arabic') echo 'selected'; ?>>Arabic</option>
                                                            <option value="arabic_translated" <?php if ($course_language_marker == 'arabic_translated') echo 'selected'; ?>>Arabic translated</option>
                                                        </select>
                                                        <small class="text-muted">Course content marker only. This does not control site language or bilingual translation rows.</small>
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="enable_drip_content"><?php echo get_phrase('enable_drip_content'); ?></label>
                                                    <div class="col-md-10 pt-2">
                                                        <input type="checkbox" name="enable_drip_content" value="1" id="enable_drip_content" data-switch="primary" <?php if ($course_details['enable_drip_content'] == 1) echo 'checked'; ?>>
                                                        <label for="enable_drip_content" data-on-label="On" data-off-label="Off"></label>
                                                    </div>
                                                </div>

                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label pt-1" for="enable_drip_content"><?php echo get_phrase('Updated as a'); ?></label>
                                                    <div class="col-md-10 pt-1">
                                                        <div class="custom-control custom-radio mb-1">
                                                            <input type="radio" id="status_active" name="status" class="custom-control-input" value="active" <?php echo $course_details['status'] == 'active' ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label" for="status_active"><?php echo get_phrase('Active course'); ?></label>
                                                        </div>

                                                        <div class="custom-control custom-radio mb-1">
                                                            <input type="radio" id="status_private" name="status" class="custom-control-input" value="private" <?php echo $course_details['status'] == 'private' ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label" for="status_private"><?php echo get_phrase('Private course'); ?></label>
                                                        </div>

                                                        <div id="upcoming" class="custom-control custom-radio mb-1">
                                                            <input type="radio" id="status_upcoming" name="status" class="custom-control-input" value="upcoming" <?php echo $course_details['status'] == 'upcoming' ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label" for="status_upcoming"><?php echo get_phrase('Upcoming course'); ?></label>
                                                        </div>

                                                         <!-- New Upcoming Image -->
                                                        <div class="form-group mt-3" id = "thumbnail-picker-area">
                                                            <div class="input-group">
                                                                <div class="custom-file">
                                                                    <input type="file" class="custom-file-input" id="upcoming_image_thumbnail" name="upcoming_image_thumbnail" value="<?php echo $course_details['upcoming_image_thumbnail']; ?>" >
                                                                    <input type="hidden"  name="old_upcoming_image_thumbnail" value="<?php echo $course_details['upcoming_image_thumbnail']; ?>" >
                                                                    <label class="custom-file-label" for="upcoming_image_thumbnail"><?php echo get_phrase('upcoming_image_thumbnail'); ?></label>
                                                                </div>
                                                            </div>
                                                            <small>(<?php echo get_phrase('the_image_size_should_be'); ?>: 365 X 460)</small>
                                                        </div>
                                                    <!-- New Upcoming Image -->
                                                    <div class="form-group mb-3" id="publish_date">
                                                        <label class="col-form-label" for="input_publish_date"><?php echo get_phrase('publish_date'); ?> <span class="required">*</span> </label>
                                                            <input type="datetime-local" class="form-control" id="input_publish_date" name = "publish_date" placeholder="<?php echo get_phrase('enter_publish_date'); ?>" value="<?php echo $course_details['publish_date'];?>">
                                                    </div>

                                                    </div>
                                                </div>

                                                <div class="form-group row mb-3">
                                                    <div class="offset-md-2 col-md-10">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" name="is_top_course" id="is_top_course" value="1" <?php if ($course_details['is_top_course'] == 1) echo 'checked'; ?>>
                                                            <label class="custom-control-label" for="is_top_course"><?php echo get_phrase('check_if_this_course_is_top_course'); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div> <!-- end col -->
                                        </div> <!-- end row -->
                                    </div> <!-- end tab pane -->

                                    <div class="tab-pane" id="info">
                                        <div class="row justify-content-center">
                                            <div class="col-xl-8">
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="faq"><?php echo get_phrase('course_faq'); ?></label>
                                                    <div class="col-md-10">
                                                        <div id="faq_area">
                                                            <?php $faq_counter = 0; ?>
                                                            <?php $course_faqs_arr = $youngo_course_english_faqs; ?>
                                                            <?php $course_faqs_arr = is_array($course_faqs_arr) ? $course_faqs_arr : array(); ?>
                                                            <?php foreach ($course_faqs_arr as $faq_title => $faq_description) : ?>
                                                                <div class="d-flex mt-2">
                                                                    <div class="flex-grow-1 px-3">
                                                                        <div class="form-group">
                                                                            <input type="text" class="form-control" value="<?php echo $faq_title; ?>" name="english_faqs[]" id="faqs" placeholder="<?php echo get_phrase('faq_question'); ?>">
                                                                            <textarea name="english_faq_descriptions[]" class="form-control mt-2" placeholder="<?php echo get_phrase('answer'); ?>"><?php echo $faq_description; ?></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="">
                                                                        <?php if ($faq_counter == 0) : ?>
                                                                            <button type="button" class="btn btn-success btn-sm" style="" name="button" onclick="appendFaq()"> <i class="fa fa-plus"></i> </button>
                                                                        <?php else : ?>
                                                                            <button type="button" class="btn btn-danger btn-sm" style="margin-top: 0px;" name="button" onclick="removeFaq(this)"> <i class="fa fa-minus"></i> </button>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <?php $faq_counter++; ?>
                                                            <?php endforeach; ?>

                                                            <?php if ($faq_counter == 0) : ?>
                                                                <div class="d-flex mt-2">
                                                                    <div class="flex-grow-1 px-3">
                                                                        <div class="form-group">
                                                                            <input type="text" class="form-control" name="english_faqs[]" id="faqs" placeholder="<?php echo get_phrase('faq_question'); ?>">
                                                                            <textarea name="english_faq_descriptions[]" class="form-control mt-2" placeholder="<?php echo get_phrase('answer'); ?>"></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="">
                                                                        <button type="button" class="btn btn-success btn-sm" style="" name="button" onclick="appendFaq()"> <i class="fa fa-plus"></i> </button>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>

                                                            <div id="blank_faq_field">
                                                                <div class="d-flex mt-2">
                                                                    <div class="flex-grow-1 px-3">
                                                                        <div class="form-group">
                                                                            <input type="text" class="form-control" name="english_faqs[]" id="faqs" placeholder="<?php echo get_phrase('faq_question'); ?>">
                                                                            <textarea name="english_faq_descriptions[]" class="form-control mt-2" placeholder="<?php echo get_phrase('answer'); ?>"></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="">
                                                                        <button type="button" class="btn btn-danger btn-sm" style="margin-top: 0px;" name="button" onclick="removeFaq(this)"> <i class="fa fa-minus"></i> </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group row mb-3 pt-2">
                                                    <label class="col-md-2 col-form-label" for="requirements"><?php echo get_phrase('requirements'); ?></label>
                                                    <div class="col-md-10">
                                                        <div id="requirement_area">
                                                            <?php if (count($youngo_course_english_requirements) > 0) : ?>
                                                                <?php
                                                                $counter = 0;
                                                                foreach ($youngo_course_english_requirements as $requirement) : ?>
                                                                    <?php if ($counter == 0) :
                                                                        $counter++; ?>
                                                                        <div class="d-flex mt-2">
                                                                            <div class="flex-grow-1 px-3">
                                                                                <div class="form-group">
                                                                                    <input type="text" class="form-control" name="english_requirements[]" id="requirements" placeholder="<?php echo get_phrase('provide_requirements'); ?>" value="<?php echo $requirement; ?>">
                                                                                </div>
                                                                            </div>
                                                                            <div class="">
                                                                                <button type="button" class="btn btn-success btn-sm" style="" name="button" onclick="appendRequirement()"> <i class="fa fa-plus"></i> </button>
                                                                            </div>
                                                                        </div>
                                                                    <?php else : ?>
                                                                        <div class="d-flex mt-2">
                                                                            <div class="flex-grow-1 px-3">
                                                                                <div class="form-group">
                                                                                    <input type="text" class="form-control" name="english_requirements[]" id="requirements" placeholder="<?php echo get_phrase('provide_requirements'); ?>" value="<?php echo $requirement; ?>">
                                                                                </div>
                                                                            </div>
                                                                            <div class="">
                                                                                <button type="button" class="btn btn-danger btn-sm" style="margin-top: 0px;" name="button" onclick="removeRequirement(this)"> <i class="fa fa-minus"></i> </button>
                                                                            </div>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            <?php else : ?>
                                                                <div class="d-flex mt-2">
                                                                    <div class="flex-grow-1 px-3">
                                                                        <div class="form-group">
                                                                            <input type="text" class="form-control" name="english_requirements[]" id="requirements" placeholder="<?php echo get_phrase('provide_requirements'); ?>">
                                                                        </div>
                                                                    </div>
                                                                    <div class="">
                                                                        <button type="button" class="btn btn-success btn-sm" style="" name="button" onclick="appendRequirement()"> <i class="fa fa-plus"></i> </button>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>

                                                            <div id="blank_requirement_field">
                                                                <div class="d-flex mt-2">
                                                                    <div class="flex-grow-1 px-3">
                                                                        <div class="form-group">
                                                                            <input type="text" class="form-control" name="english_requirements[]" id="requirements" placeholder="<?php echo get_phrase('provide_requirements'); ?>">
                                                                        </div>
                                                                    </div>
                                                                    <div class="">
                                                                        <button type="button" class="btn btn-danger btn-sm" style="margin-top: 0px;" name="button" onclick="removeRequirement(this)"> <i class="fa fa-minus"></i> </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group row mb-3 pt-2">
                                                    <label class="col-md-2 col-form-label" for="outcomes"><?php echo get_phrase('outcomes'); ?></label>
                                                    <div class="col-md-10">
                                                        <div id="outcomes_area">
                                                            <?php if (count($youngo_course_english_outcomes) > 0) : ?>
                                                                <?php
                                                                $counter = 0;
                                                                foreach ($youngo_course_english_outcomes as $outcome) : ?>
                                                                    <?php if ($counter == 0) :
                                                                        $counter++; ?>
                                                                        <div class="d-flex mt-2">
                                                                            <div class="flex-grow-1 px-3">
                                                                                <div class="form-group">
                                                                                    <input type="text" class="form-control" name="english_outcomes[]" placeholder="<?php echo get_phrase('provide_outcomes'); ?>" value="<?php echo $outcome; ?>">
                                                                                </div>
                                                                            </div>
                                                                            <div class="">
                                                                                <button type="button" class="btn btn-success btn-sm" name="button" onclick="appendOutcome()"> <i class="fa fa-plus"></i> </button>
                                                                            </div>
                                                                        </div>
                                                                    <?php else : ?>
                                                                        <div class="d-flex mt-2">
                                                                            <div class="flex-grow-1 px-3">
                                                                                <div class="form-group">
                                                                                    <input type="text" class="form-control" name="english_outcomes[]" placeholder="<?php echo get_phrase('provide_outcomes'); ?>" value="<?php echo $outcome; ?>">
                                                                                </div>
                                                                            </div>
                                                                            <div class="">
                                                                                <button type="button" class="btn btn-danger btn-sm" style="margin-top: 0px;" name="button" onclick="removeOutcome(this)"> <i class="fa fa-minus"></i> </button>
                                                                            </div>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            <?php else : ?>
                                                                <div class="d-flex mt-2">
                                                                    <div class="flex-grow-1 px-3">
                                                                        <div class="form-group">
                                                                            <input type="text" class="form-control" name="english_outcomes[]" placeholder="<?php echo get_phrase('provide_outcomes'); ?>">
                                                                        </div>
                                                                    </div>
                                                                    <div class="">
                                                                        <button type="button" class="btn btn-success btn-sm" name="button" onclick="appendOutcome()"> <i class="fa fa-plus"></i> </button>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div id="blank_outcome_field">
                                                                <div class="d-flex mt-2">
                                                                    <div class="flex-grow-1 px-3">
                                                                        <div class="form-group">
                                                                            <input type="text" class="form-control" name="english_outcomes[]" id="outcomes" placeholder="<?php echo get_phrase('provide_outcomes'); ?>">
                                                                        </div>
                                                                    </div>
                                                                    <div class="">
                                                                        <button type="button" class="btn btn-danger btn-sm" style="margin-top: 0px;" name="button" onclick="removeOutcome(this)"> <i class="fa fa-minus"></i> </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="border rounded p-3 mb-3">
                                                    <h5 class="mb-3">Arabic course info (optional)</h5>
                                                    <div class="form-group row mb-3">
                                                        <label class="col-md-2 col-form-label" for="arabic_faqs"><?php echo get_phrase('arabic_faq'); ?></label>
                                                        <div class="col-md-10">
                                                            <?php if (!empty($youngo_course_arabic_faqs)) : ?>
                                                                <?php foreach ($youngo_course_arabic_faqs as $arabic_faq_title => $arabic_faq_description) : ?>
                                                                    <input type="text" class="form-control mt-2" name="arabic_faqs[]" id="arabic_faqs" value="<?php echo html_escape($arabic_faq_title); ?>" dir="rtl" placeholder="<?php echo get_phrase('arabic_faq_question'); ?>">
                                                                    <textarea name="arabic_faq_descriptions[]" class="form-control mt-2" dir="rtl" placeholder="<?php echo get_phrase('arabic_answer'); ?>"><?php echo html_escape($arabic_faq_description); ?></textarea>
                                                                <?php endforeach; ?>
                                                            <?php else : ?>
                                                                <input type="text" class="form-control" name="arabic_faqs[]" id="arabic_faqs" dir="rtl" placeholder="<?php echo get_phrase('arabic_faq_question'); ?>">
                                                                <textarea name="arabic_faq_descriptions[]" class="form-control mt-2" dir="rtl" placeholder="<?php echo get_phrase('arabic_answer'); ?>"></textarea>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label class="col-md-2 col-form-label" for="arabic_requirements"><?php echo get_phrase('arabic_requirements'); ?></label>
                                                        <div class="col-md-10">
                                                            <?php if (!empty($youngo_course_arabic_requirements)) : ?>
                                                                <?php foreach ($youngo_course_arabic_requirements as $arabic_requirement) : ?>
                                                                    <input type="text" class="form-control mt-2" name="arabic_requirements[]" id="arabic_requirements" value="<?php echo html_escape($arabic_requirement); ?>" dir="rtl">
                                                                <?php endforeach; ?>
                                                            <?php else : ?>
                                                                <input type="text" class="form-control" name="arabic_requirements[]" id="arabic_requirements" dir="rtl">
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label class="col-md-2 col-form-label" for="arabic_outcomes"><?php echo get_phrase('arabic_outcomes'); ?></label>
                                                        <div class="col-md-10">
                                                            <?php if (!empty($youngo_course_arabic_outcomes)) : ?>
                                                                <?php foreach ($youngo_course_arabic_outcomes as $arabic_outcome) : ?>
                                                                    <input type="text" class="form-control mt-2" name="arabic_outcomes[]" id="arabic_outcomes" value="<?php echo html_escape($arabic_outcome); ?>" dir="rtl">
                                                                <?php endforeach; ?>
                                                            <?php else : ?>
                                                                <input type="text" class="form-control" name="arabic_outcomes[]" id="arabic_outcomes" dir="rtl">
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="pricing">
                                        <div class="row justify-content-center">
                                            <div class="col-xl-8">
                                                <div class="form-group row mb-3">
                                                    <div class="offset-md-2 col-md-10">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" name="is_free_course" id="is_free_course" value="1" <?php if ($course_details['is_free_course'] == 1) echo 'checked'; ?> onclick="togglePriceFields(this.id)">
                                                            <label class="custom-control-label" for="is_free_course"><?php echo get_phrase('check_if_this_is_a_free_course'); ?></label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="youngo-subscription-only-price-note alert alert-info">
                                                    This course is available through subscription plans only. One-time purchase price is not used.
                                                </div>
                                                <div class="paid-course-stuffs">
                                                    <div class="form-group row mb-3">
                                                        <label class="col-md-2 col-form-label" for="price"><?php echo get_phrase('course_price') . ' (' . currency_code_and_symbol() . ')'; ?></label>
                                                        <div class="col-md-10">
                                                            <input type="number" class="form-control" id="price" name="price" min="0" placeholder="<?php echo get_phrase('enter_course_course_price'); ?>" value="<?php echo $course_details['price']; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="form-group row mb-3">
                                                        <div class="offset-md-2 col-md-10">
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" name="discount_flag" id="discount_flag" value="1" <?php if ($course_details['discount_flag'] == 1) echo 'checked'; ?>>
                                                                <label class="custom-control-label" for="discount_flag"><?php echo get_phrase('check_if_this_course_has_discount'); ?></label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group row mb-3">
                                                        <label class="col-md-2 col-form-label" for="discounted_price"><?php echo get_phrase('discounted_price') . ' (' . currency_code_and_symbol() . ')'; ?></label>
                                                        <div class="col-md-10">
                                                            <input type="number" class="form-control" name="discounted_price" id="discounted_price" onkeyup="calculateDiscountPercentage(this.value)" value="<?php echo $course_details['discounted_price']; ?>" min="0">
                                                            <small class="text-muted"><?php echo get_phrase('this_course_has'); ?> <span id="discounted_percentage" class="text-danger">0%</span> <?php echo get_phrase('discount'); ?></small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label"><?php echo get_phrase('Expiry period'); ?></label>
                                                    <div class="col-md-10 pt-2 d-flex">
                                                        <div class="custom-control custom-radio mr-2">
                                                            <input type="radio" id="lifetime_expiry_period" name="expiry_period" class="custom-control-input" value="lifetime" onchange="checkExpiryPeriod(this)" <?php if ($course_details['expiry_period'] == 0) echo 'checked'; ?>>
                                                            <label class="custom-control-label" for="lifetime_expiry_period"><?php echo get_phrase('Lifetime'); ?></label>
                                                        </div>
                                                        <div class="custom-control custom-radio">
                                                            <input type="radio" id="limited_expiry_period" name="expiry_period" class="custom-control-input" value="limited_time" onchange="checkExpiryPeriod(this)" <?php if ($course_details['expiry_period'] > 0) echo 'checked'; ?>>
                                                            <label class="custom-control-label" for="limited_expiry_period"><?php echo get_phrase('Limited time'); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3" id="number_of_month" style="<?php if ($course_details['expiry_period'] == '') echo 'display: none'; ?>">
                                                    <label class="col-md-2 col-form-label"><?php echo get_phrase('Number of month'); ?></label>
                                                    <div class="col-md-10">
                                                        <input class="form-control" type="number" name="number_of_month" min="1" value="<?php echo $course_details['expiry_period']; ?>">
                                                        <small class="badge badge-light"><?php echo get_phrase('After purchase, students can access the course until your selected time.'); ?></small>
                                                    </div>
                                                </div>
                                                <?php if ($can_manage_youngo_course_access === true) : ?>
                                                    <hr>
                                                    <input type="hidden" name="youngo_access_settings_submitted" value="1">
                                                    <div class="form-group row mb-3">
                                                        <label class="col-md-2 col-form-label" for="youngo_access_mode"><?php echo get_phrase('access_and_purchase_settings'); ?></label>
                                                        <div class="col-md-10">
                                                            <div class="border rounded p-3">
                                                                <p class="text-muted mb-3">
                                                                    Controls whether the course is included in YounGo subscriptions and whether it may be purchased individually. This setting does not create checkout, payment, subscription, or access records.
                                                                </p>
                                                                <div class="form-group">
                                                                    <label for="youngo_access_mode"><?php echo get_phrase('access_mode'); ?></label>
                                                                    <select class="form-control" name="youngo_access_mode" id="youngo_access_mode">
                                                                        <option value="subscription_only" <?php if ($youngo_access_mode == 'subscription_only') echo 'selected'; ?>>Subscription only</option>
                                                                        <option value="subscription_and_purchase" <?php if ($youngo_access_mode == 'subscription_and_purchase') echo 'selected'; ?>>Subscription and individual purchase</option>
                                                                        <option value="purchase_only" <?php if ($youngo_access_mode == 'purchase_only') echo 'selected'; ?>>Individual purchase only</option>
                                                                    </select>
                                                                </div>

                                                                <div class="youngo-purchase-access-fields">
                                                                    <p class="text-muted mb-3">
                                                                        Disable legacy free-course mode and provide a valid course price before saving a purchase-enabled access mode.
                                                                    </p>
                                                                    <div class="form-group">
                                                                        <label for="youngo_purchase_access_type"><?php echo get_phrase('purchase_access_type'); ?></label>
                                                                        <select class="form-control" name="youngo_purchase_access_type" id="youngo_purchase_access_type">
                                                                            <option value="lifetime" <?php if ($youngo_purchase_access_type == 'lifetime') echo 'selected'; ?>>Lifetime</option>
                                                                            <option value="timed" <?php if ($youngo_purchase_access_type == 'timed') echo 'selected'; ?>>Timed</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group mb-0" id="youngo_purchase_duration_days_group">
                                                                        <label for="youngo_purchase_duration_days"><?php echo get_phrase('purchase_duration_in_days'); ?></label>
                                                                        <input type="number" class="form-control" name="youngo_purchase_duration_days" id="youngo_purchase_duration_days" min="1" value="<?php echo $youngo_purchase_duration_days; ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div> <!-- end col -->
                                        </div> <!-- end row -->
                                    </div> <!-- end tab-pane -->
                                    <div class="tab-pane" id="media">
                                        <div class="row justify-content-center">

                                            <div class="col-xl-8">
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="course_overview_provider"><?php echo get_phrase('course_overview_provider'); ?></label>
                                                    <div class="col-md-10">
                                                        <select class="form-control select2" data-toggle="select2" name="course_overview_provider" id="course_overview_provider">
                                                            <option value="youtube" <?php if ($course_details['course_overview_provider'] == 'youtube') echo 'selected'; ?>><?php echo get_phrase('youtube'); ?></option>
                                                            <option value="vimeo" <?php if ($course_details['course_overview_provider'] == 'vimeo') echo 'selected'; ?>><?php echo get_phrase('vimeo'); ?></option>
                                                            <option value="html5" <?php if ($course_details['course_overview_provider'] == 'html5') echo 'selected'; ?>><?php echo get_phrase('HTML5'); ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div> <!-- end col -->

                                            <div class="col-xl-8">
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="course_overview_url"><?php echo get_phrase('course_overview_url'); ?></label>
                                                    <div class="col-md-10">
                                                        <input type="text" class="form-control" name="course_overview_url" id="course_overview_url" placeholder="E.g: https://www.youtube.com/watch?v=oBtf8Yglw2w" value="<?php echo $course_details['video_url'] ?>">
                                                    </div>
                                                </div>
                                            </div> <!-- end col -->

                                            <!-- Course media content edit file starts -->
                                            <?php include 'course_media_edit.php'; ?>
                                            <!-- Course media content edit file ends -->
                                        </div> <!-- end row -->
                                    </div>
                                    <div class="tab-pane" id="seo">
                                        <div class="row justify-content-center">
                                            <div class="col-xl-8">
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="website_keywords"><?php echo get_phrase('meta_keywords'); ?></label>
                                                    <div class="col-md-10">
                                                        <input type="text" class="form-control bootstrap-tag-input" id="meta_keywords" name="english_meta_keywords" data-role="tagsinput" style="width: 100%;" value="<?php echo isset($youngo_course_english['meta_keywords']) ? html_escape($youngo_course_english['meta_keywords']) : html_escape($course_details['meta_keywords']); ?>" placeholder="<?php echo get_phrase('write_a_keyword_and_then_press_enter_button'); ?>" . />
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="english_seo_title"><?php echo get_phrase('english_seo_title'); ?></label>
                                                    <div class="col-md-10">
                                                        <input type="text" class="form-control" id="english_seo_title" name="english_seo_title" value="<?php echo isset($youngo_course_english['seo_title']) ? html_escape($youngo_course_english['seo_title']) : ''; ?>">
                                                    </div>
                                                </div>
                                            </div> <!-- end col -->
                                            <div class="col-xl-8">
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="meta_description"><?php echo get_phrase('meta_description'); ?></label>
                                                    <div class="col-md-10">
                                                        <textarea name="english_meta_description" class="form-control"><?php echo isset($youngo_course_english['meta_description']) ? html_escape($youngo_course_english['meta_description']) : html_escape($course_details['meta_description']); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="border rounded p-3 mb-3">
                                                    <h5 class="mb-3">Arabic SEO (optional)</h5>
                                                    <div class="form-group row mb-3">
                                                        <label class="col-md-2 col-form-label" for="arabic_seo_title"><?php echo get_phrase('arabic_seo_title'); ?></label>
                                                        <div class="col-md-10">
                                                            <input type="text" class="form-control" id="arabic_seo_title" name="arabic_seo_title" value="<?php echo isset($youngo_course_arabic['seo_title']) ? html_escape($youngo_course_arabic['seo_title']) : ''; ?>" dir="rtl">
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label class="col-md-2 col-form-label" for="arabic_meta_keywords"><?php echo get_phrase('arabic_meta_keywords'); ?></label>
                                                        <div class="col-md-10">
                                                            <input type="text" class="form-control" id="arabic_meta_keywords" name="arabic_meta_keywords" value="<?php echo isset($youngo_course_arabic['meta_keywords']) ? html_escape($youngo_course_arabic['meta_keywords']) : ''; ?>" dir="rtl">
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label class="col-md-2 col-form-label" for="arabic_meta_description"><?php echo get_phrase('arabic_meta_description'); ?></label>
                                                        <div class="col-md-10">
                                                            <textarea name="arabic_meta_description" id="arabic_meta_description" class="form-control" dir="rtl"><?php echo isset($youngo_course_arabic['meta_description']) ? html_escape($youngo_course_arabic['meta_description']) : ''; ?></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div> <!-- end col -->
                                        </div> <!-- end row -->
                                    </div>
                                    <!-- Custom Field -->
                                    <div class="tab-pane" id="customField">
                                        <?php include 'custom_field.php'; ?>
                                    </div>
                                    <!-- Custom Field -->

                                    <div class="tab-pane" id="finish">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="text-center">
                                                    <h2 class="mt-0"><i class="mdi mdi-check-all"></i></h2>
                                                    <h3 class="mt-0"><?php echo get_phrase('thank_you'); ?> !</h3>

                                                    <p class="w-75 mb-2 mx-auto"><?php echo get_phrase('you_are_just_one_click_away'); ?></p>

                                                    <div class="mb-3 mt-3">
                                                        <button type="button" class="btn btn-primary text-center" onclick="checkRequiredFields()"><?php echo get_phrase('submit'); ?></button>
                                                    </div>
                                                </div>
                                            </div> <!-- end col -->
                                        </div> <!-- end row -->
                                    </div>

                                    <ul class="list-inline mb-0 wizard text-center">
                                        <li class="previous list-inline-item">
                                            <a href="javascript:;" class="btn btn-info"> <i class="mdi mdi-arrow-left-bold"></i> </a>
                                        </li>
                                        <li class="next list-inline-item">
                                            <a href="javascript:;" class="btn btn-info"> <i class="mdi mdi-arrow-right-bold"></i> </a>
                                        </li>
                                    </ul>

                                </div> <!-- tab-content -->
                            </div> <!-- end #progressbarwizard-->
                        </form>
                    </div>
                </div><!-- end row-->
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        initSummerNote(['#description']);
        togglePriceFields('is_free_course');
    });
</script>

<script type="text/javascript">
    var blank_faq = jQuery('#blank_faq_field').html();
    var blank_outcome = jQuery('#blank_outcome_field').html();
    var blank_requirement = jQuery('#blank_requirement_field').html();
    jQuery(document).ready(function() {
        jQuery('#blank_faq_field').hide();
        jQuery('#blank_outcome_field').hide();
        jQuery('#blank_requirement_field').hide();
        calculateDiscountPercentage($('#discounted_price').val());
    });

    function appendFaq() {
        jQuery('#faq_area').append(blank_faq);
    }

    function removeFaq(faqElem) {
        jQuery(faqElem).parent().parent().remove();
    }

    function appendOutcome() {
        jQuery('#outcomes_area').append(blank_outcome);
    }

    function removeOutcome(outcomeElem) {
        jQuery(outcomeElem).parent().parent().remove();
    }

    function appendRequirement() {
        jQuery('#requirement_area').append(blank_requirement);
    }

    function removeRequirement(requirementElem) {
        jQuery(requirementElem).parent().parent().remove();
    }

    function ajax_get_sub_category(category_id) {
        $.ajax({
            url: '<?php echo site_url('admin/ajax_get_sub_category/'); ?>' + category_id,
            success: function(response) {
                jQuery('#sub_category_id').html(response);
            }
        });
    }

    function priceChecked(elem) {
        if (jQuery('#discountCheckbox').is(':checked')) {

            jQuery('#discountCheckbox').prop("checked", false);
        } else {

            jQuery('#discountCheckbox').prop("checked", true);
        }
    }

    function topCourseChecked(elem) {
        if (jQuery('#isTopCourseCheckbox').is(':checked')) {

            jQuery('#isTopCourseCheckbox').prop("checked", false);
        } else {

            jQuery('#isTopCourseCheckbox').prop("checked", true);
        }
    }

    function isFreeCourseChecked(elem) {

        if (jQuery('#' + elem.id).is(':checked')) {
            $('#price').prop('required', false);
        } else {
            $('#price').prop('required', true);
        }
    }

    function calculateDiscountPercentage(discounted_price) {
        if (discounted_price > 0) {
            var actualPrice = jQuery('#price').val();
            if (actualPrice > 0) {
                var reducedPrice = actualPrice - discounted_price;
                var discountedPercentage = (reducedPrice / actualPrice) * 100;
                if (discountedPercentage > 0) {
                    jQuery('#discounted_percentage').text(discountedPercentage.toFixed(2) + "%");

                } else {
                    jQuery('#discounted_percentage').text('<?php echo '0%'; ?>');
                }
            }
        }
    }

    function youngoToggleCourseAccessFields() {
        if (!jQuery('#youngo_access_mode').length) {
            return;
        }

        var accessMode = jQuery('#youngo_access_mode').val();
        var purchaseEnabled = accessMode == 'subscription_and_purchase' || accessMode == 'purchase_only';

        if (purchaseEnabled) {
            jQuery('.youngo-purchase-access-fields').slideDown();
            jQuery('.youngo-subscription-only-price-note').slideUp();
            if (!jQuery('#is_free_course').is(':checked')) {
                jQuery('.paid-course-stuffs').slideDown();
            }
            jQuery('#price, #discount_flag, #discounted_price').prop('disabled', false);
        } else {
            jQuery('.youngo-purchase-access-fields').slideUp();
            jQuery('.paid-course-stuffs').slideUp();
            jQuery('.youngo-subscription-only-price-note').slideDown();
            jQuery('#price, #discount_flag, #discounted_price').prop('disabled', true);
        }

        if (purchaseEnabled && jQuery('#youngo_purchase_access_type').val() == 'timed') {
            jQuery('#youngo_purchase_duration_days_group').slideDown();
        } else {
            jQuery('#youngo_purchase_duration_days_group').slideUp();
        }
    }

    jQuery(document).ready(function() {
        if (jQuery('#youngo_access_mode').length) {
            youngoToggleCourseAccessFields();
            jQuery('#youngo_access_mode, #youngo_purchase_access_type').on('change', youngoToggleCourseAccessFields);
        }
    });

    $('.on-hover-action').mouseenter(function() {
        var id = this.id;
        $('#widgets-of-' + id).show();
    });
    $('.on-hover-action').mouseleave(function() {
        var id = this.id;
        $('#widgets-of-' + id).hide();
    });

    function enrol_list(course_id) {
        var enrolList = $('#enrol_list').html();
        if (enrolList == '') {
            $('.ajax_loader').addClass('start_ajax_loading');
            $.ajax({
                url: '<?php echo site_url('admin/enrol_list/'); ?>' + course_id,
                success: function(response) {
                    $('#enrol_list').html(response);
                    $('.ajax_loader').removeClass('start_ajax_loading');
                }
            });
        }
    }


    function student_academic_progress(course_id) {
        var academicProgressContent = $('#academic_progress').html();
        if (academicProgressContent == '') {
            $('.ajax_loader').addClass('start_ajax_loading');
            $.ajax({
                url: '<?php echo site_url('admin/student_academic_progress/'); ?>' + course_id,
                success: function(response) {
                    $('#academic_progress').html(response);
                    $('.ajax_loader').removeClass('start_ajax_loading');
                }
            });
        }
    }


    //Show specific tab by passing the tab id when reload browser
    <?php if (isset($_GET['tab'])) : ?>
        $('.ajax_loader').addClass('start_ajax_loading');
        const tabClickInterval = setInterval(function() {
            if (!$("a[href$=<?= $_GET['tab']; ?>]").hasClass('active')) {
                $("a[href$=<?= $_GET['tab']; ?>]").click();
            } else {
                $('.ajax_loader').removeClass('start_ajax_loading');
                clearInterval(tabClickInterval);
            }
        }, 1000);
    <?php endif; ?>
</script>


<script type="text/javascript">
  
    $(document).ready(function() {
        $('#thumbnail-picker-area').hide();
        $('#publish_date').hide();
        $('#upcoming').click(function() {
            $('#thumbnail-picker-area').show();
            $('#publish_date').show();
        });

        $('input[type="radio"]').not('#status_upcoming').click(function() {
            $('#thumbnail-picker-area').hide();
            $('#publish_date').hide();
        });
        <?php if($course_details['status'] == 'upcoming'):?>
        $('#thumbnail-picker-area').show();
        $('#publish_date').show();
    <?php endif; ?>
    });
</script>
