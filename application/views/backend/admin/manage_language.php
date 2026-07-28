<div class="row ">
	<div class="col-xl-12">
		<div class="card">
			<div class="card-body">
				<h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i> <?php echo get_phrase('manage_language'); ?></h4>
			</div>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-body">
				<ul class="nav nav-tabs nav-bordered mb-3">
					<?php if (isset($edit_profile)) : ?>
						<li class="nav-item">
							<a href="#edit" data-toggle="tab" aria-expanded="true" class="nav-link active">
								<?php echo get_phrase('edit_phrase'); ?>
							</a>
						</li>
					<?php endif; ?>
					<li class="nav-item">
						<a href="#list" data-toggle="tab" aria-expanded="false" class="nav-link <?php if (!isset($edit_profile)) echo 'active'; ?>">
							<i class="mdi mdi-home-variant d-lg-none d-block mr-1"></i>
							<span class="d-none d-lg-block"><?php echo get_phrase('language_list'); ?></span>
						</a>
					</li>
					<li class="nav-item">
						<a href="#add_lang" data-toggle="tab" aria-expanded="false" class="nav-link">
							<i class="mdi mdi-settings-outline d-lg-none d-block mr-1"></i>
							<span class="d-none d-lg-block"><?php echo get_phrase('add_language'); ?></span>
						</a>
					</li>
					<li class="nav-item">
						<a href="#import_language" data-toggle="tab" aria-expanded="false" class="nav-link">
							<i class="mdi mdi-settings-outline d-lg-none d-block mr-1"></i>
							<span class="d-none d-lg-block"><?php echo get_phrase('Import language'); ?></span>
						</a>
					</li>
				</ul>

				<div class="tab-content">
					<!----PHRASE EDITING TAB STARTS-->
					<?php if (isset($edit_profile)) :
						$current_editing_language	=	$edit_profile;
						$youngo_edit_phrase_languages = isset($youngo_edit_phrase_languages) && is_array($youngo_edit_phrase_languages) ? $youngo_edit_phrase_languages : array('english', 'arabic');
						$youngo_initial_edit_language = in_array($current_editing_language, $youngo_edit_phrase_languages, true) ? $current_editing_language : (in_array('english', $youngo_edit_phrase_languages, true) ? 'english' : reset($youngo_edit_phrase_languages));
					?>
						<div class="tab-pane show active" id="edit" style="padding: 30px">
							<div id="youngo-edit-phrase-app" data-endpoint="<?php echo site_url('admin/youngo/language/edit-phrase-data'); ?>" data-update-url="<?php echo site_url('admin/update_phrase_with_ajax'); ?>">
								<div class="row mb-3">
									<div class="col-md-5 mb-2">
										<label for="youngo_edit_phrase_search"><?php echo get_phrase('search_phrases'); ?></label>
										<input type="search" id="youngo_edit_phrase_search" class="form-control" placeholder="<?php echo get_phrase('search_phrase_key_or_value'); ?>">
									</div>
									<div class="col-md-4 mb-2">
										<label for="youngo_edit_phrase_language"><?php echo get_phrase('language'); ?></label>
										<select id="youngo_edit_phrase_language" class="form-control">
											<?php foreach ($youngo_edit_phrase_languages as $language) :
												if ($language === 'arabic_translated') {
													continue;
												}
												?>
												<option value="<?php echo html_escape($language); ?>" <?php if ($language === $youngo_initial_edit_language) echo 'selected'; ?>>
													<?php echo ucwords(str_replace('_', ' ', $language)); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="col-md-3 mb-2">
										<label for="youngo_edit_phrase_per_page"><?php echo get_phrase('page_size'); ?></label>
										<select id="youngo_edit_phrase_per_page" class="form-control">
											<option value="25" selected>25</option>
											<option value="50">50</option>
											<option value="100">100</option>
										</select>
									</div>
								</div>

								<div id="youngo_edit_phrase_loading" class="alert alert-secondary" style="display: none;">Loading phrases...</div>
								<div id="youngo_edit_phrase_empty" class="alert alert-info" style="display: none;">No phrases match the current filters.</div>
								<div id="youngo_edit_phrase_error" class="alert alert-danger" style="display: none;">Phrase data could not be loaded.</div>

								<div class="table-responsive">
									<table class="table table-bordered table-centered mb-0" id="youngo_edit_phrase_table">
										<thead>
											<tr>
												<th style="width: 35%;"><?php echo get_phrase('phrase_key'); ?></th>
												<th><?php echo get_phrase('value'); ?></th>
												<th style="width: 80px;"><?php echo get_phrase('option'); ?></th>
											</tr>
										</thead>
										<tbody id="youngo_edit_phrase_rows"></tbody>
									</table>
								</div>

								<div class="d-flex flex-wrap align-items-center justify-content-between mt-3" id="youngo_edit_phrase_pagination">
									<div class="text-muted mb-2" id="youngo_edit_phrase_summary"></div>
									<div class="btn-group mb-2" role="group" aria-label="Edit phrase pagination">
										<button type="button" class="btn btn-outline-secondary" id="youngo_edit_phrase_prev"><?php echo get_phrase('previous'); ?></button>
										<button type="button" class="btn btn-outline-secondary" id="youngo_edit_phrase_next"><?php echo get_phrase('next'); ?></button>
									</div>
								</div>
							</div>
						</div>
					<?php endif; ?>
					<!----PHRASE EDITING TAB ENDS-->

					<!----TABLE LISTING STARTS-->
					<div class="tab-pane <?php if (!isset($edit_profile)) echo 'show active'; ?>" id="list">

						<div class="table-responsive-sm">
							<table class="table table-bordered table-centered mb-0">
								<thead>
									<tr>
										<th><?php echo get_phrase('language'); ?></th>
										<th><?php echo get_phrase('Direction'); ?></th>
										<th><?php echo get_phrase('option'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php
									$language_dirs = get_settings('language_dirs') ? json_decode(get_settings('language_dirs'), true) : ['english' => 'ltr'];
									foreach ($languages as $language) :
										if(array_key_exists($language, $language_dirs)){
											$dir = $language_dirs[$language];
										}else{
											$dir = 'ltr';
										}
										?>
										<tr>
											<td><?php echo ucwords($language); ?></td>
											<td>
												<div class="form-group">
													<form action="#">
														<input onchange="update_language_dir('<?php echo $language; ?>', 'ltr')" name="direction" id="direction_ltr<?php echo $language; ?>" type="radio" value="ltr" <?php if($dir == 'ltr') echo 'checked'; ?>>
														<label for="direction_ltr<?php echo $language; ?>"><?php echo get_phrase('LTR') ?></label>
														&nbsp;&nbsp;
														<input onchange="update_language_dir('<?php echo $language; ?>', 'rtl')" name="direction" id="direction_rtl<?php echo $language; ?>" type="radio" value="rtl" <?php if($dir == 'rtl') echo 'checked'; ?>>
														<label for="direction_rtl<?php echo $language; ?>"><?php echo get_phrase('RTL') ?></label>
													</form>
												</div>
											</td>
											<td>
												<a href="<?php echo site_url('admin/manage_language/edit_phrase/' . $language); ?>" class="btn btn-info">
													<?php echo get_phrase('edit_phrase'); ?>
												</a>
												<a href="<?php echo site_url('admin/export_language/' . $language); ?>" class="btn btn-success">
													<?php echo get_phrase('export'); ?>
												</a>
												<a href="javascript:;" onclick="confirm_modal('<?php echo site_url('admin/manage_language/delete_language/' . $language); ?>')" class="btn btn-danger">
													<?php echo get_phrase('delete_language'); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>

						</div>
					</div>
					<!----TABLE LISTING ENDS--->

					<!----PHRASE CREATION FORM STARTS---->
					<div class="tab-pane" id="add" style="padding: 30px">
						<div class="row">
							<div class="col-xl-6">
								<form class="" action="<?php echo site_url('admin/manage_language/add_phrase') ?>" method="post">
									<div class="form-group mb-3">
										<label for="simpleinput"><?php echo get_phrase('add_new_phrase'); ?></label>
										<input type="text" id="phrase" name="phrase" class="form-control" placeholder="<?php echo get_phrase('eg._contamination'); ?>">
									</div>
									<button type="submit" class="btn btn-primary" name="button"><?php echo get_phrase('save'); ?></button>
								</form>
							</div>
						</div>
					</div>
					<!----PHRASE CREATION FORM ENDS--->

					<!----ADD NEW LANGUAGE---->
					<div class="tab-pane" id="add_lang" style="padding: 30px">
						<div class="row">
							<div class="col-xl-6">
								<form class="" action="<?php echo site_url('admin/manage_language/add_language'); ?>" method="post">
									<div class="form-group mb-3">
										<label for="language"><?php echo get_phrase('add_new_language'); ?></label>
										<input type="text" id="language" name="language" class="form-control" placeholder="<?php echo get_phrase('no_special_character_or_space_is_allowed') . '. ' . get_phrase('valid_examples') . ' : French, Spanish, Bengali etc'; ?>">
									</div>
									<button type="submit" class="btn btn-primary" name="button"><?php echo get_phrase('save'); ?></button>
								</form>
							</div>
						</div>
					</div>
					<!----LANGUAGE ADDING FORM ENDS-->

					<!----ADD NEW LANGUAGE---->
					<div class="tab-pane" id="import_language" style="padding: 30px">
						<div class="row">
							<div class="col-xl-6">
								<div class="card border mb-4" id="youngo-arabic-import-preview-panel">
									<div class="card-body">
										<h5 class="mb-3"><?php echo get_phrase('safe_arabic_import_preview'); ?></h5>
										<div class="row">
											<div class="col-md-6">
												<p class="mb-1 text-muted"><?php echo get_phrase('target_language'); ?></p>
												<strong>Arabic (arabic)</strong>
											</div>
											<div class="col-md-6">
												<p class="mb-1 text-muted"><?php echo get_phrase('source_file'); ?></p>
												<strong>arabic.json</strong>
											</div>
										</div>
										<div class="form-group mt-3">
											<label for="youngo_arabic_import_mode"><?php echo get_phrase('import_mode'); ?></label>
											<select id="youngo_arabic_import_mode" class="form-control">
												<option value="missing_blank_only">missing_blank_only</option>
												<option value="update_imported_non_manual">update_imported_non_manual</option>
												<option value="force_overwrite">force_overwrite preview only / dangerous</option>
											</select>
										</div>
										<button type="button" class="btn btn-outline-primary" id="youngo_arabic_import_preview_button" data-preview-url="<?php echo site_url('admin/youngo/language/arabic-import-preview'); ?>">
											<i class="mdi mdi-eye-outline"></i> Preview Arabic import
										</button>
										<div class="alert alert-warning mt-3 mb-0">
											Preview only. Full Arabic import is disabled in this phase and phrase values are not shown here.
										</div>
										<div id="youngo_arabic_import_preview_result" class="mt-3" style="display: none;"></div>
									</div>
								</div>
								<p><?php echo get_phrase('import_your_language_files_from_here.'); ?></p>
								<div class="alert alert-info">
									Legacy import is blocked for arabic and arabic_translated. Use the safe Arabic preview workflow above.
								</div>
								<form action="<?php echo site_url('admin/language_import'); ?>" method="post" enctype="multipart/form-data">
									<div class="input-group mb-3">
										<div class="input-group">
											<div class="custom-file">
												<input type="file" class="custom-file-input" name="language_files[]" id="language_files" onchange="changeTitleOfImageUploader(this)" accept=".json" multiple required>
												<label class="custom-file-label ellipsis" for="language_files"><?php echo get_phrase('choose_your_json_file'); ?></label>
											</div>
										</div>
										<span class="badge badge-light">Ex: english.json</span>
									</div>

									<div class="form-group">
										<button type="submit" class="btn btn-primary"> <i class="mdi mdi-database-export"></i> <?php echo get_phrase('import'); ?></button>
									</div>
								</form>
							</div>
						</div>
					</div>
					<!----LANGUAGE ADDING FORM ENDS-->
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	function updatePhrase(key, key_main) {
		$('#btn-' + key).text('...');
		var updatedValue = $('#phrase-' + key).val();
		var currentEditingLanguage = $('#youngo_edit_phrase_language').length ? $('#youngo_edit_phrase_language').val() : '<?php echo isset($current_editing_language) ? $current_editing_language:''; ?>';
		$.ajax({
			type: "POST",
			url: "<?php echo site_url('admin/update_phrase_with_ajax'); ?>",
			data: {
				updatedValue: updatedValue,
				currentEditingLanguage: currentEditingLanguage,
				key: key_main
			},
			success: function(response) {
				$('#btn-' + key).html('<i class = "mdi mdi-check-circle"></i>');
				success_notify('<?php echo get_phrase('phrase_updated'); ?>');
			}
		});
	}

	function update_language_dir(language, dir){
		$.ajax({
			type: 'post',
			url: '<?php echo site_url('admin/update_language_direction'); ?>',
			data: {'language':language, 'dir':dir},
			success: function(response){
				success_notify(response);
			}
		});
	}

	(function() {
		var app = $('#youngo-edit-phrase-app');
		if (!app.length) {
			return;
		}

		var endpoint = app.data('endpoint');
		var rowsContainer = $('#youngo_edit_phrase_rows');
		var table = $('#youngo_edit_phrase_table');
		var loading = $('#youngo_edit_phrase_loading');
		var empty = $('#youngo_edit_phrase_empty');
		var error = $('#youngo_edit_phrase_error');
		var summary = $('#youngo_edit_phrase_summary');
		var prev = $('#youngo_edit_phrase_prev');
		var next = $('#youngo_edit_phrase_next');
		var search = $('#youngo_edit_phrase_search');
		var language = $('#youngo_edit_phrase_language');
		var perPage = $('#youngo_edit_phrase_per_page');
		var state = {
			page: 1,
			totalPages: 0,
			searchTimer: null
		};

		function escapeHtml(value) {
			return $('<div>').text(value === null || typeof value === 'undefined' ? '' : value).html();
		}

		function rowId(row) {
			return 'youngo-row-' + parseInt(row.phrase_id || 0, 10);
		}

		function encodeAttr(value) {
			return encodeURIComponent(value === null || typeof value === 'undefined' ? '' : value);
		}

		function renderRows(rows) {
			var html = '';
			rows.forEach(function(row) {
				var id = rowId(row);
				html += '<tr>';
				html += '<td><code>' + escapeHtml(row.phrase) + '</code></td>';
				html += '<td><input type="text" class="form-control" id="phrase-' + escapeHtml(id) + '" value="' + escapeHtml(row.selected_value) + '"></td>';
				html += '<td><button type="button" class="btn btn-icon btn-primary youngo-edit-phrase-save" id="btn-' + escapeHtml(id) + '" data-row-id="' + escapeHtml(id) + '" data-phrase-key="' + encodeAttr(row.phrase) + '"><i class="mdi mdi-check-circle"></i></button></td>';
				html += '</tr>';
			});
			rowsContainer.html(html);
		}

		function renderResult(result) {
			var rows = result && result.rows ? result.rows : [];
			state.totalPages = parseInt(result.total_pages || 0, 10);
			state.page = parseInt(result.page || 1, 10);

			renderRows(rows);
			table.toggle(rows.length > 0);
			empty.toggle(rows.length === 0);
			summary.text(rows.length > 0 ? ('Page ' + state.page + ' of ' + state.totalPages + ' - ' + result.total_rows + ' phrases') : '');
			prev.prop('disabled', state.page <= 1);
			next.prop('disabled', state.totalPages === 0 || state.page >= state.totalPages);
		}

		function loadPhrases(page) {
			state.page = page || 1;
			loading.show();
			empty.hide();
			error.hide();
			table.hide();
			prev.prop('disabled', true);
			next.prop('disabled', true);

			$.ajax({
				type: 'get',
				url: endpoint,
				dataType: 'json',
				data: {
					language: language.val(),
					page: state.page,
					per_page: perPage.val(),
					search: search.val()
				},
				success: function(response) {
					if (!response || !response.ok || !response.result) {
						error.show();
						summary.text('');
						return;
					}
					renderResult(response.result);
				},
				error: function() {
					error.show();
					summary.text('');
				},
				complete: function() {
					loading.hide();
				}
			});
		}

		search.on('input', function() {
			clearTimeout(state.searchTimer);
			state.searchTimer = setTimeout(function() {
				loadPhrases(1);
			}, 250);
		});

		language.on('change', function() {
			loadPhrases(1);
		});

		perPage.on('change', function() {
			loadPhrases(1);
		});

		prev.on('click', function() {
			if (state.page > 1) {
				loadPhrases(state.page - 1);
			}
		});

		next.on('click', function() {
			if (state.page < state.totalPages) {
				loadPhrases(state.page + 1);
			}
		});

		rowsContainer.on('click', '.youngo-edit-phrase-save', function() {
			var button = $(this);
			updatePhrase(button.data('row-id'), decodeURIComponent(button.attr('data-phrase-key') || ''));
		});

		loadPhrases(1);
	})();

	(function() {
		var previewButton = $('#youngo_arabic_import_preview_button');
		var previewResult = $('#youngo_arabic_import_preview_result');
		var previewFields = [
			'total_keys',
			'matching_existing_phrase_keys',
			'missing_phrase_keys',
			'blank_arabic_values',
			'non_blank_arabic_values',
			'manual_override_preserved',
			'legacy_existing_preserved',
			'imported_non_manual_updatable',
			'invalid_keys',
			'would_insert_meta',
			'would_update_phrase_values',
			'would_skip',
			'would_force_overwrite'
		];

		function escapeHtml(value) {
			return $('<div>').text(value === null || typeof value === 'undefined' ? '' : value).html();
		}

		function renderPreview(response) {
			var preview = response && response.preview ? response.preview : {};
			var html = '<div class="table-responsive"><table class="table table-sm table-bordered mb-0"><tbody>';
			html += '<tr><th><?php echo get_phrase('target_language'); ?></th><td>' + escapeHtml(preview.target_language_code) + '</td></tr>';
			html += '<tr><th><?php echo get_phrase('source_file'); ?></th><td>' + escapeHtml(preview.source_file) + '</td></tr>';
			html += '<tr><th><?php echo get_phrase('import_mode'); ?></th><td>' + escapeHtml(preview.import_mode) + '</td></tr>';
			html += '<tr><th><?php echo get_phrase('full_apply_allowed'); ?></th><td>' + (preview.full_apply_allowed ? 'yes' : 'no') + '</td></tr>';

			previewFields.forEach(function(field) {
				html += '<tr><th>' + escapeHtml(field) + '</th><td>' + escapeHtml(preview[field]) + '</td></tr>';
			});

			html += '</tbody></table></div>';
			if (response && response.message) {
				html += '<div class="alert alert-secondary mt-3 mb-0">' + escapeHtml(response.message) + '</div>';
			}
			if (preview.warnings && preview.warnings.length) {
				html += '<div class="alert alert-warning mt-3 mb-0">' + escapeHtml(preview.warnings.join(' ')) + '</div>';
			}

			previewResult.html(html).show();
		}

		previewButton.on('click', function() {
			var button = $(this);
			var originalHtml = button.html();
			button.prop('disabled', true).text('Previewing...');
			previewResult.hide().empty();

			$.ajax({
				type: 'get',
				url: button.data('preview-url'),
				data: {
					mode: $('#youngo_arabic_import_mode').val()
				},
				dataType: 'json',
				success: function(response) {
					renderPreview(response);
				},
				error: function() {
					previewResult.html('<div class="alert alert-danger mb-0">Preview is unavailable or access was denied.</div>').show();
				},
				complete: function() {
					button.prop('disabled', false).html(originalHtml);
				}
			});
		});
	})();
</script>
