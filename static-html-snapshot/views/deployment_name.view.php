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

<?php

        global $wpdb;
        $deploy_path = '';
        $table_name  = 'wp_shs_deploy_path';

        $deployPathResults = $wpdb->get_results(
            'SELECT deploy_path FROM '.$table_name." WHERE id='1' "
        );

        if (count($deployPathResults) > 0) {
            $deploy_path = $deployPathResults[0]->deploy_path;
        }

?>


<div class="input-group">
  <input id="deploy-path" type="text" placeholder="local deploy path" style="min-width: 25%;"
    <?php echo $deploy_path !== '' ? "value='{$deploy_path}'" : ''; ?>>
    <sup>(Writeable absolute path on server)</sup>
</div>
<br>
<div class="input-group">
  <input type="button" id="save-deploy-path" class="button button-primary" value="Save">
</div>
