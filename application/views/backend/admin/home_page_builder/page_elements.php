<!-- Here must have the draggable class in the each elements -->

<h5 class="mb-3 pb-2 border-bottom text-14px">Buttons:</h5>
<div class="d-flex gap-2 align-items-center flex-wrap justify-content-between">
    <a href="#" class="draggable cursor-move py-2"><?php echo get_phrase('link'); ?></a>
    <a href="#" class="draggable cursor-move purchase-btn"><?php echo get_phrase('gradient'); ?></a>

    <a href="#" class="draggable cursor-move btn btn-primary"><?php echo get_phrase('primary'); ?></a>
    <a href="#" class="draggable cursor-move btn btn-dark"><?php echo get_phrase('dark'); ?></a>
    <a href="#" class="draggable cursor-move btn btn-light"><?php echo get_phrase('light'); ?></a>
    <a href="#" class="draggable cursor-move btn btn-success"><?php echo get_phrase('success'); ?></a>
    <a href="#" class="draggable cursor-move btn btn-info"><?php echo get_phrase('info'); ?></a>
    <a href="#" class="draggable cursor-move btn btn-warning"><?php echo get_phrase('warning'); ?></a>
    <a href="#" class="draggable cursor-move btn btn-danger"><?php echo get_phrase('danger'); ?></a>
</div>

<script>
    initialize_draggable('clone');
</script>