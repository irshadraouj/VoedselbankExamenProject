<div class="box">
    <h1><?=$title_page?></h1>
    <div class="txt-wrap">
        <?=form_open($form_url)?>

        <input type="hidden" name="confirm" value="ok"/>

        <p><strong><?=lang(WYVERN_MAP.'_delete')?></strong></p>
        <p class="notice"><?=lang(WYVERN_MAP.'_delete_notice')?></p>

        <input type="submit" class="btn" value="<?=lang('delete')?>" name="submit">
        </p>
        </form>
    </div>
</div>