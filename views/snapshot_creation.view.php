<?php
/**
 * Admin page section field view
 *
 * @package   Snapshot\StaticSnapshot
 * @author    Anthony Allen
 * @copyright 2018 Snapshot
 * @license   GPL-2.0+
 */
?>


<div class="input-group">
  <input id="snapshot-name" type="text" placeholder="unique name">
</div>
<br>
<div class="input-group snapshot-action">
  <input type="button" id="create-snapshot" class="button button-primary" value="Create">
  <div class="loader-inner" style="display: none;">
    <img src="images/wpspin_light-2x.gif" alt="" height="28" width="28">
  </div>
</div>
