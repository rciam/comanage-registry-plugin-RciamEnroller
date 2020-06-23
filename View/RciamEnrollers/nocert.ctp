<?php
print $this->Html->css('/RciamEnroller/css/rciam_enroller');
?>
<script type="text/javascript">

</script>
<div class="co-info-topbox">
  <p>
    <div class="ui-icon ui-icon-info co-info"></div>
    <?php
    print $vv_nocert_msg;
    ?>
  </p>
  <div id="connection-test-lbl" class="field-name"></div>
  <div class="field-info">
    <button type='button'
            id='return-btn'
            onclick="window.location.href='<?php print $vv_redirect_final; ?>'"
            class='ui-button ui-corner-all ui-widget'>
      <?php print _txt('pl.rciam_enroller.return'); ?>
    </button>
  </div>
</div>