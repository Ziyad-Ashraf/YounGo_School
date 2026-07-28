<?php
    $course_details = $this->crud_model->get_course_by_id($param3)->row_array();
    $section_details = $this->crud_model->get_section('section', $param2)->row_array();
    $CI = &get_instance();
    $CI->load->model('Youngo_translation_model', 'youngo_translation_model');
    $youngo_section_english_translation = $CI->youngo_translation_model->get_section_translation_with_fallback((int) $param2, 'english');
    $youngo_section_arabic_translation = $CI->youngo_translation_model->get_section_translation((int) $param2, 'arabic');
    $youngo_section_english = !empty($youngo_section_english_translation) && is_array($youngo_section_english_translation) ? $youngo_section_english_translation : array();
    $youngo_section_arabic = !empty($youngo_section_arabic_translation) && is_array($youngo_section_arabic_translation) ? $youngo_section_arabic_translation : array();
    $youngo_section_english_title = isset($youngo_section_english['title']) && $youngo_section_english['title'] !== '' ? $youngo_section_english['title'] : $section_details['title'];
    $youngo_section_arabic_title = isset($youngo_section_arabic['title']) ? $youngo_section_arabic['title'] : '';
?>
<form action="<?php echo site_url('admin/sections/'.$param3.'/edit/'.$param2); ?>" method="post">
    <div class="border rounded p-3 mb-3">
        <h5 class="mb-3"><?php echo get_phrase('english'); ?></h5>
        <div class="form-group mb-0">
            <label for="english_section_title"><?php echo get_phrase('title'); ?><span class="required">*</span></label>
            <input class="form-control" type="text" name="english_title" id="english_section_title" value="<?php echo html_escape($youngo_section_english_title); ?>" required>
            <small class="text-muted"><?php echo get_phrase('provide_a_section_name'); ?></small>
        </div>
    </div>

    <div class="border rounded p-3 mb-3">
        <h5 class="mb-3"><?php echo get_phrase('arabic'); ?></h5>
        <div class="form-group mb-0">
            <label for="arabic_section_title"><?php echo get_phrase('title'); ?></label>
            <input class="form-control" type="text" name="arabic_title" id="arabic_section_title" value="<?php echo html_escape($youngo_section_arabic_title); ?>" dir="rtl">
        </div>
    </div>

    <!-- <div class="form-group mb-3">
        <label><?php echo get_phrase('Date of study plan'); ?> <small class="text-muted">(<?php echo get_phrase('Optional'); ?>)</small></label>
        <input type="text" name="date_range_of_study_plan" class="form-control date date-range-with-time" data-toggle="date-picker" data-time-picker="true" data-locale="{'format': 'DD/MM hh:mm A'}">

    </div>

    <div class="form-group mb-3">
        <label><?php echo get_phrase('Restriction of study plan'); ?></label>

        <br>
        <input type="radio" id="is_restricted_no" value="" name="restricted_by" <?php if(!$section_details['restricted_by']) echo 'checked'; ?>> <label for="is_restricted_no"><?php echo get_phrase('No restriction'); ?></label>

        <br>
        <input type="radio" id="is_restricted_start_date" value="start_date" name="restricted_by" <?php if($section_details['restricted_by'] == 'start_date') echo 'checked'; ?>> <label for="is_restricted_start_date"><?php echo get_phrase('Until the start date, keep this section locked'); ?></label>

        <br>
        <input type="radio" id="is_restricted_date_range" value="date_range" name="restricted_by" <?php if($section_details['restricted_by'] == 'date_range') echo 'checked'; ?>> <label for="is_restricted_date_range"><?php echo get_phrase('Keep this section open only within the selected date range'); ?></label>

    </div> -->

    <div class="text-right">
        <button class = "btn btn-success" type="submit" name="button"><?php echo get_phrase('submit'); ?></button>
    </div>
</form>


<!-- <script type="text/javascript">
    $(function() {
        'use strict';
        $('.date-range-with-time').daterangepicker({
            timePicker: true,
            startDate: '<?php echo date('m/d/y H:i:s', $section_details['start_date']); ?>',
            endDate: '<?php echo date('m/d/y H:i:s', $section_details['end_date']); ?>',
            locale: {
                format: 'MM/DD/YYYY hh:mm A'
            }
        });
    });
</script> -->

<style type="text/css">
    .calendar-time select{
        color: #787878 !important;
    }
</style>
