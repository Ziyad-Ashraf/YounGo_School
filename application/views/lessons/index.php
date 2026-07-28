<?php
$youngo_lesson_language = isset($youngo_frontend_language) ? $youngo_frontend_language : 'english';
if (!function_exists('youngo_lesson_phrase')) {
	function youngo_lesson_phrase($key, $fallback = '') {
		global $youngo_lesson_language;
		if ($youngo_lesson_language === 'arabic') {
			$arabic = array(
				'course_details' => 'تفاصيل الدورة',
				'course_content_not_found' => 'محتوى الدورة غير موجود',
				'please_ensure_that_your_course_has_at_least_one_section_and_one_lesson.' => 'يرجى التأكد من أن الدورة تحتوي على قسم ودرس واحد على الأقل.',
				'current_lesson' => 'الدرس الحالي',
				'learning_path' => 'مسار التعلم',
				'course_content' => 'محتوى الدورة',
				'sections' => 'أقسام',
				'lessons' => 'دروس',
				'completed' => 'مكتمل',
				'summary' => 'الملخص',
				'live_class' => 'الدرس المباشر',
				'description' => 'الوصف',
				'uncheck' => 'إلغاء التحديد',
				'mark_as_complete' => 'وضع علامة كمكتمل',
				'quiz' => 'اختبار',
				'audio' => 'صوت',
				'video' => 'فيديو',
				'txt' => 'ملف نصي',
				'pdf' => 'ملف PDF',
				'doc' => 'مستند',
			);
			if (isset($arabic[$key])) return $arabic[$key];
		}
		return function_exists('youngo_frontend_phrase')
			? youngo_frontend_phrase($key, $fallback, $youngo_lesson_language)
			: get_phrase($fallback !== '' ? $fallback : $key);
	}
}
$language_dir = $youngo_lesson_language === 'arabic' ? 'rtl' : 'ltr';

$full_page = $this->session->userdata('full_page_layout');
$user_id = $this->session->userdata('user_id');
$is_course_instructor = $this->crud_model->is_course_instructor($course_details['id'], $user_id);
$number_of_lessons = $this->crud_model->get_lessons('course', $course_details['id'])->num_rows();
$completed_lessons_for_header = array();
if(isset($watch_history) && !empty($watch_history['completed_lesson']) && is_array(json_decode($watch_history['completed_lesson'], true))){
	$completed_lessons_for_header = json_decode($watch_history['completed_lesson'], true);
}
$completed_lesson_count = count($completed_lessons_for_header);
$course_progress_value = isset($watch_history['course_progress']) ? round($watch_history['course_progress']) : 0;
$lesson_type_label = '';
if(isset($lesson_details) && is_array($lesson_details) && !empty($lesson_details['lesson_type'])){
	if($lesson_details['lesson_type'] == 'other' || $lesson_details['lesson_type'] == 'text'){
		$lesson_type_label = !empty($lesson_details['attachment_type']) ? youngo_lesson_phrase($lesson_details['attachment_type'], $lesson_details['attachment_type']) : youngo_lesson_phrase($lesson_details['lesson_type'], $lesson_details['lesson_type']);
	}else{
		$lesson_type_label = youngo_lesson_phrase($lesson_details['lesson_type'], $lesson_details['lesson_type']);
	}
}
?>

<!DOCTYPE html>
<html lang="<?php echo $youngo_lesson_language === 'arabic' ? 'ar' : 'en'; ?>" dir="<?php echo $language_dir; ?>">
<head>
	<title><?php echo $course_details['title'].' | '.get_settings('system_name'); ?></title>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="author" content="<?php echo get_settings('author') ?>" />
	<meta name="keywords" content="<?php echo $course_details['meta_keywords']; ?>"/>
	<meta name="description" content="<?php echo $course_details['meta_description']; ?>" />
	<link name="favicon" type="image/x-icon" href="<?php echo base_url('uploads/system/'.get_frontend_settings('favicon')); ?>" rel="shortcut icon" />

	<?php include 'includes_top.php';?>

	<style type="text/css">
		.custom-accordion .accordion-button{
			padding: 13px 0px !important;
		}
		.course-content-items .item a{
			font-family: "Inter", sans-serif;
			line-height: 20px !important;
		    font-size: 15px;
		    font-weight: 500;
		    line-height: 34px;
		    color: #737982;
		    transition: all 0.3s;
		}
		.course-content-items .item a > i{
			border-radius: 50%;
		    height: 29px;
		    width: 29px;
		    padding: 10.5px 11.5px;
		    font-size: 8px;
		    background-color: rgba(115, 121, 130, 0.2);
		    color: #6f7a8b;
		}
		.course-content-items .item.active a > i{
		    background-color: #fff;
		    color: #1663d4;
		}
		.course-content-items .item a .checkbox, .course-content-items .item .checkbox{
			min-height: 20.5px;
    		min-width: 35px;
    		position: relative;
		}
		.course-content-items .item a input, .course-content-items .item input{
		    min-width: 20px;
		    min-height: 20px;
		    position: absolute;
    		top: 4px;
    		left: 4.5px;
		}
		.course-content-items .lesson-icon{
			font-size: 10px;
		    margin-top: -2px !important;
		    display: inline-block;
		    font-weight: 700;
		}
		.course-content-items .item.active a{
			color: #fff;
		}
		.lesson_checkbox, .lesson_checkbox:hover{
			accent-color: #e3e4e6;
		}
	</style>
</head>

<body class="youngo-lesson-player">
<nav class="youngo-lesson-header fixed-top" aria-label="<?php echo get_phrase('Lesson navigation'); ?>">
	<div class="youngo-lesson-header__inner">
		<a class="youngo-lesson-brand" href="<?php echo site_url('home'); ?>" aria-label="<?php echo get_settings('system_name'); ?>">
			<img src="<?php echo base_url('assets/frontend/youngo/images/logo_small_c.png'); ?>" alt="<?php echo get_settings('system_name'); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
			<span class="youngo-logo-fallback"><?php echo get_settings('system_name'); ?></span>
		</a>

		<div class="youngo-lesson-heading">
			<a aria-current="page" href="<?php echo site_url('home/course/'.slugify($course_details['title']).'/'.$course_details['id']); ?>">
				<strong><?php echo $course_details['title']; ?></strong>
			</a>
			<div class="youngo-lesson-progress" aria-label="<?php echo get_phrase('Course progress'); ?>">
				<div class="youngo-lesson-progress__bar"><span style="width: <?php echo $course_progress_value; ?>%;"></span></div>
				<span><?php echo $course_progress_value . '% ' . youngo_lesson_phrase('completed', 'Completed'); ?> (<?php echo $completed_lesson_count; ?>/<?php echo $number_of_lessons; ?>)</span>
			</div>
		</div>

		<div class="youngo-lesson-actions">
			<?php if($full_page): ?>
				<a href="#" onclick="actionTo('<?php echo site_url('home/course_playing_page_layout'); ?>')" class="youngo-lesson-action" aria-label="<?php echo get_phrase('Exit full page layout'); ?>"><i class="fas fa-arrows-alt"></i></a>
			<?php else: ?>
				<a href="#" onclick="actionTo('<?php echo site_url('home/course_playing_page_layout'); ?>')" class="youngo-lesson-action" aria-label="<?php echo get_phrase('Expand lesson layout'); ?>"><i class="fas fa-arrows-alt-h"></i></a>
			<?php endif; ?>

			<a href="<?php echo site_url('home/course/'.slugify($course_details['title']).'/'.$course_details['id']); ?>" class="youngo-lesson-action">
				<i class="fas fa-chevron-left"></i>
				<span><?php echo youngo_lesson_phrase('course_details', 'Course details'); ?></span>
			</a>

			<?php if($this->session->userdata('admin_login')): ?>
				<a href="<?php echo site_url('admin/course_form/course_edit/'.$course_details['id']); ?>" class="youngo-lesson-action youngo-lesson-action--primary">
					<span><?php echo get_phrase('Course Manager'); ?></span>
					<i class="fas fa-angle-right"></i>
				</a>
			<?php elseif($is_course_instructor): ?>
				<a href="<?php echo site_url('user/course_form/course_edit/'.$course_details['id']); ?>" class="youngo-lesson-action youngo-lesson-action--primary">
					<span><?php echo get_phrase('Course Manager'); ?></span>
					<i class="fas fa-angle-right"></i>
				</a>
			<?php else: ?>
				<a href="<?php echo site_url('home/my_courses'); ?>" class="youngo-lesson-action youngo-lesson-action--primary">
					<span><?php echo get_phrase('My Courses'); ?></span>
					<i class="fas fa-angle-right"></i>
				</a>
			<?php endif; ?>
		</div>
	</div>
</nav>



	<!-- Start Course Playing -->
	<section class="course-playing youngo-lesson-shell">
		<div class="youngo-lesson-container">
			<div class="row g-3 justify-content-center youngo-lesson-grid">
				<!-- Sidebar -->
				<?php if($course_details['course_type'] == 'general'): ?>
					<?php if(!is_array($lesson_details)): ?>
						<div class="youngo-lesson-empty">
							<h5><?php echo youngo_lesson_phrase('course_content_not_found', 'Course content not found') ?></h5>
							<p><?php echo youngo_lesson_phrase('please_ensure_that_your_course_has_at_least_one_section_and_one_lesson.', 'Please ensure that your course has at least one section and one lesson.'); ?></p>
						</div>
					<?php endif; ?>

					<div class="<?php if($full_page){ echo 'col-lg-12'; }else{ echo 'col-lg-4'; } ?> order-2 youngo-lesson-sidebar-column">
						<?php include "sidebar.php"; ?>
					</div>
					<!-- Content -->
					<div class="<?php if($full_page){ echo 'col-lg-12'; }else{ echo 'col-lg-8'; } ?> order-1 youngo-lesson-content-column">
						<?php if(is_array($lesson_details)): ?>
							<div class="course-playing-content youngo-lesson-content-panel">
								<div class="youngo-lesson-current">
									<div>
									<p class="youngo-lesson-current__eyebrow"><i class="far fa-play-circle"></i> <?php echo youngo_lesson_phrase('current_lesson', 'Current lesson'); ?></p>
										<h1><?php echo $lesson_details['title']; ?></h1>
										<div class="youngo-lesson-current__meta">
											<?php if($lesson_type_label != ''): ?><span><i class="far fa-file-alt"></i> <?php echo $lesson_type_label; ?></span><?php endif; ?>
											<?php if(!empty($lesson_details['duration'])): ?><span><i class="far fa-clock"></i> <?php echo $lesson_details['duration']; ?></span><?php endif; ?>
										</div>
									</div>
									<?php if($lesson_type_label != ''): ?><span class="youngo-lesson-type-pill"><?php echo $lesson_type_label; ?></span><?php endif; ?>
								</div>
								<div class="youngo-lesson-content-surface">
									<div class="youngo-lesson-content-frame" <?php if($full_page) echo 'style="margin-top: -2px;"'; ?>>
									<?php if(in_array($lesson_details['id'], $locked_lesson_ids) && $course_details['enable_drip_content']): ?>
										<div class="youngo-lesson-locked">
											<?php echo remove_js(htmlspecialchars_decode_($drip_content_settings['locked_lesson_message'])); ?>
										</div>
									<?php else: ?>
										<?php if(in_array($lesson_details['section_id'], $restricted_section_ids)): ?>
											<div class="youngo-lesson-locked">
												<div class="locked-card">
													<i class="fas fa-lock text-30px"></i>
													<h6 class="w-100 text-center text-dark my-2"><?php echo get_phrase('This section is not included in the current study plan'); ?></h6>
													<small class="text-12px"><?php echo date('d M Y h:i A', $section['start_date']).' - '.date('d M Y h:i A', $section['end_date']); ?></small>
												</div>
											</div>
										<?php else: ?>
											<?php include $course_details['course_type'].'_course_content_body.php'; ?>
										<?php endif ?>
									<?php endif; ?>
									</div>
									<div class="content youngo-lesson-tabs">
										<div>
											<?php include "bottom_tabs.php"; ?>
										</div>
									</div>
								</div>
							</div>
						<?php endif; ?>
					</div>
				<?php else: ?>
					<div class="col-lg-12 youngo-lesson-content-column">
						
						<?php include $course_details['course_type'].'_course_content_body.php'; ?>

						<div class="row">
							<div class="col-md-12 pt-5">
								<?php include "bottom_tabs.php"; ?>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
    <!-- End Course Playing -->
    <?php include "includes_bottom.php"; ?>
    <?php include APPPATH."views/frontend/default-new/common_scripts.php"; ?>
    <?php include APPPATH."views/frontend/default-new/init.php"; ?>
</body>
</html>
