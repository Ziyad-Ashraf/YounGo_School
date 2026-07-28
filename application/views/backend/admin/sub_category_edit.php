<?php
$data = $this->db->get_where('category', array('id' => $sub_category_id))->row_array();
if (!isset($this->youngo_translation_model)) {
    $this->load->model('youngo_translation_model');
}
$english_translation = $this->youngo_translation_model->get_category_translation_with_fallback($sub_category_id, 'english');
$arabic_translation  = $this->youngo_translation_model->get_category_translation($sub_category_id, 'arabic');
$english_name = !empty($english_translation['name']) ? $english_translation['name'] : $data['name'];
$english_slug = isset($english_translation['slug']) && $english_translation['slug'] !== '' ? $english_translation['slug'] : $data['slug'];
$arabic_name  = !empty($arabic_translation['name']) ? $arabic_translation['name'] : '';
$arabic_slug  = !empty($arabic_translation['slug']) ? $arabic_translation['slug'] : '';
?>
<ol class="breadcrumb bc-3">
    <li>
        <a href="<?php echo site_url('admin/dashboard'); ?>">
            <i class="entypo-folder"></i>
            <?php echo get_phrase('dashboard'); ?>
        </a>
    </li>
    <li><a href="<?php echo site_url('admin/sub_categories'); ?>"><?php echo get_phrase('sub_categories'); ?></a> </li>
    <li><a href="#" class="active"><?php echo get_phrase('edit_sub_category'); ?></a> </li>
</ol>
<h2><i class="fa fa-arrow-circle-o-right"></i> <?php echo $page_title; ?></h2>
<br />
<div class="row">
	<div class="col-md-8 col-md-offset-2">
		<div class="panel panel-primary" data-collapsed="0">
            <div class="panel-heading">
				<div class="panel-title">
					<?php echo get_phrase('sub_category_edit'); ?>
				</div>
			</div>
			<div class="panel-body">
                <form action="<?php echo site_url('admin/categories/edit/'.$sub_category_id); ?>" method="post" role="form" class="form-horizontal form-groups-bordered">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('sub_category_code'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" name = "code" class="form-control" readonly value="<?php echo $data['code']; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('category'); ?></label>
                            <div class="col-sm-5">
                                <select class="form-control select2" id="source" name="parent" data-init-plugin="select2" required>
                                    <?php foreach ($categories->result_array() as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php if($data['parent'] == $category['id']) echo 'selected'; ?>><?php echo $category['name']; ?></option>
                                    <?php endforeach; ?>

                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('english'); ?> <?php echo get_phrase('sub_category_title'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" name="english_name" class="form-control" required value="<?php echo html_escape($english_name); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('english'); ?> <?php echo get_phrase('slug'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" name="english_slug" class="form-control" value="<?php echo html_escape($english_slug); ?>" placeholder="<?php echo get_phrase('leave_blank_to_auto_generate'); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('arabic'); ?> <?php echo get_phrase('sub_category_title'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" name="arabic_name" class="form-control" value="<?php echo html_escape($arabic_name); ?>" dir="rtl">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('arabic'); ?> <?php echo get_phrase('slug'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" name="arabic_slug" class="form-control" value="<?php echo html_escape($arabic_slug); ?>" dir="rtl" placeholder="<?php echo get_phrase('leave_blank_to_auto_generate'); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-5">
                                <button class = "btn btn-success" type="submit" name="button"><?php echo get_phrase('update_sub_category'); ?></button>
                            </div>
                        </div>
                    </div>
                </form>
			</div>
		</div>
	</div>
</div>
