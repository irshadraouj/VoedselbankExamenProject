<div class="box">

    <div class="tbl-ctrls">

        <h1><?=$title_page?></h1>
        <section class="item-wrap log">

            <div class="item">
                <h3>
                    <b>Send date:</b> <?=ee()->localize->human_time($result->send_date)?>,
                    <?php if($result->member_id != 0):?><b>Username:</b> <a href="index.php?/cp/myaccount&amp;id=<?=$result->member_id?>"><?=$result->getMemberName()?></a>,<?php endif;?>
                    <?php if($result->cc != ''):?><b>cc:</b> <?=str_replace('|', ', ', $result->cc)?>,<?php endif;?>
                    <?php if($result->bcc != ''):?><b>bcc:</b><?=str_replace('|', ', ', $result->bcc)?>,<?php endif;?>
                    <b><abbr title="Internet Protocol">IP</abbr>:</b> <?=$result->ip?>
                </h3>
                <div class="message">
                    <?=$result->content?>
                </div>
            </div>

        </section>
   </div>

</div>