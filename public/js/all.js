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
    //
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

function GetTooltipDiv(icon, header, body) {
  var tooltipImageSize = 64;
  var tooltip = '<div id=\'objtooltip\' class=\'flex items-start\' style=\'max-width: 400px;\'>'
    + '<img style=\'margin-right:5px\' src=\'' + icon + '\' width=\'' + tooltipImageSize + '\' height=\'' + tooltipImageSize + '\' />'
    + '<div><b>' + header + '</b><br><span style=\'white-space: nowrap\'>' + body + '</span></div></div>';
  tooltip = replaceAll('<', '&lt;', tooltip);
  tooltip = replaceAll('>', '&gt;', tooltip);
  tooltip = replaceAll('\'', '\\\'', tooltip);
  tooltip = replaceAll('"', '&quot;', tooltip);
  return tooltip;
}

function GetGameAndTooltipDiv(gameID, gameTitle, gameIcon, consoleName, imageInstead) {
  var consoleStr = '(' + consoleName + ')';
  var tooltip = GetTooltipDiv(gameIcon, gameTitle, consoleStr);
  var displayable = gameTitle + ' ' + consoleStr;
  if (imageInstead) {
    displayable = '<img alt="started playing ' + gameTitle
      + '" title="Started playing ' + gameTitle + '" src=\'' + gameIcon
      + '\' width=\'32\' height=\'32\' class=\'badgeimg\' loading=\'lazy\' />';
  }
  return '<div class=\'inline\' onmouseover="Tip(\'' + tooltip
    + '\')" onmouseout="UnTip()" >'
    + '<a href=\'/game/' + gameID.toString() + '\'>'
    + displayable
    + '</a>'
    + '</div>';
}

function GetUserAndTooltipDiv(user, hardcorePoints, softcorePoints, imageInstead, extraText) {
  if (hardcorePoints > softcorePoints) {
    points = 'Points: ' + hardcorePoints;
  } else if (softcorePoints > 0) {
    points = 'Softcore Points: ' + softcorePoints;
  } else {
    points = 'Points: 0';
  }

  var tooltip = GetTooltipDiv('/UserPic/' + user + '.png', user, points);
  var displayable = user;
  if (imageInstead) {
    displayable = '<img src=\'/UserPic/' + user
      + '.png\' width=\'32\' height=\'32\' alt=\'' + user + '\' title=\'' + user
      + '\' class=\'badgeimg\' loading=\'lazy\' />';
  }
  return '<div class=\'inline\' onmouseover="Tip(\'' + tooltip
    + '\')" onmouseout="UnTip()" >'
    + '<a href=\'/user/' + user + '\'>'
    + displayable
    + '</a>'
    + '</div>';
}

function UpdateMailboxCount(messageCount) {
  $('#mailboxicon').attr('src', messageCount > 0 ? '/assets/images/icon/mail-unread.png' : '/assets/images/icon/mail.png');
  $('#mailboxcount').html(messageCount);
}

function reloadTwitchContainer(videoID) {
  var vidHTML = '<iframe src="https://player.twitch.tv/?channel=retroachievementsorg" height="500" width="100%" frameborder="0" scrolling="no" allowfullscreen="true"></iframe>';
  console.log(videoID);
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
    window.location = '/gamecompare.php?ID=' + gameID + '&f=' + ui.item.id;
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
        $('.searchusericon').attr('src', '/UserPic/' + ui.item.id + '.png');
      }
      return false;
    },
  });
  $searchUser.on('autocompleteselect', function (event, ui) {
    $searchUser.val(ui.item.id);
    $('.searchusericon').attr('src', '/UserPic/' + ui.item.id + '.png');
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
  if (!window.confirm('Ary you sure you want to permanently delete this comment?')) {
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
