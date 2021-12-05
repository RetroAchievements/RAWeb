/* pingURL - URL for updating feed */
var pingURL = '/request/feed/ping.php';
/* create XMLHttpRequest objects for updating the feed and getting the selected color */
var xmlHttpGetFeed = createXmlHttpRequestObject();
/* variables that establish how often to access the server */
var updateIntervalFeed = 30000; // how many milliseconds to wait to get new feed item (30000 = 30s)
/* initialize the messages cache */
var cacheFeed = [];
/* lastFeedID - the ID of the most recent feed feed item */
var lastFeedID = -1;

var timeouts = [];

function createXmlHttpRequestObject() {
  // will store the reference to the XMLHttpRequest object
  var xmlHttp;
  // this should work for all browsers except IE6 and older
  try {
    // try to create XMLHttpRequest object
    xmlHttp = new XMLHttpRequest();
  } catch (e) {
    // assume IE6 or older
    var XmlHttpVersions = [
      'MSXML2.XMLHTTP.6.0',
      'MSXML2.XMLHTTP.5.0',
      'MSXML2.XMLHTTP.4.0',
      'MSXML2.XMLHTTP.3.0',
      'MSXML2.XMLHTTP',
      'Microsoft.XMLHTTP',
    ];
    // try every prog id until one works
    for (var i = 0; i < XmlHttpVersions.length && !xmlHttp; i += 1) {
      try {
        // try to create XMLHttpRequest object
        xmlHttp = new ActiveXObject(XmlHttpVersions[i]);
      } catch (ex) {
        //
      }
    }
  }
  // return the created object or display an error message
  if (!xmlHttp) {
    console.error('Error creating the XMLHttpRequest object.');
  }
  return xmlHttp;
}

// (function ($) {
//   var sR = {
//     defaults: {
//       slideSpeed: 400,
//       easing: false,
//       callback: false,
//     },
//     thisCallArgs: {
//       slideSpeed: 400,
//       easing: false,
//       callback: false,
//     },
//     methods: {
//       up: function (arg1, arg2, arg3) {
//         if (typeof arg1 == 'object') {
//           for (p in arg1) {
//             sR.thisCallArgs.eval(p) = arg1[p];
//           }
//         } else if (typeof arg1 != 'undefined' &&
//           (typeof arg1 == 'number' || arg1 == 'slow' || arg1 == 'fast')) {
//           sR.thisCallArgs.slideSpeed = arg1;
//         } else {
//           sR.thisCallArgs.slideSpeed = sR.defaults.slideSpeed;
//         }
//
//         if (typeof arg2 == 'string') {
//           sR.thisCallArgs.easing = arg2;
//         } else if (typeof arg2 == 'function') {
//           sR.thisCallArgs.callback = arg2;
//         } else if (typeof arg2 == 'undefined') {
//           sR.thisCallArgs.easing = sR.defaults.easing;
//         }
//         if (typeof arg3 == 'function') {
//           sR.thisCallArgs.callback = arg3;
//         } else if (typeof arg3 == 'undefined' && typeof arg2 != 'function') {
//           sR.thisCallArgs.callback = sR.defaults.callback;
//         }
//         var $cells = $(this).find('td');
//         $cells.wrapInner('<div class="slideRowUp" />');
//         var currentPadding = $cells.css('padding');
//         $cellContentWrappers = $(this).find('.slideRowUp');
//         $cellContentWrappers.slideUp(sR.thisCallArgs.slideSpeed, sR.thisCallArgs.easing)
//                             .parent()
//                             .animate({
//                               paddingTop: '0px',
//                               paddingBottom: '0px',
//                             }, {
//                               complete: function () {
//                                 $(this)
//                                   .children('.slideRowUp')
//                                   .replaceWith($(this).children('.slideRowUp').contents());
//                                 $(this).parent().css({ 'display': 'none' });
//                                 $(this).css({ 'padding': currentPadding });
//                               },
//                             });
//         var wait = setInterval(function () {
//           if ($cellContentWrappers.is(':animated') === false) {
//             clearInterval(wait);
//             if (typeof sR.thisCallArgs.callback == 'function') {
//               sR.thisCallArgs.callback.call(this);
//             }
//           }
//         }, 100);
//         return $(this);
//       },
//       down: function (arg1, arg2, arg3) {
//         if (typeof arg1 == 'object') {
//           for (p in arg1) {
//             sR.thisCallArgs.eval(p) = arg1[p];
//           }
//         } else if (typeof arg1 != 'undefined' &&
//           (typeof arg1 == 'number' || arg1 == 'slow' || arg1 == 'fast')) {
//           sR.thisCallArgs.slideSpeed = arg1;
//         } else {
//           sR.thisCallArgs.slideSpeed = sR.defaults.slideSpeed;
//         }
//
//         if (typeof arg2 == 'string') {
//           sR.thisCallArgs.easing = arg2;
//         } else if (typeof arg2 == 'function') {
//           sR.thisCallArgs.callback = arg2;
//         } else if (typeof arg2 == 'undefined') {
//           sR.thisCallArgs.easing = sR.defaults.easing;
//         }
//         if (typeof arg3 == 'function') {
//           sR.thisCallArgs.callback = arg3;
//         } else if (typeof arg3 == 'undefined' && typeof arg2 != 'function') {
//           sR.thisCallArgs.callback = sR.defaults.callback;
//         }
//         var $cells = $(this).find('td');
//         $cells.wrapInner('<div class="slideRowDown" style="display:none;" />');
//         $cellContentWrappers = $cells.find('.slideRowDown');
//         $(this).show();
//         $cellContentWrappers.slideDown(sR.thisCallArgs.slideSpeed, sR.thisCallArgs.easing,
//           function () {
//             $(this).replaceWith($(this).contents());
//           });
//
//         var wait = setInterval(function () {
//           if ($cellContentWrappers.is(':animated') === false) {
//             clearInterval(wait);
//             if (typeof sR.thisCallArgs.callback == 'function') {
//               sR.thisCallArgs.callback.call(this);
//             }
//           }
//         }, 100);
//         return $(this);
//       },
//     },
//   };
//
//   $.fn.slideRow = function (method, arg1, arg2, arg3) {
//     if (typeof method != 'undefined') {
//       if (sR.methods[method]) {
//         return sR.methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
//       }
//     }
//   };
// })(jQuery);

/* this function initiates the feed; it executes when the feed page loads */
function initFeed() {
  // initiates updating the feed
  refreshFeed(parseInt(readCookie('RAPrefs_Feed'), 10) === 1);
}

function refreshFeed(friendsOnly) {
  var user = readCookie('RA_User');
  if (user == null) {
    friendsOnly = false;
  }

  var $feed = $('#feed tbody');

  $feed.empty();
  $feed.html(
    '<tr id=\'feedloadingfirstrow\'><td class=\'feedcell\'><img src=\'' + window.assetUrl + '/Images/loading.gif\' width=\'16\' height=\'16\'/></td><td class=\'feedcell\'></td><td class=\'feedcellmessage\'>Loading feed...</td></tr>'
  );

  cacheFeed = [];

  for (var i = 0; i < timeouts.length; i += 1) {
    clearTimeout(timeouts[i]);
  }
  // quick reset of the timer array you just cleared
  timeouts = [];

  var days = 30;

  var date = new Date();
  date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
  var expires = '; expires=' + date.toGMTString();

  if (friendsOnly) {
    document.cookie = 'RAPrefs_Feed=1' + expires;
    $('#globalfeedtitle').html('Friends Feed');
  } else {
    document.cookie = 'RAPrefs_Feed=0' + expires;
    $('#globalfeedtitle').html('Global Feed');
  }

  lastFeedID = -1;
  xmlHttpGetFeed = null;
  xmlHttpGetFeed = createXmlHttpRequestObject();

  requestNewFeed();
}

/* makes asynchronous request to retrieve new feed, post new feed, delete feed */
function requestNewFeed() {
  if (xmlHttpGetFeed) {
    try {
      // don't start another server operation if such an operation
      // is already in progress
      if (xmlHttpGetFeed.readyState === 4 || xmlHttpGetFeed.readyState === 0) {
        var feedPrefs = readCookie('RAPrefs_Feed');
        var user = readCookie('RA_User');

        if (user === '') {
          feedPrefs = '0';
        }

        var individualQS = (feedPrefs === '1') ? '&user=' + user : '';

        // we will store the parameters used to make the server request
        var params = '';
        // if there are requests stored in queue, take the oldest one
        if (cacheFeed.length > 0) {
          params = cacheFeed.shift();
        } else {
          // if the cache is empty, just retrieve new messages
          params = 'mode=RetrieveNew' + individualQS + '&id=' + lastFeedID;
        }

        // call the server page to execute the server-side operation
        xmlHttpGetFeed.open('POST', pingURL, true);
        xmlHttpGetFeed.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xmlHttpGetFeed.onreadystatechange = handleReceivingFeed;

        xmlHttpGetFeed.send(params);
      } else {
        // we will check again for new messages
        timeouts.push(setTimeout(function () {
          requestNewFeed();
        }, updateIntervalFeed));
      }
    } catch (e) {
      console.error(e.toString());
    }
  }
}

/* function that handles the http response when updating messages */
function handleReceivingFeed() {
  // continue if the process is completed
  if (xmlHttpGetFeed.readyState === 4) {
    // continue only if HTTP status is "OK"
    if (xmlHttpGetFeed.status === 200) {
      try {
        // process the server's response
        readFeed();
      } catch (e) {
        // display the error message
        console.error(e.toString());
      }
    } else {
      // display the error message
      timeouts.push(setTimeout(function () {
        requestNewFeed();
      }, updateIntervalFeed));
    }
  }
}

/* function that processes the server's response when updating messages */
function readFeed() {
  // retrieve the server's response
  if (xmlHttpGetFeed.responseText == null
    || xmlHttpGetFeed.responseText.length === 0
    || xmlHttpGetFeed.responseText.indexOf('ERRNO') >= 0
    || xmlHttpGetFeed.responseText.indexOf('error:') >= 0
    || xmlHttpGetFeed.responseXML == null
  ) {
    console.error(
      xmlHttpGetFeed.responseText ? xmlHttpGetFeed.responseText : 'Void server response.'
    );
    return;
  }

  var response = xmlHttpGetFeed.responseXML.documentElement;
  if (response != null) {
    // retrieve the arrays from the server's response
    var idArray = response.getElementsByTagName('feedID');
    var timestampArray = response.getElementsByTagName('feedTimestamp');
    var actTypeArray = response.getElementsByTagName('feedActType');
    var userArray = response.getElementsByTagName('feedUser');
    var userPointsArray = response.getElementsByTagName('feedUserPoints');
    var userMottoArray = response.getElementsByTagName('feedUserMotto');
    var dataArray = response.getElementsByTagName('feedData');
    var data2Array = response.getElementsByTagName('feedData2');
    var gameTitleArray = response.getElementsByTagName('feedGameTitle');
    var gameIDArray = response.getElementsByTagName('feedGameID');
    var gameIconArray = response.getElementsByTagName('feedGameIcon');
    var consoleNameArray = response.getElementsByTagName('feedConsoleName');
    var achTitleArray = response.getElementsByTagName('feedAchTitle');
    var achDescArray = response.getElementsByTagName('feedAchDesc');
    var achBadgeArray = response.getElementsByTagName('feedAchBadge');
    var achPointsArray = response.getElementsByTagName('feedAchPoints');
    var LBTitleArray = response.getElementsByTagName('feedLBTitle');
    var LBDescArray = response.getElementsByTagName('feedLBDesc');
    var LBFormatArray = response.getElementsByTagName('feedLBFormat');
    var commentUserArray = response.getElementsByTagName('feedCommentUser');
    var commentUserPointsArray = response.getElementsByTagName('feedCommentUserPoints');
    var commentUserMottoArray = response.getElementsByTagName('feedCommentUserMotto');
    var commentArray = response.getElementsByTagName('feedComment');
    var commentPostedArray = response.getElementsByTagName('feedCommentPostedAt');

    // the ID of the last received message is stored locally
    if (idArray.length > 0) {
      lastFeedID = idArray.item(idArray.length - 1).firstChild.data;
    }

    // add the new messages to the feed window
    displayFeedItems(idArray, timestampArray, actTypeArray,
      userArray, userPointsArray, userMottoArray,
      dataArray, data2Array,
      gameTitleArray, gameIDArray, gameIconArray, consoleNameArray,
      achTitleArray, achDescArray, achBadgeArray, achPointsArray,
      LBTitleArray, LBDescArray, LBFormatArray,
      commentUserArray, commentUserPointsArray, commentUserMottoArray, commentArray,
      commentPostedArray);
  }

  // restart sequence
  timeouts.push(setTimeout(function () {
    requestNewFeed();
  }, updateIntervalFeed));
}

/* function that appends the new messages to the feed list  */
function displayFeedItems(
  idArray, timestampArray, actTypeArray,
  userArray, userPointsArray, userMottoArray,
  dataArray, data2Array,
  gameTitleArray, gameIDArray, gameIconArray, consoleNameArray,
  achTitleArray, achDescArray, achBadgeArray, achPointsArray,
  LBTitleArray, LBDescArray, LBFormatArray,
  commentUserArray, commentUserPointsArray, commentUserMottoArray,
  commentArray, commentPostedArray
) {
  // each loop adds a new message
  for (var i = 0; i < idArray.length; i += 1) {
    var feedItemID = parseInt(idArray.item(i).firstChild.data.toString(), 10);

    // get the message details
    var timestamp = timestampArray.item(i).firstChild.data.toString();
    var acttype = parseInt(actTypeArray.item(i).firstChild.data.toString(), 10);
    var user = userArray.item(i).firstChild.data.toString();
    var userPoints = userPointsArray.item(i).firstChild.data.toString();
    var userMotto = userMottoArray.item(i).firstChild.data.toString();
    var data = dataArray.item(i).firstChild.data.toString();
    var data2 = data2Array.item(i).firstChild.data.toString();
    var gameTitle = gameTitleArray.item(i).firstChild.data.toString();
    var gameID = parseInt(gameIDArray.item(i).firstChild.data.toString(), 10);
    var gameIcon = gameIconArray.item(i).firstChild.data.toString();
    var consoleName = consoleNameArray.item(i).firstChild.data.toString();
    var achTitle = achTitleArray.item(i).firstChild.data.toString();
    var achDesc = achDescArray.item(i).firstChild.data.toString();
    var achBadge = achBadgeArray.item(i).firstChild.data.toString();
    var achPoints = parseInt(achPointsArray.item(i).firstChild.data.toString(), 10);
    var lbTitle = LBTitleArray.item(i).firstChild.data.toString();
    var lbDesc = LBDescArray.item(i).firstChild.data.toString();
    var lbFormat = LBFormatArray.item(i).firstChild.data.toString();
    var commentUser = commentUserArray.item(i).firstChild.data.toString();
    var commentUserPoints = commentUserPointsArray.item(i).firstChild.data.toString();
    var commentUserMotto = commentUserMottoArray.item(i).firstChild.data.toString();

    // compose the HTML code that displays the message

    // var d = new Date( parseInt( time )*1000 ); // In UTC

    // var dLocal = new Date(); // In Local!
    // dLocal.setUTCFullYear( d.getFullYear() );
    // dLocal.setUTCMonth( d.getMonth() );
    // dLocal.setUTCDate( d.getDate() );
    // dLocal.setUTCHours( d.getHours() );
    // dLocal.setUTCMinutes( d.getMinutes() );
    // dLocal.setUTCSeconds( d.getSeconds() );

    // var timeStr = "";
    // timeStr += "[";
    // timeStr += ("0" + dLocal.getUTCHours()).slice(-2);
    // timeStr += ":";
    // timeStr += ("0" + dLocal.getUTCMinutes()).slice(-2);
    // timeStr += "]";

    // add the new message to the feed list:
    pushFeedItem(feedItemID, timestamp, acttype, user, userPoints, userMotto, data, data2,
      gameTitle, gameID, gameIcon, consoleName, achTitle, achDesc, achBadge, achPoints, lbTitle,
      lbDesc, lbFormat);

    if (commentUser !== null && commentUser.length > 1) {
      var nextFeedItemID = feedItemID;
      var lastID = parseInt(idArray.item(i).firstChild.data.toString(), 10);
      while (i < idArray.length) {
        var innerCommentUser = commentUserArray.item(i).firstChild.data.toString();
        var innerComment = commentArray.item(i).firstChild.data.toString();
        var innerCommentPosted = commentPostedArray.item(i).firstChild.data.toString();
        var innerCommentUserPoints = commentUserPointsArray.item(i).firstChild.data.toString();
        var innerCommentUserMotto = commentUserMottoArray.item(i).firstChild.data.toString();

        pushFeedComment(nextFeedItemID, commentUser, comment, commentPosted, commentUserPoints,
          commentUserMotto);
        i += 1;
      }
      i -= 1;
    }
  }

  // While instead: should cope with comments now too
  // Don't use while! Will never complete (animation requires time, length remains >50!)
  if ($('#feed tr').length > 50) {
    popFinalFeedItem(idArray.length);
  }
}

/**
 * @return {string}
 */
function GetFormattedLeaderboardEntry(formatType, scoreIn) {
  scoreIn = parseInt(scoreIn, 10);

  var mins;
  var secs;
  var milli;
  if (formatType === 'TIME') {
    mins = parseInt(scoreIn / 3600, 10);
    secs = parseInt((scoreIn % 3600) / 60, 10);
    milli = parseInt(((scoreIn % 3600) % 60) * (100.0 / 60.0), 10);
    return mins.toString() + ':' + strPad(secs, 2) + '.' + strPad(milli, 2);
  }
  if (formatType === 'TIMESECS') {
    mins = parseInt(scoreIn / 60, 10);
    secs = parseInt(scoreIn % 60, 10);
    return strPad(secs, 1) + ':' + strPad(milli, 2);
  }
  if (formatType === 'MILLISECS') {
    mins = parseInt(scoreIn / 6000, 10);
    secs = parseInt((scoreIn % 6000) / 100, 10);
    milli = parseInt(scoreIn % 100, 10);
    return mins.toString() + ':' + strPad(secs, 2) + '.' + strPad(milli, 2);
  }

  return scoreIn.toString();
}

function pushFeedItem(
  feedItemID, timestamp, acttype, user, userPoints, userMotto, data, data2, gameTitle, gameID,
  gameIcon, consoleName, achTitle, achDesc, achBadge, achPoints, lbTitle, lbDesc, lbFormat
) {
  var rowClass = '';
  var rowIcon1 = '';
  var rowIcon2 = '';
  var rowData = '';

  switch (acttype) {
    case 0: // Unknown
      rowClass = 'feed_login';
      break;
    case 1: // New Achievement Earned
      rowClass = 'feed_won';

      if (data2 === 1) {
        rowData += ' (HARDCORE)';
        rowClass = 'feed_won_hc';
      }

      rowIcon1 = GetAchievementAndTooltipDiv(data, achTitle, achDesc, achPoints, gameTitle,
        achBadge, true, true);
      rowIcon2 = GetUserAndTooltipDiv(user, userPoints, userMotto, true, '');

      rowData = GetUserAndTooltipDiv(user, userPoints, userMotto, false, '');
      rowData += ' earned ';
      rowData += GetAchievementAndTooltipDiv(data, achTitle, achDesc, achPoints, gameTitle,
        achBadge, false, false);

      if (data2 === 1) {
        rowData += ' (HARDCORE)';
      }

      rowData += ' in ';
      rowData += GetGameAndTooltipDiv(gameID, gameTitle, gameIcon, consoleName);

      break;
    case 2: // Login
      rowClass = 'feed_login';

      rowIcon1 = '<img '
        + 'alt=\'' + user + ' logged in\' '
        + 'title=\'Logged in\' src=\'/Images/LoginIcon32.png\' '
        + 'width=\'32\' height=\'32\' class=\'badgeimg\'>';
      rowIcon2 = GetUserAndTooltipDiv(user, userPoints, userMotto, true, '');

      rowData = GetUserAndTooltipDiv(user, userPoints, userMotto, false, '');
      rowData += ' logged in';

      break;
    case 3: // Started Playing Game
      rowClass = 'feed_startplay';

      rowIcon1 = GetGameAndTooltipDiv(data, gameTitle, gameIcon, consoleName, true);
      rowIcon2 = GetUserAndTooltipDiv(user, userPoints, userMotto, true, '');

      rowData = GetUserAndTooltipDiv(user, userPoints, userMotto, false, '');
      rowData += ' started playing ';
      rowData += GetGameAndTooltipDiv(gameID, gameTitle, gameIcon, consoleName);

      break;
    case 4: // Created Achievement
      rowClass = 'feed_dev1';

      rowIcon1 = GetAchievementAndTooltipDiv(data, achTitle, achDesc, achPoints, gameTitle,
        achBadge, true, true);
      rowIcon2 = GetUserAndTooltipDiv(user, userPoints, userMotto, true, '');

      rowData = GetUserAndTooltipDiv(user, userPoints, userMotto, false, '');
      rowData += ' uploaded a new achievement: ';
      rowData += GetAchievementAndTooltipDiv(data, achTitle, achDesc, achPoints, gameTitle,
        achBadge, false, false);

      break;
    case 5: // Updated Achievement
      rowClass = 'feed_dev2';

      rowIcon1 = GetAchievementAndTooltipDiv(data, achTitle, achDesc, achPoints, gameTitle,
        achBadge, true, true);
      rowIcon2 = GetUserAndTooltipDiv(user, userPoints, userMotto, true, '');

      rowData = GetUserAndTooltipDiv(user, userPoints, userMotto, false, '');
      rowData += ' made improvements to: ';
      rowData += GetAchievementAndTooltipDiv(data, achTitle, achDesc, achPoints, gameTitle,
        achBadge, false, false);

      break;
    case 6: // Game Completed
      rowClass = 'feed_completegame';

      rowIcon1 = GetGameAndTooltipDiv(data, gameTitle, gameIcon, consoleName, true);
      rowIcon2 = GetUserAndTooltipDiv(user, userPoints, userMotto, true, '');

      rowData = GetUserAndTooltipDiv(user, userPoints, userMotto, false, '');

      rowData += data2 === 1 ? ' MASTERED ' : ' completed ';

      rowData += GetGameAndTooltipDiv(gameID, gameTitle, gameIcon, consoleName);

      if (data2 === 1) {
        rowData += ' (HARDCORE) ';
      }

      break;
    case 7: // LB New Entry
      rowClass = 'feed_submitrecord';

      rowIcon1 = GetGameAndTooltipDiv(gameID, gameTitle, gameIcon, consoleName, true);
      rowIcon2 = GetUserAndTooltipDiv(user, userPoints, userMotto, true, '');

      rowData = GetUserAndTooltipDiv(user, userPoints, userMotto, false, '');
      rowData += ' submitted ';
      rowData += GetLeaderboardAndTooltipDiv(data, lbTitle, lbDesc, gameTitle, gameIcon,
        GetFormattedLeaderboardEntry(lbFormat, data2));
      rowData += ' for ';
      rowData += GetLeaderboardAndTooltipDiv(data, lbTitle, lbDesc, gameTitle, gameIcon, lbTitle);
      rowData += ' on ';
      rowData += GetGameAndTooltipDiv(gameID, gameTitle, gameIcon, consoleName);

      break;
    case 8: // LB Update Entry
      rowClass = 'feed_updaterecord';

      rowIcon1 = GetGameAndTooltipDiv(gameID, gameTitle, gameIcon, consoleName, true);
      rowIcon2 = GetUserAndTooltipDiv(user, userPoints, userMotto, true, '');

      var entryType = (lbFormat === 'TIME' || lbFormat === 'TIMESECS') ? 'time' : 'score';

      rowData = GetUserAndTooltipDiv(user, userPoints, userMotto, false, '');
      rowData += ' improved their ' + entryType + ': ';
      rowData += GetLeaderboardAndTooltipDiv(data, lbTitle, lbDesc, gameTitle, gameIcon,
        GetFormattedLeaderboardEntry(lbFormat, data2));
      rowData += ' for ';
      rowData += GetLeaderboardAndTooltipDiv(data, lbTitle, lbDesc, gameTitle, gameIcon, lbTitle);
      rowData += ' on ';
      rowData += GetGameAndTooltipDiv(gameID, gameTitle, gameIcon, consoleName);

      break;

    case 9: // Opened a ticket
    case 10: // Closed a ticket
      rowClass = 'feed_dev2';

      rowIcon1 = GetAchievementAndTooltipDiv(data, achTitle, achDesc, achPoints, gameTitle,
        achBadge, true, true);
      rowIcon2 = GetUserAndTooltipDiv(user, userPoints, userMotto, true, '');

      rowData = GetUserAndTooltipDiv(user, userPoints, userMotto, false, '');
      rowData += (acttype === 9 ? ' opened ' : ' closed ') + 'a ticket for ';
      rowData += GetAchievementAndTooltipDiv(data, achTitle, achDesc, achPoints, gameTitle,
        achBadge, false, false);

      if (data2 === 1) {
        rowData += ' (HARDCORE)';
      }

      rowData += ' in ';
      rowData += GetGameAndTooltipDiv(gameID, gameTitle, gameIcon, consoleName);

      break;
    default:
      break;
  }

  var d = new Date(parseInt(timestamp, 10) * 1000); // In UTC

  var dLocal = new Date(); // In Local!
  dLocal.setUTCFullYear(d.getFullYear());
  dLocal.setUTCMonth(d.getMonth());
  dLocal.setUTCDate(d.getDate());
  dLocal.setUTCHours(d.getHours());
  dLocal.setUTCMinutes(d.getMinutes());
  dLocal.setUTCSeconds(d.getSeconds());

  var timeStr = '';
  timeStr += ('0' + dLocal.getUTCHours()).slice(-2);
  timeStr += ':';
  timeStr += ('0' + dLocal.getUTCMinutes()).slice(-2);

  var rowID = 'art_' + feedItemID;

  var insertRowHtml = '';
  var niceTime = timeStr;

  var dToday = new Date();

  insertRowHtml += '<tr id=\'' + rowID + '\' class=\'' + rowClass + '\' >';
  insertRowHtml += '<td class=\'feeddate\'><small>' + niceTime + '</small></td>';
  insertRowHtml += '<td class=\'icons\'>' + rowIcon1 + '</td>';
  insertRowHtml += '<td class=\'icons\'>' + rowIcon2 + '</td>';
  insertRowHtml += '<td class=\'feeditem ' + rowClass + '\'>' + rowData + '</td>';

  // Add 'edit' if appropriate:
  var localUser = readCookie('RA_User');
  if (localUser !== null) {
    // Discrepancy between article type and activity type: feed activity is always article type 5!!
    // insertRowHtml += "<td class='editbutton'><img src='" + window.assetUrl + "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '" + rowID.toString() + "', '" + acttype.toString() + "' )\" /></td>";
    insertRowHtml += '<td class=\'editbutton\'><img src=\'' + window.assetUrl + '/Images/Edit.png\' width=\'16\' height=\'16\' style=\'cursor: pointer;\' onclick="insertEditForm( \''
      + rowID.toString() + '\', \'5\' )" /></td>';
  }

  insertRowHtml += '</tr>';

  var topRow = $('#feed tr:first');
  topRow.before(insertRowHtml);

  topRow = $('#feed tr:first'); // Ensure we have OUR row
  // topRow.hide();
  // topRow.fadeIn(5000);

  // dataRow.slideDown("slow");
  // topRow.children("td").each( function(){
  //   $(this).wrapInner("<div/>").children("div").slideDown(5000);
  // } );

  topRow.slideRow('down', 1000);

  var feedLink = '<tr><td colspan=5><span class=\'morebutton\'><a href=\'/feed.php?g=1\'>more...</a></span></td></tr>';
  $('#feedloadingfirstrow').replaceWith(feedLink);
}

function popFinalFeedItem(numItems) {
  var lastRow = $('#feed tr:last');
  var lastDataRow = lastRow.prev();

  for (var i = 0; i < numItems; i += 1) {
    lastDataRow.slideRow('up', 600, function () {
      lastDataRow.remove();
    });
    lastDataRow = lastDataRow.prev();
  }
  // lastDataRow.remove();
}

function pushFeedComment(
  nextFeedItemID, commentUser, comment, commentPosted, commentUserPoints, commentUserMotto
) {
  // $niceDate = date( "d M\nH:i ", strtotime( commentPosted ) );

  var d = new Date(parseInt(commentPosted, 10) * 1000);
  var niceDate = d.getDate() + ' ' + shortMonths[d.getMonth()] + '\n'
    + strPad(d.getHours(), 2) + ':' + strPad(d.getMinutes(), 2);

  var insertRowHtml = '<tr class=\'feed_comment\'>';
  insertRowHtml += '<td class=\'smalldate\'><small>' + niceDate + '</small></td>';
  insertRowHtml += '<td class=\'iconscomment\'>'
    + GetUserAndTooltipDiv(commentUser, commentUserPoints, commentUserMotto, true, '') + '</td>';
  insertRowHtml += '<td class=\'commenttext\' colspan=\'3\'>' + comment.toString() + '</td>';

  insertRowHtml += '</tr>';

  var rowName = '#art_' + nextFeedItemID.toString();

  $(rowName).after(insertRowHtml);
}

// displays a message
function scrollFeedContainer() {
  $('#feedcontainer').scrollTop(99999);
}
