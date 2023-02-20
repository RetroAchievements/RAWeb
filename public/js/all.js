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

function injectShortcode(start, end) {
  var commentTextarea = document.getElementById('commentTextarea');
  if (commentTextarea !== undefined) {
    // Something's selected: wrap it
    var startPos = commentTextarea.selectionStart;
    var endPos = commentTextarea.selectionEnd;
    var selectedText = commentTextarea.value.substring(startPos, endPos);

    var textBeforeSelection = commentTextarea.value.substr(0, commentTextarea.selectionStart);
    var textAfterSelection = commentTextarea.value.substr(
      commentTextarea.selectionEnd,
      commentTextarea.value.length
    );
    commentTextarea.value = textBeforeSelection + start + selectedText + end + textAfterSelection;
  } else {
    // Nothing selected, just inject at the end of the message
    commentTextarea.value += start;
    commentTextarea.value += ' ';
    commentTextarea.value += end;
  }

  commentTextarea.focus();
}

function replaceAll(find, replace, str) {
  return str.replace(new RegExp(find, 'g'), replace);
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

function reloadTwitchContainer(videoID) {
  var vidHTML = '<iframe src="https://player.twitch.tv/?channel=retroachievementsorg" height="500" width="100%" frameborder="0" scrolling="no" allowfullscreen="true"></iframe>';
  if (videoID && archiveURLs[videoID]) {
    var vidTitle = archiveTitles[videoID];
    var vidURL = archiveURLs[videoID];
    vidURL = vidURL.split('/');
    vidHTML = '<iframe src="https://player.twitch.tv/?video='
      + vidURL[vidURL.length - 1]
      + '" height="500" width="100%" frameborder="0" scrolling="no" allowfullscreen="true">'
      + '</iframe>';
  }
  $('.streamvid').html(vidHTML);
}

jQuery(document).ready(function onReady($) {
  $('.msgPayload').hide();

  var $searchBoxInput = $('.searchboxinput');
  $searchBoxInput.autocomplete({
    source: function (request, response) {
      $.post('/request/search.php', request)
        .done(function (data) {
          response(data);
        });
    },
    minLength: 2
  });
  $searchBoxInput.autocomplete({
    select: function (event, ui) {
      return false;
    },
  });
  $searchBoxInput.on('autocompleteselect', function (event, ui) {
    window.location = ui.item.mylink;
    return false;
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
      $('#artcomment_' + artTypeID + '_' + artID + '_' + commentID).hide();
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
  var status = $('#status');
  status.addClass('failure');
  status.html(message);
  status.show();
}

function hideStatusMessage() {
  $('#status').hide();
}

function tabClick(evt, tabName, type) {
  // Declare all variables
  var i;
  var tabcontent;
  var tablinks;

  // Get all elements with class="tabcontent" and hide them
  tabcontent = document.getElementsByClassName('tabcontent'.concat(type));
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = 'none';
  }

  // Get all elements with class="tablinks" and remove the class "active"
  tablinks = document.getElementsByClassName(type);
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(' active', '');
  }

  // Show the current tab, and add an "active" class to the button that opened the tab
  document.getElementById(tabName).style.display = 'block';
  evt.currentTarget.className += ' active';
}

function copy(text) {
  var inp = document.createElement('input');
  document.body.appendChild(inp);
  inp.value = text;
  inp.select();
  document.execCommand('copy', false);
  inp.remove();
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

/**
 * Creates a throttled version of a function that can be called at most
 * once per `waitMs` milliseconds.
 *
 * @param {Function} fn - The function to throttle.
 * @param {number} waitMs - The number of milliseconds to wait before allowing the function to be called again.
 * @returns {Function} A throttled version of the original function.
 */
function throttle(fn, waitMs) {
  let isThrottled = false;

  return (...args) => {
    if (!isThrottled) {
      isThrottled = true;

      setTimeout(() => {
        fn(...args);
        isThrottled = false;
      }, waitMs);
    }
  };
}

/**
 * @type Record<string, Element>
 */
const cachedAwardsExpandButtons = {};

/**
 * @param {Event} event
 * @param {string} expandButtonId - The ID for the expand button of
 * this awards section.
 *
 * Based on the user's scroll position, we will adjust the "shadow"
 * on the top and bottom of the awards container div. This provides
 * a helpful contextual clue that scrolling is available in the given
 * direction.
 */
const onAwardsScroll = (event, expandButtonId) => {
  const awards = event.target;
  const minimumContainerScrollPosition = awards.offsetHeight;
  const userCurrentScrollPosition = awards.scrollTop + awards.offsetHeight;

  const newTopFadeOpacity = 1.0 - Math.min((userCurrentScrollPosition - minimumContainerScrollPosition) / 120, 1.0);
  const newBottomFadeOpacity = 1.0 - Math.min((awards.scrollHeight - userCurrentScrollPosition) / 120, 1.0);

  // It is better to not be constantly querying the whole DOM tree for this
  // element. Generally, the tree is going to be huge if this is needed
  // in the first place.
  let expandButton = cachedAwardsExpandButtons[expandButtonId];
  if (!expandButton) {
    cachedAwardsExpandButtons[expandButtonId] = document.getElementById(expandButtonId);
    expandButton = cachedAwardsExpandButtons[expandButtonId];
  }

  if (userCurrentScrollPosition >= awards.scrollHeight - 100) {
    expandButton.style.opacity = 0;
  } else {
    expandButton.style.opacity = 100;
  }

  // When the button is clicked, it is removed from the DOM.
  // We don't want the fade to ever be applied if all awards are in view.
  if (expandButton) {
    const opacityGradient = `linear-gradient(
      to bottom,
      rgba(0, 0, 0, ${newTopFadeOpacity}),
      rgba(0, 0, 0, 1) 120px calc(100% - 120px),
      rgba(0, 0, 0, ${newBottomFadeOpacity})
    )`;
    awards.style['-webkit-mask-image'] = opacityGradient;
    awards.style['mask-image'] = opacityGradient;
  }
};
window.handleAwardsScroll = throttle(onAwardsScroll, 25);

/**
 * @param {Event} event
 * When executed, the entire awards div is displayed at full
 * height with no scrolling.
 */
function showFullAwards(event) {
  const button = event.target;
  const awards = button.parentElement.querySelector('.component');

  awards.style['max-height'] = '100000px';
  awards.style['mask-image'] = '';
  awards.style['-webkit-mask-image'] = '';

  awards.classList.remove('awards-fade');

  delete cachedAwardsExpandButtons[event.target.id];
  button.remove();
}

/**
 * @param {string} awardsContainerId
 * @param {string} awardsExpandButtonId
 * @param {string} awardsFadeClassName
 * Determines whether to apply the awards group fade and show the
 * expand button based on the difference between the container's true
 * height and the rendered height in the user's browser. This executes
 * after an optimistic check for this runs on the server. This follow-up
 * check gives us greater precision on when to show the expand and fade.
 */
function shouldApplyAwardsGroupFade(
  awardsContainerId,
  awardsExpandButtonId,
  awardsFadeClassName
) {
  const awardsContainerEl = document.getElementById(awardsContainerId);
  const awardsExpandButtonEl = document.getElementById(awardsExpandButtonId);

  const renderedContainerHeight = awardsContainerEl.clientHeight;
  const trueContainerHeight = awardsContainerEl.scrollHeight;

  if (renderedContainerHeight < trueContainerHeight) {
    awardsContainerEl.classList.add(awardsFadeClassName);
    awardsExpandButtonEl.classList.remove('hidden');
  } else {
    awardsContainerEl.classList.remove(awardsFadeClassName);
    awardsExpandButtonEl.classList.add('hidden');
  }
}
