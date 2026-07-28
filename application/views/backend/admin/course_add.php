<?php $can_manage_youngo_course_access = isset($can_manage_youngo_course_access) && $can_manage_youngo_course_access === true; ?>
<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i> <?php echo get_phrase('add_new_course'); ?></h4>
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
                        <h4 class="header-title my-1"><?php echo get_phrase('course_adding_form'); ?></h4>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo site_url('admin/courses'); ?>" class="alignToTitle btn btn-outline-secondary btn-rounded btn-sm my-1"> <i class=" mdi mdi-keyboard-backspace"></i> <?php echo get_phrase('back_to_course_list'); ?></a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <form class="required-form" action="<?php echo site_url('admin/course_actions/add'); ?>" method="post" enctype="multipart/form-data">
                            <div class="scrollable-tab-section" id="basicwizard">

                                <button type="button" class="scrollable-tab-btn-left" ><i class="mdi mdi-arrow-left"></i></button>

                                <div class="scrollable-tab">
                                    <ul class="nav nav-pills nav-justified form-wizard-header" style="flex-wrap: unset;">
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
                                            <a href="#finish" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                <i class="mdi mdi-checkbox-marked-circle-outline"></i>
                                                <span class=""><?php echo get_phrase('finish'); ?></span>
                                            </a>
                                        </li>
                                        <li class="w-100 bg-white pb-3">
                                             <!--ajax page loader-->
                                            
                                            <!--end ajax page loader-->
                                        </li>
                                    </ul>
                                    
                                </div>
                                <button type="button" class="scrollable-tab-btn-right" ><i class="mdi mdi-arrow-right"></i></button>


                                <div class="tab-content b-0 mb-0">
                                    <div class="tab-pane" id="basic">
                                        <div class="row justify-content-center">
                                            <div class="col-xl-8">
                                                <?php if(addon_status('scorm_course') || addon_status('h5p')): ?>
                                                    <div class="form-group row mb-3">
                                                        <label class="col-md-2 col-form-label" for="course_type"><?php echo get_phrase('course_type'); ?></label>
                                                        <div class="col-md-10">
                                                            <select class="form-control select2" data-toggle="select2" name="course_type" id="course_type">
                                                                <option value="general"><?php echo get_phrase('general'); ?></option>
                                                                <?php if(addon_status('scorm_course')){ ?>
                                                                    <option value="scorm"><?php echo get_phrase('scorm'); ?></option>
                                                                 <?php }?>
                                                                <?php if(addon_status('h5p')){?>
                                                                    <option value="h5p"><?php echo get_phrase('H5P');?>
                                                                 <?php }?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <input type="hidden" name = "course_type" value="general">
                                                <?php endif; ?>
                                                <div class="border rounded p-3 mb-3">
                                                    <h5 class="mb-3"><?php echo get_phrase('bilingual_course_content'); ?></h5>
                                                    <ul class="nav nav-tabs mb-3" role="tablist">
                                                        <li class="nav-item">
                                                            <a class="nav-link active" data-toggle="tab" href="#course-add-english-content" role="tab"><?php echo get_phrase('english'); ?></a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link" data-toggle="tab" href="#course-add-arabic-content" role="tab"><?php echo get_phrase('arabic'); ?></a>
                                                        </li>
                                                    </ul>
                                                    <div class="tab-content">
                                                        <div class="tab-pane active" id="course-add-english-content" role="tabpanel">
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="english_course_title"><?php echo get_phrase('course_title'); ?> <span class="required">*</span> </label>
                                                                <div class="col-md-10">
                                                                    <input type="text" class="form-control" id="english_course_title" name="english_title" placeholder="<?php echo get_phrase('enter_course_title'); ?>" required>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="english_course_slug"><?php echo get_phrase('english_slug'); ?></label>
                                                                <div class="col-md-10">
                                                                    <input type="text" class="form-control" id="english_course_slug" name="english_slug" placeholder="<?php echo get_phrase('generated_from_english_title_if_blank'); ?>">
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="english_short_description"><?php echo get_phrase('short_description'); ?></label>
                                                                <div class="col-md-10">
                                                                    <textarea name="english_short_description" id="english_short_description" class="form-control"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="description"><?php echo get_phrase('description'); ?></label>
                                                                <div class="col-md-10">
                                                                    <textarea name="english_description" id="description" class="form-control"></textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="tab-pane" id="course-add-arabic-content" role="tabpanel">
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="arabic_course_title"><?php echo get_phrase('arabic_title'); ?></label>
                                                                <div class="col-md-10">
                                                                    <input type="text" class="form-control" id="arabic_course_title" name="arabic_title" dir="rtl">
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="arabic_course_slug"><?php echo get_phrase('arabic_slug'); ?></label>
                                                                <div class="col-md-10">
                                                                    <input type="text" class="form-control" id="arabic_course_slug" name="arabic_slug" dir="rtl" placeholder="<?php echo get_phrase('generated_from_arabic_title_if_blank'); ?>">
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="arabic_short_description"><?php echo get_phrase('arabic_short_description'); ?></label>
                                                                <div class="col-md-10">
                                                                    <textarea name="arabic_short_description" id="arabic_short_description" class="form-control" dir="rtl"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label class="col-md-2 col-form-label" for="arabic_description"><?php echo get_phrase('arabic_description'); ?></label>
                                                                <div class="col-md-10">
                                                                    <textarea name="arabic_description" id="arabic_description" class="form-control" dir="rtl"></textarea>
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
                                                            <?php foreach ($categories->result_array() as $category): ?>
                                                                <optgroup label="<?php echo $category['name']; ?>">
                                                                    <?php $sub_categories = $this->crud_model->get_sub_categories($category['id']);
                                                                    foreach ($sub_categories as $sub_category): ?>
                                                                    <option value="<?php echo $sub_category['id']; ?>"><?php echo $sub_category['name']; ?></option>
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
                                                        <option value="beginner"><?php echo get_phrase('beginner'); ?></option>
                                                        <option value="advanced"><?php echo get_phrase('advanced'); ?></option>
                                                        <option value="intermediate"><?php echo get_phrase('intermediate'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group row mb-3">
                                                <label class="col-md-2 col-form-label" for="language_made_in"><?php echo get_phrase('language_made_in'); ?></label>
                                                <div class="col-md-10">
                                                    <select class="form-control select2" data-toggle="select2" name="language_made_in" id="language_made_in">
                                                        <option value="english">English</option>
                                                        <option value="arabic">Arabic</option>
                                                        <option value="arabic_translated">Arabic translated</option>
                                                    </select>
                                                    <small class="text-muted">Course content marker only. This does not control site language or bilingual translation rows.</small>
                                                </div>
                                            </div>
                                            <div class="form-group row mb-3">
                                                <label class="col-md-2 col-form-label" for="enable_drip_content"><?php echo get_phrase('enable_drip_content'); ?></label>
                                                <div class="col-md-10 pt-2">
                                                    <input type="checkbox" name="enable_drip_content" value="1" id="enable_drip_content" data-switch="primary">
                                                    <label for="enable_drip_content" data-on-label="On" data-off-label="Off"></label>
                                                </div>
                                            </div>

                                            
                                            <div class="form-group row mb-3">
                                                <label class="col-md-2 col-form-label pt-1" for="enable_drip_content"><?php echo get_phrase('Create as a'); ?></label>
                                                <div class="col-md-10 pt-1">
                                                    <div class="custom-control custom-radio mb-1">
                                                        <input type="radio" id="status_active" name="status" class="custom-control-input" value="active" checked>
                                                        <label class="custom-control-label" for="status_active"><?php echo get_phrase('Active course'); ?></label>
                                                    </div>

                                                    <div class="custom-control custom-radio mb-1">
                                                        <input type="radio" id="status_private" name="status" class="custom-control-input" value="private">
                                                        <label class="custom-control-label" for="status_private"><?php echo get_phrase('Private course'); ?></label>
                                                    </div>

                                                    <div id="upcoming" class="custom-control  custom-radio mb-1">
                                                        <input type="radio" id="status_upcoming" name="status" class="custom-control-input" value="upcoming">
                                                        <label class="custom-control-label" for="status_upcoming"><?php echo get_phrase('Upcoming course'); ?></label>
                                                    </div>
                                                    <!-- New Upcoming Image -->
                                                    <div class="form-group mt-3" id = "thumbnail-picker-area">
                                                        <div class="input-group">
                                                            <div class="custom-file">
                                                                <input type="file" class="custom-file-input" id="upcoming_image_thumbnail" name="upcoming_image_thumbnail" accept="image/*" onchange="changeTitleOfImageUploader(this)">
                                                                <label class="custom-file-label" for="upcoming_image_thumbnail"><?php echo get_phrase('upcoming_image_thumbnail'); ?></label>
                                                            </div>
                                                        </div>
                                                        <small>(<?php echo get_phrase('the_image_size_should_be'); ?>: 365 X 460)</small>
                                                    </div>
                                                    <!-- New Upcoming Image -->
                                                    <div class="form-group mb-3" id="publish_date">
                                                        <label class="col-form-label" for="input_publish_date"><?php echo get_phrase('publish_date'); ?> <span class="required">*</span> </label>
                                                            <input type="datetime-local" class="form-control" id="input_publish_date" name = "publish_date" placeholder="<?php echo get_phrase('enter_publish_date'); ?>" >
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="form-group row mb-3">
                                                <div class="offset-md-2 col-md-10">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input" name="is_top_course" id="is_top_course" value="1">
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
                                                    <div id = "faq_area">
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
                                                        <div id = "blank_faq_field">
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
                                                    <div id = "requirement_area">
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
                                                        <div id = "blank_requirement_field">
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
                                                    <div id = "outcomes_area">
                                                        <div class="d-flex mt-2">
                                                            <div class="flex-grow-1 px-3">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control" name="english_outcomes[]" id="outcomes" placeholder="<?php echo get_phrase('provide_outcomes'); ?>">
                                                                </div>
                                                            </div>
                                                            <div class="">
                                                                <button type="button" class="btn btn-success btn-sm" name="button" onclick="appendOutcome()"> <i class="fa fa-plus"></i> </button>
                                                            </div>
                                                        </div>
                                                        <div id = "blank_outcome_field">
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
                                                        <input type="text" class="form-control" name="arabic_faqs[]" id="arabic_faqs" dir="rtl" placeholder="<?php echo get_phrase('arabic_faq_question'); ?>">
                                                        <textarea name="arabic_faq_descriptions[]" class="form-control mt-2" dir="rtl" placeholder="<?php echo get_phrase('arabic_answer'); ?>"></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="arabic_requirements"><?php echo get_phrase('arabic_requirements'); ?></label>
                                                    <div class="col-md-10">
                                                        <input type="text" class="form-control" name="arabic_requirements[]" id="arabic_requirements" dir="rtl">
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="arabic_outcomes"><?php echo get_phrase('arabic_outcomes'); ?></label>
                                                    <div class="col-md-10">
                                                        <input type="text" class="form-control" name="arabic_outcomes[]" id="arabic_outcomes" dir="rtl">
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
                                                        <input type="checkbox" class="custom-control-input" name="is_free_course" id="is_free_course" value="1" onclick="togglePriceFields(this.id)">
                                                        <label class="custom-control-label" for="is_free_course"><?php echo get_phrase('check_if_this_is_a_free_course'); ?></label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="youngo-subscription-only-price-note alert alert-info">
                                                This course is available through subscription plans only. One-time purchase price is not used.
                                            </div>
                                            <div class="paid-course-stuffs">
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="price"><?php echo get_phrase('course_price').' ('.currency_code_and_symbol().')'; ?></label>
                                                    <div class="col-md-10">
                                                        <input type="number" class="form-control" id="price" name = "price" placeholder="<?php echo get_phrase('enter_course_course_price'); ?>" min="0">
                                                    </div>
                                                </div>

                                                <div class="form-group row mb-3">
                                                    <div class="offset-md-2 col-md-10">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" name="discount_flag" id="discount_flag" value="1">
                                                            <label class="custom-control-label" for="discount_flag"><?php echo get_phrase('check_if_this_course_has_discount'); ?></label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="discounted_price"><?php echo get_phrase('discounted_price').' ('.currency_code_and_symbol().')'; ?></label>
                                                    <div class="col-md-10">
                                                        <input type="number" class="form-control" name="discounted_price" id="discounted_price" onkeyup="calculateDiscountPercentage(this.value)" min="0">
                                                        <small class="text-muted"><?php echo get_phrase('this_course_has'); ?> <span id = "discounted_percentage" class="text-danger">0%</span> <?php echo get_phrase('discount'); ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="form-group row mb-3">
                                                <label class="col-md-2 col-form-label"><?php echo get_phrase('Expiry period'); ?></label>
                                                <div class="col-md-10 pt-2 d-flex">
                                                    <div class="custom-control custom-radio mr-2">
                                                        <input type="radio" id="lifetime_expiry_period" name="expiry_period" class="custom-control-input" value="lifetime" onchange="checkExpiryPeriod(this)" checked>
                                                        <label class="custom-control-label" for="lifetime_expiry_period"><?php echo get_phrase('Lifetime'); ?></label>
                                                    </div>
                                                    <div class="custom-control custom-radio">
                                                        <input type="radio" id="limited_expiry_period" name="expiry_period" class="custom-control-input" value="limited_time" onchange="checkExpiryPeriod(this)">
                                                        <label class="custom-control-label" for="limited_expiry_period"><?php echo get_phrase('Limited time'); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group row mb-3" id="number_of_month" style="display: none">
                                                <label class="col-md-2 col-form-label"><?php echo get_phrase('Number of month'); ?></label>
                                                <div class="col-md-10">
                                                    <input class="form-control" type="number" name="number_of_month" min="1">
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
                                                                    <option value="subscription_only" selected>Subscription only</option>
                                                                    <option value="subscription_and_purchase">Subscription and individual purchase</option>
                                                                    <option value="purchase_only">Individual purchase only</option>
                                                                </select>
                                                            </div>

                                                            <div class="youngo-purchase-access-fields">
                                                                <p class="text-muted mb-3">
                                                                    Disable legacy free-course mode and provide a valid course price before saving a purchase-enabled access mode.
                                                                </p>
                                                                <div class="form-group">
                                                                    <label for="youngo_purchase_access_type"><?php echo get_phrase('purchase_access_type'); ?></label>
                                                                    <select class="form-control" name="youngo_purchase_access_type" id="youngo_purchase_access_type">
                                                                        <option value="lifetime" selected>Lifetime</option>
                                                                        <option value="timed">Timed</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group mb-0" id="youngo_purchase_duration_days_group">
                                                                    <label for="youngo_purchase_duration_days"><?php echo get_phrase('purchase_duration_in_days'); ?></label>
                                                                    <input type="number" class="form-control" name="youngo_purchase_duration_days" id="youngo_purchase_duration_days" min="1">
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
                                                        <option value="youtube"><?php echo get_phrase('youtube'); ?></option>
                                                        <option value="vimeo"><?php echo get_phrase('vimeo'); ?></option>
                                                        <option value="html5"><?php echo get_phrase('HTML5'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div> <!-- end col -->

                                        <div class="col-xl-8">
                                            <div class="form-group row mb-3">
                                                <label class="col-md-2 col-form-label" for="course_overview_url"><?php echo get_phrase('course_overview_url'); ?></label>
                                                <div class="col-md-10">
                                                    <input type="text" class="form-control" name="course_overview_url" id="course_overview_url" placeholder="E.g: https://www.youtube.com/watch?v=oBtf8Yglw2w">
                                                </div>
                                            </div>
                                        </div> <!-- end col -->
                                        <!-- this portion will be generated theme wise from the theme-config.json file Starts-->
                                        <?php include 'course_media_add.php'; ?>
                                        <!-- this portion will be generated theme wise from the theme-config.json file Ends-->

                                    </div> <!-- end row -->
                                </div>
                                <div class="tab-pane" id="seo">
                                    <div class="row justify-content-center">
                                        <div class="col-xl-8">
                                            <div class="form-group row mb-3">
                                                <label class="col-md-2 col-form-label" for="website_keywords"><?php echo get_phrase('meta_keywords'); ?></label>
                                                <div class="col-md-10">
                                                    <input type="text" class="form-control bootstrap-tag-input" id="meta_keywords" name="english_meta_keywords" data-role="tagsinput" style="width: 100%;" placeholder="<?php echo get_phrase('write_a_keyword_and_then_press_enter_button'); ?>"./>
                                                </div>
                                            </div>
                                            <div class="form-group row mb-3">
                                                <label class="col-md-2 col-form-label" for="english_seo_title"><?php echo get_phrase('english_seo_title'); ?></label>
                                                <div class="col-md-10">
                                                    <input type="text" class="form-control" id="english_seo_title" name="english_seo_title">
                                                </div>
                                            </div>
                                        </div> <!-- end col -->
                                        <div class="col-xl-8">
                                            <div class="form-group row mb-3">
                                                <label class="col-md-2 col-form-label" for="meta_description"><?php echo get_phrase('meta_description'); ?></label>
                                                <div class="col-md-10">
                                                    <textarea name="english_meta_description" class="form-control"></textarea>
                                                </div>
                                            </div>
                                            <div class="border rounded p-3 mb-3">
                                                <h5 class="mb-3">Arabic SEO (optional)</h5>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="arabic_seo_title"><?php echo get_phrase('arabic_seo_title'); ?></label>
                                                    <div class="col-md-10">
                                                        <input type="text" class="form-control" id="arabic_seo_title" name="arabic_seo_title" dir="rtl">
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="arabic_meta_keywords"><?php echo get_phrase('arabic_meta_keywords'); ?></label>
                                                    <div class="col-md-10">
                                                        <input type="text" class="form-control" id="arabic_meta_keywords" name="arabic_meta_keywords" dir="rtl">
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="arabic_meta_description"><?php echo get_phrase('arabic_meta_description'); ?></label>
                                                    <div class="col-md-10">
                                                        <textarea name="arabic_meta_description" id="arabic_meta_description" class="form-control" dir="rtl"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div> <!-- end col -->
                                    </div> <!-- end row -->
                                </div>
                                <div class="tab-pane" id="finish">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="text-center">
                                                <h2 class="mt-0"><i class="mdi mdi-check-all"></i></h2>
                                                <h3 class="mt-0"><?php echo get_phrase("thank_you"); ?> !</h3>

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
  $(document).ready(function () {
    initSummerNote(['#description']);
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

function priceChecked(elem){
  if (jQuery('#discountCheckbox').is(':checked')) {

    jQuery('#discountCheckbox').prop( "checked", false );
  }else {

    jQuery('#discountCheckbox').prop( "checked", true );
  }
}

function topCourseChecked(elem){
  if (jQuery('#isTopCourseCheckbox').is(':checked')) {

    jQuery('#isTopCourseCheckbox').prop( "checked", false );
  }else {

    jQuery('#isTopCourseCheckbox').prop( "checked", true );
  }
}

function isFreeCourseChecked(elem) {

  if (jQuery('#'+elem.id).is(':checked')) {
    $('#price').prop('required',false);
  }else {
    $('#price').prop('required',true);
  }
}

function calculateDiscountPercentage(discounted_price) {
  if (discounted_price > 0) {
    var actualPrice = jQuery('#price').val();
    if ( actualPrice > 0) {
      var reducedPrice = actualPrice - discounted_price;
      var discountedPercentage = (reducedPrice / actualPrice) * 100;
      if (discountedPercentage > 0) {
        jQuery('#discounted_percentage').text(discountedPercentage.toFixed(2)+'%');

      }else {
        jQuery('#discounted_percentage').text('<?php echo '0%'; ?>');
      }
    }
  }
}

function youngoToggleAddCourseAccessFields() {
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
    youngoToggleAddCourseAccessFields();
    jQuery('#youngo_access_mode, #youngo_purchase_access_type').on('change', youngoToggleAddCourseAccessFields);
  }
});
</script>

<style media="screen">
body {
  overflow-x: hidden;
}
</style>

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
    });
</script>
