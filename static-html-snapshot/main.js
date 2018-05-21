jQuery(function ($) {
  var $errorOutput = $('#snapshot-plugin .output.error');

  /**
   * toggle loader
   */
  function toggle_loader(toggleLoader) {
    var $loader = $('.loader-inner', '#snapshot-plugin');
    $loader.css(
      'display',
      (!!toggleLoader) && 'inline' || 'none'
    );
  }

  /**
   * create new snapshot
   */
  function create_snapshot() {
    var data = {
      action: 'add_snapshot',
      name: $('#snapshot-name').val()
    };
    $errorOutput.hide();

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
      timeout: 30000, // throw an error if not completed after 30 sec.
      dataType: 'json',
      data: data,
      headers: {
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        Pragma: 'no-cache',
        Expires: '0'
      },
      cache: false,
      success: function (response, status, xhr) {
        if (response.snapshot) {
          var snapshot = response.snapshot;
          var rowId = 'snapshot-' + snapshot.name;
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
          $('#' + rowId + ' td:nth-child(4) a')
            .prop('href', response.snapshot_url)
            .on('click', delete_snapshot);
          $('#' + rowId + ' td:nth-child(5) a')
            .prop('id', 'delete-snapshot-' + snapshot.name)
            .on('click', delete_snapshot);

          $('#available-snapshots').show();
          toggle_loader(false);
        } else {
          console.log({ response, status, xhr })
          $errorOutput.text(xhr.responseJSON.message);
          $errorOutput.show();
          toggle_loader(false);
        }  
      },
      fail: function (response, status, xhr) {
        if (Object.hasOwnProperty('responseJSON')) {
          $errorOutput.text(response.responseJSON.message);
          $errorOutput.show();
        } else if (
          Object.hasOwnProperty('statusText') &&
          Object.hasOwnProperty('status') &&
          Object.status == 504 &&
          Object.statusText == 'Gateway Time-out'
        ) {
          clear_output(10);
          window.location.reload;
        }
      },
      always: function (response, status, xhr) {
        toggle_loader(false);
      }
    });
  }

  $('#create-snapshot', '#snapshot-plugin').on('click', create_snapshot);

  /**
   * delete snapshot
   */
  function delete_snapshot() {
    var data = {
      action: 'delete_snapshot',
      name: this.id.split('-snapshot-')[1]
    };
    $.ajax({
      url: website_snapshot.ajax_url,
      type: 'post',
      timeout: 10000, // throw an error if not completed after 30 sec.
      dataType: 'json',
      data: data,
      success: function (response, status, xhr) {
        console.log(response);
        $('#snapshot-' + data.name).remove();
        if ($('#available-snapshots > table tr > td').length < 1) {
          $('#available-snapshots').hide();
        }
      },
      fail: function (response, status, xhr) {
        $errorOutput.text(response.responseJSON.message);
        $errorOutput.show();
      },
      always: function (response, status, xhr) {
        toggle_loader(false);
      }
    });
  }

  $('[id^=delete-snapshot-]', '#snapshot-plugin').on('click', delete_snapshot);
});
