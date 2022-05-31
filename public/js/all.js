function asset(uri) {
  return window.assetUrl + '/' + uri.replace(/^\/|\/$/g, '');
}

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

function readCookie(name) {
  var nameEQ = name + '=';
  var ca = document.cookie.split(';');
  for (var i = 0; i < ca.length; i += 1) {
    var c = ca[i];
    while (c.charAt(0) === ' ') c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
  }
  return null;
}

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

function onSubmitComment(event) {
  event.preventDefault();
  var $form = $(event.currentTarget);
  var $submit = $form.find('.comment-submit-button');
  var $loading = $form.find('.comment-loading-indicator');
  var $error = $form.find('.form-error');

  var input = $form.serializeArray().reduce(function (obj, item) {
    obj[item.name] = item.value;
    return obj;
  }, {});

  // validate
  $error.hide();
  if (input.c.length === 0) {
    $error.show();
    $error.text('Comment is empty');
    return false;
  }

  $submit.hide();
  $loading.show();
  $.post('/request/comment/create.php', input).done(function () {
    window.location.reload();
  });

  return false;
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

function updateDisplayOrder(user, objID, gameID) {
  var inputText = $('#' + objID).val();
  var inputNum = Math.max(0, Math.min(Number(inputText), 10000));
  var posting = $.post(
    '/request/achievement/update.php',
    {
      u: user,
      a: objID.substr(4),
      g: gameID,
      f: 1,
      v: inputNum,
    }
  );
  posting.done(onUpdateDisplayOrderComplete);
  $('#warning').html('Status: updating...');
}

function updateAwardDisplayOrder(awardType, awardData, awardDataExtra, objID) {
  var inputText = $('#' + objID).val();
  var inputNum = Math.max(-1, Math.min(Number(inputText), 10000));
  var posting = $.post(
    '/request/user/update-site-award.php',
    {
      t: awardType,
      d: awardData,
      e: awardDataExtra,
      v: inputNum,
    }
  );
  posting.done(onUpdateDisplayOrderComplete);
  $('#warning').html('Status: updating...');
}

function onUpdateDisplayOrderComplete(data) {
  if (data !== 'OK') {
    $('#warning').html('Status: Errors...' + data);
  } else {
    $('#warning').html('Status: OK!');
  }
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

function GetAchievementAndTooltipDiv(
  achID,
  achName,
  achDesc,
  achPoints,
  gameName,
  badgeName,
  inclSmallBadge,
  smallBadgeOnly
) {
  var tooltipImageSize = 64;
  var tooltip = '<div id=\'objtooltip\'>'
    + '<img src=\'' + asset(`Badge/${badgeName}.png`) + '\' width=' + tooltipImageSize + ' height='
    + tooltipImageSize + ' />'
    + '<b>' + achName + ' (' + achPoints.toString() + ')</b><br>'
    + '<i>(' + gameName + ')</i><br>'
    + '<br>'
    + achDesc + '<br>'
    + '</div>';
  tooltip = replaceAll('<', '&lt;', tooltip);
  tooltip = replaceAll('>', '&gt;', tooltip);
  tooltip = replaceAll('\'', '\\\'', tooltip);
  tooltip = replaceAll('"', '&quot;', tooltip);
  var smallBadge = '';
  var displayable = achName + ' (' + achPoints.toString() + ')';
  if (inclSmallBadge) {
    var smallBadgePath = asset(`Badge/${badgeName}.png`);
    smallBadge = '<img width=\'32\' height=\'32\' style=\'floatimg\' src=\''
      + smallBadgePath + '\' alt="' + achName + '" title="' + achName
      + '" class=\'badgeimg\' />';
    if (smallBadgeOnly) {
      displayable = '';
    }
  }

  return '<div class=\'bb_inline\' onmouseover="Tip(\'' + tooltip
    + '\')" onmouseout="UnTip()" >'
    + '<a href=\'/achievement/' + achID + '\'>'
    + smallBadge
    + displayable
    + '</a>'
    + '</div>';
}

function GetGameAndTooltipDiv(gameID, gameTitle, gameIcon, consoleName, imageInstead) {
  var tooltipImageSize = 64;
  var consoleStr = '(' + consoleName + ')';
  var tooltip = '<div id=\'objtooltip\'>'
    + '<img src=\'' + gameIcon + '\' width=\'' + tooltipImageSize
    + '\' height=\'' + tooltipImageSize + '\' />'
    + '<b>' + gameTitle + '</b><br>'
    + consoleStr
    + '</div>';
  tooltip = replaceAll('<', '&lt;', tooltip);
  tooltip = replaceAll('>', '&gt;', tooltip);
  tooltip = replaceAll('\'', '\\\'', tooltip);
  tooltip = replaceAll('"', '&quot;', tooltip);
  var displayable = gameTitle + ' ' + consoleStr;
  if (imageInstead) {
    displayable = '<img alt="started playing ' + gameTitle
      + '" title="Started playing ' + gameTitle + '" src=\'' + gameIcon
      + '\' width=\'32\' height=\'32\' class=\'badgeimg\' loading=\'lazy\' />';
  }
  return '<div class=\'bb_inline\' onmouseover="Tip(\'' + tooltip
    + '\')" onmouseout="UnTip()" >'
    + '<a href=\'/game/' + gameID.toString() + '\'>'
    + displayable
    + '</a>'
    + '</div>';
}

function GetUserAndTooltipDiv(user, points, motto, imageInstead, extraText) {
  var tooltipImageSize = 128;
  var tooltip = '<div id=\'objtooltip\'>';
  tooltip += '<table><tbody>';
  tooltip += '<tr>';
  // Image
  tooltip += '<td class=\'fixedtooltipcolleft\'><img src=\'/UserPic/' + user
    + '.png\' width=\'' + tooltipImageSize + '\' height=\'' + tooltipImageSize
    + '\' /></td>';
  // Username (points)
  tooltip += '<td class=\'fixedtooltipcolright\'>';
  tooltip += '<b>' + user + '</b>';
  if (points !== null) {
    tooltip += '&nbsp;(' + points.toString() + ')';
  }
  // Motto
  if (motto && motto.length > 2) {
    tooltip += '<br><span class=\'usermotto\'>' + motto + '</span>';
  }
  if (extraText.length > 0) {
    tooltip += extraText;
  }
  tooltip += '</td>';
  tooltip += '</tr>';
  tooltip += '</tbody></table>';
  tooltip += '</div>';
  // tooltip = escapeHtml( tooltip );
  tooltip = replaceAll('<', '&lt;', tooltip);
  tooltip = replaceAll('>', '&gt;', tooltip);
  tooltip = replaceAll('\'', '\\\'', tooltip); // &#039;
  tooltip = replaceAll('"', '&quot;', tooltip);
  var displayable = user;
  if (imageInstead) {
    displayable = '<img src=\'/UserPic/' + user
      + '.png\' width=\'32\' height=\'32\' alt=\'' + user + '\' title=\'' + user
      + '\' class=\'badgeimg\' loading=\'lazy\' />';
  }
  return '<div class=\'bb_inline\' onmouseover="Tip(\'' + tooltip
    + '\')" onmouseout="UnTip()" >'
    + '<a href=\'/user/' + user + '\'>'
    + displayable
    + '</a>'
    + '</div>';
}

function GetLeaderboardAndTooltipDiv(
  lbID,
  lbName,
  lbDesc,
  gameName,
  gameIcon,
  displayable
) {
  var tooltipImageSize = 64;
  var tooltip = '<div id=\'objtooltip\'>'
    + '<img src=\'' + gameIcon + '\' width=\'' + tooltipImageSize
    + '\' height=\'' + tooltipImageSize + '\' />'
    + '<b>' + lbName + '</b><br>'
    + '<i>(' + gameName + ')</i><br>'
    + '<br>'
    + lbDesc + '<br>'
    + '</div>';
  tooltip = replaceAll('<', '&lt;', tooltip);
  tooltip = replaceAll('>', '&gt;', tooltip);
  tooltip = replaceAll('\'', '\\\'', tooltip);
  tooltip = replaceAll('"', '&quot;', tooltip);
  return '<div class=\'bb_inline\' onmouseover="Tip(\'' + tooltip
    + '\')" onmouseout="UnTip()" >'
    + '<a href=\'/leaderboardinfo.php?i=' + lbID + '\'>'
    + displayable
    + '</a>'
    + '</div>';
}

function UpdateMailboxCount(messageCount) {
  $('#mailboxicon').attr('src', messageCount > 0 ? '/Images/_MailUnread.png' : '/Images/_Mail.png');
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
  $searchBoxInput.autocomplete({ source: '/request/search.php', minLength: 2 });
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
  $seachBoxCompareUser.autocomplete({ source: '/request/search.php?p=gamecompare', minLength: 2 });
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
  $searchUser.autocomplete({ source: '/request/search.php?p=user', minLength: 2 });
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

  var $resetForm = $('#resetform');
  $resetForm.submit(function () {
    if (!window.confirm('Are you sure you want to reset this progress?')) {
      return false;
    }
    $.post(
      $(this).attr('action'),
      $(this).serialize(),
      setTimeout(function () {
        window.location.reload();
      }, 100),
      'json'
    );
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

  var posting = $.post('/request/comment/delete.php', { a: artID, c: commentID });
  posting.done(function (data) {
    var result = $.parseJSON(data);
    if (result.Success) {
      $('#artcomment_' + artTypeID + '_' + artID + '_' + commentID).hide();
    }
  });
  return true;
}

function ResetTheme() {
  // Unload all themes...
  var allLinks = document.getElementsByTagName('link');
  var numLinks = allLinks.length;
  for (var i = 0; i < numLinks; i += 1) {
    var nextLink = allLinks[i];
    if (nextLink.rel === 'stylesheet') {
      if (nextLink.href.indexOf('css/rac') !== -1) {
        nextLink.disabled = true;
      }
    }
  }

  // Then load the one you selected:
  var cssToLoad = $('#themeselect :selected').val();
  var cssLink = $('<link rel="stylesheet" type="text/css" href="' + cssToLoad + '">');
  $('head').append(cssLink);
  setCookie('RAPrefs_CSS', cssToLoad);
}

function setCookie(cookiename, value) {
  // Finally persist as a cookie
  var days = 30;
  var date = new Date();
  date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
  var expires = '; expires=' + date.toGMTString();
  document.cookie = cookiename + '=' + value + expires + ';SameSite=lax';
}

function refreshOnlinePlayers() {
  var posting = $.post('/request/user/list-currently-online.php');
  posting.done(onRefreshOnlinePlayers);

  $('#playersonlinebox').fadeTo('fast', 0.0);
  $('#playersonline-update').fadeTo('fast', 0.0);
}

function onRefreshOnlinePlayers(data) {
  var playerList = $.parseJSON(data);
  var numPlayersOnline = playerList.length;

  var htmlOut = '<div>There are currently <strong>' + numPlayersOnline
    + '</strong> players online:</div>';

  for (var i = 0; i < numPlayersOnline; i += 1) {
    var player = playerList[i];

    if (i > 0 && i === numPlayersOnline - 1) {
      // last but one:
      htmlOut += ' and ';
    } else if (i > 0) {
      htmlOut += ', ';
    }

    var extraText = '<br>' + player.LastActivityAt + ': ' + player.User + ' '
      + player.LastActivity;
    htmlOut += GetUserAndTooltipDiv(player.User, player.RAPoints, player.Motto, false, extraText);
  }

  var d = new Date();

  $('#playersonlinebox').html(htmlOut);
  $('#playersonlinebox').fadeTo('fast', 1.0);
  $('#playersonline-update').html('Last updated at ' + d.toLocaleTimeString());
  $('#playersonline-update').fadeTo('fast', 0.5);
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
