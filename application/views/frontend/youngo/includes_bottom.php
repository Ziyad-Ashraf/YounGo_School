<script src="<?php echo base_url('assets/global/js/jquery-3.6.1.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/global/jquery-form/jquery.form.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/global/toastr/toastr.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/frontend/youngo/js/youngo.js'); ?>"></script>

<?php if ($this->session->flashdata('flash_message') != ""): ?>
    <script>toastr.success('<?php echo $this->session->flashdata("flash_message"); ?>');</script>
<?php endif; ?>
<?php if ($this->session->flashdata('error_message') != ""): ?>
    <script>toastr.error('<?php echo $this->session->flashdata("error_message"); ?>');</script>
<?php endif; ?>
<?php if ($this->session->flashdata('info_message') != ""): ?>
    <script>toastr.info('<?php echo $this->session->flashdata("info_message"); ?>');</script>
<?php endif; ?>
