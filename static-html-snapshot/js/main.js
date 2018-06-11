/**
 * @author Jason Lusk, Anthony Allen
 *
 * @summary Defines ajax calls for generating static html site and deploying to saved
 * deployment path.
 *
 * @since 1.0.0
 *
 * @param {jQuery} $ The jQuery object.
 */
jQuery(function($) {
  var $errorOutput = $('#snapshot-plugin .output.error');
  var $successOutput = $('#snapshot-plugin .output.success');
  $('#create-snapshot', '#snapshot-plugin').on('click', create_snapshot);
  $('[id^=delete-snapshot-]', '#snapshot-plugin').on('click', delete_snapshot);
  $('#save-deploy-path', '#snapshot-plugin').on('click', save_deploy_path);
  $('.deploy-snapshot', '#snapshot-plugin').on('click', deploy_snapshot);

  /**
   * toggle loader
   */
  function toggle_loader(toggleLoader) {
    var $loader = $('.loader-inner', '#snapshot-plugin');
    $loader.css('display', (!!toggleLoader && 'inline') || 'none');
  }

  /**
   * Create a new static html snapshot.
   */
  function create_snapshot() {
    var data = {
      action: 'create_snapshot',
      name: $('#snapshot-name').val()
    };
    $errorOutput.hide();
    $successOutput.hide();

    if (data.name.length === 0) {
      $errorOutput.text('Snapshot name is required');
      $errorOutput.show();
      return;
    }

    if (data.name.length > 200) {
      $errorOutput.text('Snapshot name is too long!');
      $errorOutput.show();
      return;
    }

    toggle_loader(true);

    $.ajax({
      url: website_snapshot.ajax_url,
      type: 'post',
      timeout: 30000,
      dataType: 'json',
      data: data,
      headers: {
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        Pragma: 'no-cache',
        Expires: '0'
      },
      cache: false,
      success: function(response, status, xhr) {
        if (response.type === 'Success') {
          var snapshot = response[0].snapshot;
          var rowId = 'snapshot-' + snapshot.name;
          $successOutput.text(response[0].message);
          $successOutput.show();

          $('#template-row')
            .clone()
            .prop('id', rowId)
            .appendTo('#available-snapshots > table')
            .show();
          $('#' + rowId + ' td:nth-child(1)').html(snapshot.id);
          $('#' + rowId + ' td:nth-child(2)').html(snapshot.name);
          $('#' + rowId + ' td:nth-child(3)').html(
            snapshot.creationDate.split(' ')[0]
          );
          $('#' + rowId + ' td:nth-child(4)').html();
          $('#' + rowId + ' td:nth-child(4) a')
            .prop('href', snapshot.snapshot_url)
            .on('click', delete_snapshot);
          $('#' + rowId + ' td:nth-child(5) a')
            .prop('id', 'delete-snapshot-' + snapshot.name)
            .on('click', delete_snapshot);
          $('#available-snapshots').show();

          toggle_loader(false);
          window.location.reload();
        } else {
          $errorOutput.text(response[0].message);
          $errorOutput.show();
          var consoleResponse = status + ' - ' + xhr;
          console.log(response);
          console.log(consoleResponse);
          toggle_loader(false);
        }
      },
      error: function(response, status, xhr) {
        if (response.responseJSON) {
          $errorOutput.text(response.responseJSON[0].message);
          $errorOutput.show();
        } else {
          $errorOutput.text('An error has occurred.');
          $errorOutput.show();
        }
        var consoleResponse = status + ' - ' + xhr;
        console.log(response);
        console.log(consoleResponse);
        toggle_loader(false);
      }
    });
  }

  /**
   * Delete snapshot from db and the associated tar files.
   */
  function delete_snapshot() {
    var data = {
      action: 'delete_snapshot',
      name: this.id.split('-snapshot-')[1]
    };

    $errorOutput.hide();
    $successOutput.hide();

    $.ajax({
      url: website_snapshot.ajax_url,
      type: 'post',
      timeout: 30000,
      dataType: 'json',
      data: data,
      success: function(response, status, xhr) {
        if (response.type === 'Success') {
          $('#snapshot-' + data.name).remove();
          if ($('#available-snapshots > table tr > td').length < 1) {
            $('#available-snapshots').hide();
          }
          $successOutput.text(response[0].message);
          $successOutput.show();
        } else {
          $errorOutput.text(response[0].message);
          $errorOutput.show();
          var consoleResponse = status + ' - ' + xhr;
          console.log(response);
          console.log(consoleResponse);
        }
      },
      error: function(response, status, xhr) {
        if (response.responseJSON) {
          $errorOutput.text(response.responseJSON[0].message);
          $errorOutput.show();
        } else {
          $errorOutput.text('An error has occurred.');
          $errorOutput.show();
        }
        var consoleResponse = status + ' - ' + xhr;
        console.log(response);
        console.log(consoleResponse);
      }
    });
  }

  /**
   * Save deploy path to database.
   */
  function save_deploy_path() {
    var data = {
      action: 'save_deploy_path',
      deploy_path: $('#deploy-path').val()
    };
    $errorOutput.hide();
    $successOutput.hide();

    if (data.deploy_path.length === 0) {
      $errorOutput.text('Snapshot path name is required');
      $errorOutput.show();
      return;
    }

    if (data.deploy_path.length > 200) {
      $errorOutput.text('Snapshot path name is too long!');
      $errorOutput.show();
      return;
    }

    $.ajax({
      url: website_snapshot.ajax_url,
      type: 'post',
      timeout: 30000,
      dataType: 'json',
      data: data,
      headers: {
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        Pragma: 'no-cache',
        Expires: '0'
      },
      cache: false,
      success: function(response, status, xhr) {
        if (response.type === 'Success') {
          var snapshot = response[0].snapshot;
          $successOutput.text(response[0].message);
          $successOutput.show();
        } else {
          $errorOutput.text(response[0].message);
          $errorOutput.show();
          var consoleResponse = status + ' - ' + xhr;
          console.log(response);
          console.log(consoleResponse);
        }
      },
      error: function(response, status, xhr) {
        if (response.responseJSON) {
          $errorOutput.text(response.responseJSON[0].message);
          $errorOutput.show();
        } else {
          $errorOutput.text('An error has occurred.');
          $errorOutput.show();
        }
        var consoleResponse = status + ' - ' + xhr;
        console.log(response);
        console.log(consoleResponse);
      }
    });
  }

  /**
   * Deploy snapshot to path defined in db.
   * @param {*} e
   */
  function deploy_snapshot(e) {
    var data = {
      action: 'deploy_snapshot',
      deploy_path: $('#deploy-path').val(),
      snapshot_name: $(e.currentTarget).val()
    };
    $errorOutput.hide();
    $successOutput.hide();

    $.ajax({
      url: website_snapshot.ajax_url,
      type: 'post',
      timeout: 30000,
      dataType: 'json',
      data: data,
      headers: {
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        Pragma: 'no-cache',
        Expires: '0'
      },
      cache: false,
      success: function(response, status, xhr) {
        if (response.type === 'Success') {
          var deployed = response[0].deployed;
          var snapshotName = response[0].snapshot_name;
          var rowId = 'snapshot-' + snapshotName;

          $('td').removeClass('snapshot_success');
          $('#' + rowId + ' td:nth-child(4)')
            .addClass('snapshot_success')
            .html(deployed);

          $successOutput.text(response[0].message);
          $successOutput.show();
        } else {
          $errorOutput.text(response[0].message);
          $errorOutput.show();
          var consoleResponse = status + ' - ' + xhr;
          console.log(response);
          console.log(consoleResponse);
        }
      },
      error: function(response, status, xhr) {
        if (response.responseJSON) {
          $errorOutput.text(response.responseJSON[0].message);
          $errorOutput.show();
        } else {
          $errorOutput.text('An error has occurred.');
          $errorOutput.show();
        }
        var consoleResponse = status + ' - ' + xhr;
        console.log(response);
        console.log(consoleResponse);
      }
    });
  }
});
