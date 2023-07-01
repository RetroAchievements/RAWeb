function asset(uri) {
  return window.assetUrl + '/' + uri.replace(/^\/|\/$/g, '');
}

function mediaAsset(uri) {
  return window.mediaAssetUrl + '/' + uri.replace(/^\/|\/$/g, '');
}

// global xhr headers
$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  }
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

var shortMonths = [
  'Jan',
  'Feb',
  'Mar',
  'Apr',
  'May',
  'Jun',
  'Jul',
  'Aug',
  'Sep',
  'Oct',
  'Nov',
  'Dec'];

function htmlEntities(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/**
 * Pads a number with 0s
 */
function strPad(input, padLength, padString) {
  input += '';
  if (input.length >= width) {
    return input;
  }
  padString = padString || '0';
  return new Array(padLength - input.length + 1).join(padString) + input;
}

function getParameterByName(name) {
  name = name.replace(/[\[]/, '\\\[').replace(/[\]]/, '\\\]');
  var regexS = '[\\?&]' + name + '=([^&#]*)';
  var regex = new RegExp(regexS);
  var results = regex.exec(window.location.search);
  if (results == null) {
    return '';
  }
  return decodeURIComponent(results[1].replace(/\+/g, ' '));
}

function focusOnArticleID(id) {
  $('#art_' + id).scrollIntoView();
}

var cardsCache = {};

function useCard(type, id, context = null, html = '') {
  var cardId = `tooltip_card_${type}_${id}`;

  if (context) {
    cardId += `_${context}`;
  }

  if (cardsCache[cardId]) {
    return cardsCache[cardId];
  }

  cardsCache[cardId] = html;

  return html;
}

function loadCard(target, type, id, context = null) {
  var cardId = `tooltip_card_${type}_${id}`;

  if (context) {
    cardId += `_${context}`;
  }

  if (cardsCache[cardId]) {
    return cardsCache[cardId];
  }

  // delay requesting the tooltip for 200ms in case the mouse is just passing over the avatar
  timeoutObject = setTimeout(function () {
    $(target).off('mouseleave');
    $.post('/request/card.php', {
      type: type,
      id: id,
      context: context,
    })
      .done(function (data) {
        cardsCache[cardId] = data.html;
        $(`#${cardId}_yield`).html(data.html);
      });
  }, 200);
  $(target).mouseleave(function () {
    $(target).off('mouseleave');
    clearTimeout(timeoutObject);
  });

  return `<div id="${cardId}_yield">
    <div class="flex justify-center items-center" style="width: 30px; height: 30px">
        <img class="m-5" src="${asset('assets/images/icon/loading.gif')}" alt="Loading">
    </div>
  </div>`;
}

function UpdateMailboxCount(messageCount) {
  $('#mailboxicon').attr('src', messageCount > 0 ? asset('/assets/images/icon/mail-unread.png') : asset('/assets/images/icon/mail.png'));
  $('#mailboxcount').html(messageCount);
}

jQuery(document).ready(function onReady($) {
  $('.msgPayload').hide();

  $('.searchboxinput').each(function () {
    // eslint-disable-next-line no-underscore-dangle
    $(this).autocomplete({
      source: function (request, response) {
        $.post('/request/search.php', request)
          .done(function (data) {
            response(data);
          });
      },
      minLength: 2,
      select: function (event) {
        // This is required by jQuery UI Autocomplete. Unfortunately we can't
        // just rely on the browser's native behavior for dealing with anchor tags.
        event.preventDefault();
        window.location = event.srcElement.href;
      }
    }).data('autocomplete')._renderItem = function (ul, item) {
      const li = $('<li>');
      const a = $('<a>', {
        text: item.label,
        href: item.mylink,
      });

      return li.append(a).appendTo(ul);
    };
  });

  var $seachBoxCompareUser = $('.searchboxgamecompareuser');
  $seachBoxCompareUser.autocomplete({
    source: function (request, response) {
      request.source = 'game-compare';
      $.post('/request/search.php', request)
        .done(function (data) {
          response(data);
        });
    },
    minLength: 2
  });
  $seachBoxCompareUser.autocomplete({
    select: function (event, ui) {
      return false;
    },
  });
  $seachBoxCompareUser.on('autocompleteselect', function (event, ui) {
    var gameID = getParameterByName('ID');
    if (window.location.pathname.substring(0, 6) === '/game/') {
      gameID = window.location.pathname.substring(6);
    }
    window.location = '/gamecompare.php?ID=' + gameID + '&f=' + ui.item.label;
    return false;
  });

  var $searchUser = $('.searchuser');
  $searchUser.autocomplete({
    source: function (request, response) {
      request.source = 'user';
      $.post('/request/search.php', request)
        .done(function (data) {
          response(data);
        });
    },
    minLength: 2
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

  var $anchor = window.location.hash;
  if ($anchor.startsWith('#comment_')) {
    $($anchor).addClass('highlight');
  }
});

$(function () {
  function repeatFade($element, delay, duration) {
    $element.delay(delay).fadeToggle(duration, function () {
      repeatFade($element, delay, duration);
    });
  }

  repeatFade($('.trophyimageincomplete'), 200, 300);
});

function removeComment(artTypeID, artID, commentID) {
  if (!window.confirm('Are you sure you want to permanently delete this comment?')) {
    return false;
  }

  $.post('/request/comment/delete.php', {
    commentable: artID,
    comment: commentID
  })
    .done(function () {
      $('#comment_' + commentID).hide();
    });
  return true;
}

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

function hideStatusMessage() {
  $('#status').hide();
}

function initializeTextareaCounter() {
  var textareaCounters = document.getElementsByClassName('textarea-counter');
  for (var i = 0; i < textareaCounters.length; i++) {
    var textareaCounter = textareaCounters[i];
    var textareaId = textareaCounter.dataset.textareaId;
    var textarea = document.getElementById(textareaId);
    var max = textarea.getAttribute('maxlength');

    if (max) {
      var updateCount = function () {
        var count = textarea.value.length;
        textareaCounter.textContent = count + ' / ' + max;
        textareaCounter.classList.toggle('text-danger', count >= max);
      };
      ['keydown', 'keypress', 'keyup', 'blur'].forEach(function (eventName) {
        textarea.addEventListener(eventName, updateCount);
      });
      updateCount();
    }
  }
}

window.addEventListener('load', initializeTextareaCounter);
