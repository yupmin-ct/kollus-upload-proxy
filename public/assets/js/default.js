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

  if ('id' in options) {
    alertDiv.attr('id', options.id);
  }

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
        if ('message' in data) {
          showAlert('success', data.message);
        }

        document.location.reload();
      },
      error: function (jqXHR, textStatus, errorThrown) {
        data = jqXHR.length === 0 ? textStatus : $.parseJSON(jqXHR.responseText);

        showAlert('danger', ('message' in data ? data.message : 'Ajax response error.'));
      },
      complete: function () {
        $(self).button('reset');
      }
    });
  }
});

/**
 * bootstrap popover on.
 */
$(function () {
  $('[data-toggle="popover"]').popover()
})
