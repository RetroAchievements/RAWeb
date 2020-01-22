var maxMessages = 50; //
var ws;
var webSocketReconnectIntervalID;

function replaceURLWithHTMLLinks(text) {
  var exp = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
  return text.replace(exp, '<a href=\'$1\'>$1</a>');
}

function linkify(inputText) {
  var replacedText;
  var replacePattern1;
  var replacePattern2;
  var replacePattern3;

  // URLs starting with http://, https://, or ftp://
  replacePattern1 = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
  replacedText = inputText.replace(replacePattern1,
    '<a href="$1" target="_blank" rel="noopener">$1</a>');

  // URLs starting with "www." (without // before it, or it'd re-link the ones done above).
  replacePattern2 = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
  replacedText = replacedText.replace(replacePattern2,
    '$1<a href="https://$2" target="_blank" rel="noopener">$2</a>');

  // Change email addresses to mailto:: links.
  replacePattern3 = /(\w+@[a-zA-Z_]+?\.[a-zA-Z]{2,6})/gim;
  replacedText = replacedText.replace(replacePattern3, '<a href="mailto:$1">$1</a>');

  return replacedText;
}

/* removes leading and trailing spaces from the string */
function trim(s) {
  return s.replace(/(^\s+)|(\s+$)/g, '');
}

function playSound(filename) {
  document.getElementById('sound').innerHTML = '<audio autoplay="autoplay"><source src="'
    + filename + '.mp3" type="audio/mpeg" /><source src="' + filename
    + '.ogg" type="audio/ogg" /><embed hidden="true" autostart="true" loop="false" src="' + filename
    + '.mp3" /></audio>';
}

// displays a message
function scrollChatContainer() {
  // $('#chatcontainer').animate( { scrollTop: $('#chatbox tr:last').offset().top }, 'slow' );
  $('#chatcontainer').animate({ scrollTop: $('.chatinnercontainer').height() }, 'slow');
}

/* handles keydown to detect when enter is pressed */
function handleKey(e) {
  // get the event
  e = (!e) ? window.event : e;
  // get the code of the character that has been pressed
  var code = 0;
  if (e.charCode) {
    code = e.charCode;
  }
  if (!code && e.keyCode) {
    code = e.keyCode;
  }
  if (!code && e.which) {
    code = e.which;
  }
  // handle the keydown event
  if (e.type === 'keydown') {
    // if enter (code 13) is pressed
    if (code === 13) {
      // send the current message
      sendMessage();
    }
  }
}

function attemptConnectWebSocket() {
  // TODO: get port from somewhere else
  // TODO: use cert for secure websocket connection
  ws = new WebSocket('ws://' + window.location.hostname + ':5000');
  ws.onopen = function (evt) {
    onOpen(evt);
  };
  ws.onclose = function (evt) {
    onClose(evt);
  };
  ws.onmessage = function (evt) {
    onMessage(evt);
  };
  ws.onerror = function (evt) {
    onError(evt);
  };

}

function onOpen(evt) {
  clearInterval(webSocketReconnectIntervalID);

  // console.info('WebSocket open... ');
  var obj = $('#chatloadingfirstrow');
  obj[0].innerHTML = '<tr><td colspan=\'4\'>Connected!</td></tr>';

  var theUser = readCookie('RA_User');
  if (theUser == null) {
    return;
  }

  // save the message to a local variable and clear the text box
  var theMessage = document.getElementById('chatinput').value;
  if (trim(theMessage) === '') {
    return;
  }

  document.getElementById('chatinput').value = ''; // Clear input

  // ws.send( theUser + " joined the chat." );
  // var jsonMsg = JSON.stringify( {user:'Server', theUser + "joined the chat.", timestamp:d} );
  // ws.send( jsonMsg );
}

function onChatMessage(timestamp, user, message, withSound) {
  var ts = new Date(timestamp);

  $('#chatbox')
    .append($('<tr>')
      .append($('<td>').html(GetUserAndTooltipDiv(user, null, null, true, '')))
      .append($('<td>')
        .html(('0' + ts.getHours()).slice(-2) + ':' + ('0' + ts.getMinutes()).slice(-2))
        .addClass('chatcelldate'))
      .append($('<td>')
        .html(linkify(message))
        .addClass('chatcellmessage')));

  if ($('#mutechat').prop('checked') === false && withSound) {
    scrollChatContainer();
    playSound('media/pop');
  }
}

function onMessage(evt) {
  // console.log("Recv: " + evt.data);
  var msgData = JSON.parse(evt.data); // { user, message }
  if (msgData.type === 'history') {
    var history = msgData.data;
    for (var i = 0; i < history.length; i += 1) {
      var innerMsg = history[i];
      // var innerMsg = JSON.parse(history[i]);
      // console.log(innerMsg);
      onChatMessage(innerMsg.timestamp, innerMsg.user, innerMsg.message, false);
    }
    scrollChatContainer();
    return;
  }
  if (msgData.type === 'ping') { // dodgy strcmp shizzle tbd
    return;
  }
  onChatMessage(msgData.timestamp, msgData.user, msgData.message, true);
}

function onError(evt) {
  // console.error('WebSocket issue... ' + evt.data);
  clearInterval(webSocketReconnectIntervalID);
  webSocketReconnectIntervalID = setInterval(attemptConnectWebSocket, 5000);
}

function onClose(evt) {
  // console.warn('Chat WebSocket closed... ' + evt.data);
  clearInterval(webSocketReconnectIntervalID);
  webSocketReconnectIntervalID = setInterval(attemptConnectWebSocket, 5000);
}

/* this function initiates the chat; it executes when the chat page loads */
function initChat(maxMsgs) {
  if (!document.getElementById('chatinput')) {
    return;
  }
  // prevents the autofill function from starting
  document.getElementById('chatinput').setAttribute('autocomplete', 'off');
  maxMessages = maxMsgs;
  attemptConnectWebSocket();
}

/* function called when the Send button is pressed */
function sendMessage() {
  var theUser = readCookie('RA_User');
  if (theUser == null) {
    return;
  }

  // save the message to a local variable and clear the text box
  var theMessage = document.getElementById('chatinput').value;
  if (trim(theMessage) === '') {
    return;
  }

  // Clear input
  document.getElementById('chatinput').value = '';

  var d = new Date().getTime();
  var jsonMsg = JSON.stringify({ user: theUser, message: theMessage, timestamp: d });
  ws.send(jsonMsg);
}
