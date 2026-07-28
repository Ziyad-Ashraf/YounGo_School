<input type="hidden" name="lesson_type" value="text-description">

<div class="border rounded p-3 mb-3">
    <h5 class="mb-3"><?php echo get_phrase('english'); ?> <?php echo get_phrase('text'); ?></h5>
    <div class="form-group mb-0">
        <label for="english_text_content"> <?php echo get_phrase('enter_your_text'); ?></label>
        <textarea name="english_text_content" class="form-control" id="english_text_content" rows="4"></textarea>
    </div>
</div>

<div class="border rounded p-3 mb-3">
    <h5 class="mb-3"><?php echo get_phrase('arabic'); ?> <?php echo get_phrase('text'); ?></h5>
    <div class="form-group mb-0">
        <label for="arabic_text_content"> <?php echo get_phrase('enter_your_text'); ?></label>
        <textarea name="arabic_text_content" class="form-control" id="arabic_text_content" rows="4" dir="rtl"></textarea>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        initSummerNote(['#english_text_content', '#arabic_text_content']);
    });
</script>
