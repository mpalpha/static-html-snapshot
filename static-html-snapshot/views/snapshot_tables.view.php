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
    $table_name    = 'wp_shs_snapshot';
    $deploy_path   = '';
    $dp_table_name = 'wp_shs_deploy_path';

    $snapshots = $wpdb->get_results(
        'SELECT id, name, creationDate, deployedDate FROM '.$table_name
    );

    $lastDeployed = $wpdb->get_results(
        'SELECT max(deployedDate) deployedDate FROM '.$table_name
    );

    $deployPathResults = $wpdb->get_results(
        'SELECT deploy_path FROM '.$dp_table_name." WHERE id='1' "
    );

    if (count($deployPathResults) > 0) {
        $deploy_path = $deployPathResults[0]->deploy_path;
    }
?>

<div id="available-snapshots" style="<?php echo count($snapshots) == 0 ? 'display: none' : ''; ?>">
    <table class="widefat" cellspacing="0">
        <thead>
            <tr>
                <th scope="col" max-width="50">ID</th>
                <th scope="col" max-width="200">Name</th>
                <th scope="col">Created On</th>
                <th scope="col">Deployed On</th>
                <th scope="col">Download</th>
                <th scope="col" max-width="50">Delete</th>
                <th scope="col">Deploy</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($snapshots as $snapshot) { ?>
                <?php $snapshot_url  = get_site_url().'/'.$snapshot->name.'.tar'; ?>
                <?php $created_date  = explode(' ', $snapshot->creationDate); ?>
                <?php $deployed_date = $snapshot->deployedDate; ?>

                <tr id="<?php echo 'snapshot-'.$snapshot->name; ?>" class="alternate row-actions">
                    <td><?php echo $snapshot->id; ?></td>
                    <td class="snapshot-name"><?php echo $snapshot->name; ?></td>
                    <td><?php echo $created_date[0]; ?></td>
                    <td <?php echo $lastDeployed[0]->deployedDate === $deployed_date ? 'class="snapshot_success"' : ''; ?>>
                        <?php echo $deployed_date !== '' ? $deployed_date : 'Not Deployed'; ?>
                    </td>
                    <td>
                        <a class="font-download" href="<?php echo $snapshot_url; ?>">
                            <span class="dashicons dashicons-download"></span>
                        </a>
                    </td>
                    <td class="delete plugins">
                        <a class="font-delete delete" id="delete-snapshot-<?php echo $snapshot->name; ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                    </td>
                    <td>
                        <button type="button" class="button button-primary deploy-snapshot" value="<?php echo $snapshot->name; ?>"
                            <?php echo count($deployPathResults) == 0 ? 'disabled' : ''; ?>>Deploy
                        </button>
                    </td>
                </tr>
            <?php }//end foreach ?>
        </tbody>
    </table>

    <!-- template -->
    <table style="display: none">
        <tr id="template-row" class="alternate row-actions">
            <td></td>
            <td></td>
            <td></td>
            <td>
                <?php echo $deployed_date[0] !== '' ? $deployed_date[0] : 'Not Deployed'; ?>
            </td>
            <td>
                <a class="font-download" href="<?php echo $snapshot_url; ?>">
                    <span class="dashicons dashicons-download"></span>
                </a>
            </td>
            <td class="delete plugins">
                <a class="font-delete delete wp-ui-text-notification">
                    <span class="dashicons dashicons-trash"></span>
                </a>
            </td>
            <td>
                <button type="button" class="button button-primary deploy-snapshot" value="<?php echo $snapshot->name; ?>"
                    <?php echo count($deployPathResults) == 0 ? 'disabled' : ''; ?>>Deploy
                </button>
            </td>
        </tr>
    </table>

</div>
