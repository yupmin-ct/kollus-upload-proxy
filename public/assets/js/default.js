/**
 * Show Instant Message.
 *
 * @param {string} type - success|info|warning|danger
 * @param {string} message
 * @param {object|null} options
 */
function showAlert(type, message, options) {
  var alertDiv = $('<div class="alert alert-' + type + '">' +
      '<button type="button" class="close" data-dismiss="alert">' +
      '&times;</button>' + message + '</div>'),
    delayDuration = 5000,
    slideUpDuration = 200;

  options = options || {};

  if ('delayDuration' in options) {
    delayDuration = options.delayDuration;
  }

  if ('slideUpDuration' in options) {
    slideUpDuration = options.slideUpDuration;
  }

  if (delayDuration !== 0 && slideUpDuration !== 0) {
    alertDiv.delay(delayDuration).slideUp(slideUpDuration);
  }

  $('#alert_message').append(alertDiv);
}

/**
 * Action event handler
 */
$(document).on('click', '[data-action=ajax-action]', function(e) {
  e.preventDefault();
  e.stopPropagation();

  var self = this,
    href = $(self).attr('href'),
    method = $(self).attr('data-method'),
    data;

  if (confirm('Are you sure?')) {
    $(self).button('loading');

    $.ajax({
      url: href,
      type: method,
      dataType: 'json',
      success: function (data) {
        var level = 'success';

        if ('data' in data &&
            'partial_url' in data.data) {

          $.get(data.data.partial_url, null, function(data) {

            if ('data' in data &&
              'partials' in data.data) {

              $.each(data.data.partials, function(key, value) {
                $('#'+key).html(value);
              });
            }
          }, 'json');
        } else {
          level = 'danger';
        }

        if ('message' in data) {
          showAlert(level, data.message);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        data = jqXHR.length === 0 ? {message: textStatus} : $.parseJSON(jqXHR.responseText);

        showAlert('danger', ('message' in data ? data.message : 'Ajax response error.'));
      },
      complete: function () {
        $(self).button('reset');
      }
    }); // $.ajax
  } // if
});

/**
 * bootstrap popover on.
 */
$(function () {
  $('[data-toggle="popover"]').popover()
})
