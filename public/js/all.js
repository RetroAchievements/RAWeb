function mediaAsset(uri) {
  return window.mediaAssetUrl + '/' + uri.replace(/^\/|\/$/g, '');
}

// global xhr headers
$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
  },
});

// global xhr error handler
$(document).ajaxError(function (event, xhr, settings, thrownError) {
  var message = thrownError;
  try {
    message = JSON.parse(xhr.responseText).message;
  } catch (exception) {
    if (message.length === 0) {
      try {
        var html = $($.parseHTML(xhr.responseText));
        message = html.filter('title').text();
      } catch (exception2) {
        message = 'Unknown error';
      }
    }
  }
  showStatusFailure(message);
});

// global xhr success handler
$(document).ajaxSuccess(function (event, xhr) {
  var message = null;
  try {
    message = JSON.parse(xhr.responseText).message;
  } catch (exception) {
    //
  }
  if (message) {
    showStatusSuccess(message);
  }
});

jQuery(document).ready(function onReady($) {
  $('.msgPayload').hide();

  var $searchUser = $('.searchuser');
  $searchUser.autocomplete({
    source: function (request, response) {
      request.source = 'user';
      $.post('/request/search.php', request).done(function (data) {
        response(data);
      });
    },
    minLength: 2,
  });
  $searchUser.autocomplete({
    select: function (event, ui) {
      var TABKEY = 9;
      if (event.keyCode === TABKEY) {
        $('.searchusericon').attr('src', mediaAsset('/UserPic/' + ui.item.label + '.png'));
      }

      return false;
    },
  });
  $searchUser.on('autocompleteselect', function (event, ui) {
    $searchUser.val(ui.item.label);
    $('.searchusericon').attr('src', mediaAsset('/UserPic/' + ui.item.label + '.png'));

    return false;
  });

  // Add highlights to deep-linked comments.
  const urlHash = window.location.hash;
  if (urlHash.startsWith('#comment_')) {
    const highlightTargetEl =
      document.querySelector(`${urlHash}_highlight`) || document.getElementById(urlHash);
    if (highlightTargetEl) {
      highlightTargetEl.classList.add('highlight');
    }
  }
});

// - used by comment widget, which may exist multiple times on a single page
// eslint-disable-next-line @typescript-eslint/no-unused-vars
function removeComment(artTypeID, artID, commentID) {
  if (!window.confirm('Are you sure you want to permanently delete this comment?')) {
    return false;
  }

  $.post('/request/comment/delete.php', {
    commentable: artID,
    comment: commentID,
  }).done(function () {
    for (const el of document.querySelectorAll(`[id^="comment_${commentID}"]`)) {
      el.style.display = 'none';
    }
  });

  return true;
}

// - used by many pages
// eslint-disable-next-line @typescript-eslint/no-unused-vars
function showStatusMessage(message) {
  var status = $('#status');
  status.removeClass('success');
  status.removeClass('failure');
  status.show();
  status.html(message);
}

function showStatusSuccess(message) {
  var status = $('#status');
  status.addClass('success');
  status.html(message);
  status.show();
  status.delay(2000).fadeOut();
}

function showStatusFailure(message) {
  const status = document.getElementById('status');
  if (status && message) {
    status.classList.add('failure');
    status.innerHTML = message;
    status.style.display = 'block';
  } else if (!message) {
    console.trace();
  }
}

/**
 * @param {string} buttonName
 * @param {string} contentName
 * @returns {void}
 */
// eslint-disable-next-line @typescript-eslint/no-unused-vars -- used by several pages
function toggleExpander(buttonName, contentName) {
  const buttonEl = document.getElementById(buttonName);
  const contentEl = document.getElementById(contentName);
  if (contentEl && buttonEl) {
    contentEl.classList.toggle('hidden');
    buttonEl.innerHTML =
      buttonEl.innerText.substring(0, buttonEl.innerText.length - 1) +
      (contentEl.classList.contains('hidden') ? '▼' : '▲');
  }
}
