<div id="toolbar-preset" class="cke_ltr">
    <?php foreach ($selected as $button): ?>
        <a title="<?php echo $button[0] ?>" class="cke_button button__<?php echo $button[0] ?>_icon" data-icon="<?php echo $button[1]?>">
            <span class="delete"></span>
            <span class="cke_button_icon cke_button__<?php echo $button[0] ?>_icon">&nbsp;</span>
        </a>
    <?php endforeach; ?>
</div>
