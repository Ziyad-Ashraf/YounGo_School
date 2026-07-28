<!-- bundle -->
<script src="<?php echo base_url('assets/backend/js/app.min.js'); ?>"></script>
<!-- third party js -->
<script src="<?php echo base_url('assets/backend/js/vendor/Chart.bundle.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/jquery-jvectormap-1.2.2.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/jquery-jvectormap-world-mill-en.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/jquery.dataTables.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/dataTables.bootstrap4.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/dataTables.responsive.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/responsive.bootstrap4.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/dataTables.buttons.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/buttons.bootstrap4.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/buttons.html5.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/buttons.flash.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/buttons.print.min.js'); ?>"></script>
<script>
    // Phase 2W.4: DataTables ships English-only UI strings out of the box.
    // Setting the language on $.fn.dataTable.defaults (rather than editing
    // every .DataTable({...}) call site) applies to every table on every
    // admin page, current and future. Runs synchronously (not inside
    // $(document).ready) so it is in place before any page's own ready
    // handler calls .DataTable() - see script order note in the diagnostic.
    $.extend(true, $.fn.dataTable.defaults, {
        language: {
            search: <?php echo json_encode(get_phrase('datatable_search')); ?>,
            lengthMenu: <?php echo json_encode(get_phrase('datatable_length_menu')); ?>,
            info: <?php echo json_encode(get_phrase('datatable_info')); ?>,
            infoEmpty: <?php echo json_encode(get_phrase('datatable_info_empty')); ?>,
            infoFiltered: <?php echo json_encode(get_phrase('datatable_info_filtered')); ?>,
            loadingRecords: <?php echo json_encode(get_phrase('datatable_loading_records')); ?>,
            processing: <?php echo json_encode(get_phrase('datatable_processing')); ?>,
            zeroRecords: <?php echo json_encode(get_phrase('datatable_zero_records')); ?>,
            emptyTable: <?php echo json_encode(get_phrase('datatable_empty_table')); ?>,
            paginate: {
                first: <?php echo json_encode(get_phrase('datatable_paginate_first')); ?>,
                last: <?php echo json_encode(get_phrase('datatable_paginate_last')); ?>,
                next: <?php echo json_encode(get_phrase('datatable_paginate_next')); ?>,
                previous: <?php echo json_encode(get_phrase('datatable_paginate_previous')); ?>
            },
            aria: {
                sortAscending: <?php echo json_encode(get_phrase('datatable_aria_sort_ascending')); ?>,
                sortDescending: <?php echo json_encode(get_phrase('datatable_aria_sort_descending')); ?>
            },
            buttons: {
                copy: <?php echo json_encode(get_phrase('datatable_button_copy')); ?>,
                csv: <?php echo json_encode(get_phrase('datatable_button_csv')); ?>,
                excel: <?php echo json_encode(get_phrase('datatable_button_excel')); ?>,
                pdf: <?php echo json_encode(get_phrase('datatable_button_pdf')); ?>,
                print: <?php echo json_encode(get_phrase('datatable_button_print')); ?>,
                colvis: <?php echo json_encode(get_phrase('datatable_button_colvis')); ?>
            }
        }
    });
</script>
<script src="<?php echo base_url('assets/backend/js/vendor/dataTables.keyTable.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/dataTables.select.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/summernote-bs4.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/fullcalendar.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/pages/demo.summernote.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/dropzone.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/pages/datatable-initializer.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/font-awesome-icon-picker/fontawesome-iconpicker.min.js'); ?>" charset="utf-8"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/bootstrap-tagsinput.min.js'); ?>" charset="utf-8"></script>
<script src="<?php echo base_url() . 'assets/frontend/default-new/js/bootstrap.bundle.min.js'; ?>"></script>
<script src="<?php echo base_url('assets/backend/js/bootstrap-tagsinput.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/backend/js/vendor/dropzone.min.js'); ?>" charset="utf-8"></script>
<script src="<?php echo base_url('assets/backend/js/ui/component.fileupload.js'); ?>" charset="utf-8"></script>
<script src="<?php echo base_url('assets/backend/js/pages/demo.form-wizard.js'); ?>"></script>
<!-- dragula js-->
<script src="<?php echo base_url('assets/backend/js/vendor/dragula.min.js'); ?>"></script>
<!-- component js -->
<script src="<?php echo base_url('assets/backend/js/ui/component.dragula.js'); ?>"></script>

<!-- Jquery form -->
<script src="<?php echo base_url('assets/global/jquery-form/jquery.form.min.js'); ?>"></script>

<script src="<?php echo site_url('assets/backend/js/custom.js'); ?>"></script>

<!-- Dashboard chart's data is coming from this file -->

<?php if (isset($page_name) && $page_name == 'dashboard'): ?>
  <?php include "$logged_in_user_role/dashboard-chart.php"; ?>
<?php endif; ?>

<script type="text/javascript">
  $(document).ready(function() {
    $(function() {
      $('.icon-picker').iconpicker();
    });
  });
</script>

<!-- Toastr and alert notifications scripts -->
<script type="text/javascript">
  function notify(message) {
    $.NotificationApp.send("<?php echo get_phrase('heads_up'); ?>!", message, "top-right", "rgba(0,0,0,0.2)", "info");
  }

  function success_notify(message) {
    $.NotificationApp.send("<?php echo get_phrase('congratulations'); ?>!", message, "top-right", "rgba(0,0,0,0.2)", "success");
  }

  function error_notify(message) {
    $.NotificationApp.send("<?php echo get_phrase('oh_snap'); ?>!", message, "top-right", "rgba(0,0,0,0.2)", "error");
  }

  function error_required_field() {
    $.NotificationApp.send("<?php echo get_phrase('oh_snap'); ?>!", "<?php echo get_phrase('please_fill_all_the_required_fields'); ?>", "top-right", "rgba(0,0,0,0.2)", "error");
  }
</script>

<?php if ($this->session->flashdata('info_message') != ""): ?>
  <script type="text/javascript">
    $.NotificationApp.send("<?php echo get_phrase('success'); ?>!", '<?php echo $this->session->flashdata("info_message"); ?>', "top-right", "rgba(0,0,0,0.2)", "info");
  </script>
<?php endif; ?>

<?php if ($this->session->flashdata('error_message') != ""): ?>
  <script type="text/javascript">
    $.NotificationApp.send("<?php echo get_phrase('oh_snap'); ?>!", '<?php echo $this->session->flashdata("error_message"); ?>', "top-right", "rgba(0,0,0,0.2)", "error");
  </script>
<?php endif; ?>

<?php if ($this->session->flashdata('flash_message') != ""): ?>
  <script type="text/javascript">
    $.NotificationApp.send("<?php echo get_phrase('congratulations'); ?>!", '<?php echo $this->session->flashdata("flash_message"); ?>', "top-right", "rgba(0,0,0,0.2)", "success");
  </script>
<?php endif; ?>