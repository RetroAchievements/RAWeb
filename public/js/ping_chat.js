
var updateInterval = 250000;			//	ms
var lastChatMessageID = -1;			//	last known chat msg
var maxMessages = 50;				//
var receivedIDsList = new Array()	//	cache recv'd IDs, to avoid duplicates

var ws;// = new WebSocket("ws://retroachievements.org:5000");

function replaceURLWithHTMLLinks(text) {
    var exp = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
    return text.replace(exp,"<a href='$1'>$1</a>"); 
}

function linkify(inputText) {
    var replacedText, replacePattern1, replacePattern2, replacePattern3;

    //URLs starting with http://, https://, or ftp://
    replacePattern1 = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
    replacedText = inputText.replace(replacePattern1, '<a href="$1" target="_blank" rel="noopener">$1</a>');

    //URLs starting with "www." (without // before it, or it'd re-link the ones done above).
    replacePattern2 = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
    replacedText = replacedText.replace(replacePattern2, '$1<a href="https://$2" target="_blank" rel="noopener">$2</a>');

    //Change email addresses to mailto:: links.
    replacePattern3 = /(\w+@[a-zA-Z_]+?\.[a-zA-Z]{2,6})/gim;
    replacedText = replacedText.replace(replacePattern3, '<a href="mailto:$1">$1</a>');

    return replacedText;
}

/* removes leading and trailing spaces from the string */
function trim(s) {
    return s.replace(/(^\s+)|(\s+$)/g, "")
}

function playSound(filename) {   
	document.getElementById("sound").innerHTML='<audio autoplay="autoplay"><source src="' + filename + '.mp3" type="audio/mpeg" /><source src="' + filename + '.ogg" type="audio/ogg" /><embed hidden="true" autostart="true" loop="false" src="' + filename +'.mp3" /></audio>';
}

// displays a message
function scroll_chatcontainer() {
	//$('#chatcontainer').animate( { scrollTop: $('#chatbox tr:last').offset().top }, 'slow' );
	$('#chatcontainer').animate( { scrollTop: $('.chatinnercontainer').height() }, 'slow' );
}

// function that displays an error message
function displayError( msg ) {
	// display error message, with more technical details if debugMode is true
	alert( "Error accessing the server! " + msg );
}

/* handles keydown to detect when enter is pressed */
function handleKey( e ) {
	// get the event
	e = (!e) ? window.event : e;
	// get the code of the character that has been pressed        
	code = (e.charCode) ? e.charCode :
         	((e.keyCode) ? e.keyCode :
         	((e.which) ? e.which : 0));

	// handle the keydown event       
	if (e.type == "keydown") 
	{
		// if enter (code 13) is pressed
		if(code == 13)
		{
			// send the current message  
			sendMessage();
    	}
  	}
}

var webSocketReconnectIntervalID;
function attemptConnectWebSocket()
{
	//ws = new WebSocket("ws://retroachievements.org:5000");
	ws = new WebSocket("ws://" + location.hostname + ":5000");
	ws.onopen = function(evt) { onOpen(evt) }; 
	ws.onclose = function(evt) { onClose(evt) }; 
	ws.onmessage = function(evt) { onMessage(evt) }; 
	ws.onerror = function(evt) { onError(evt) };
}

function onOpen(evt)
{
	clearInterval( webSocketReconnectIntervalID );
	
	console.info("WebSocket open... ");
	var obj = $("#chatloadingfirstrow");
	obj[0].innerHTML = "<tr><td colspan='4'>Connected!</td></tr>";
	//	request most recent messages
	
	var theUser = RA_ReadCookie('RA_User');
	if( theUser == null )
		return;
	
	// save the message to a local variable and clear the text box 
	var theMessage = document.getElementById("chatinput").value;
	if( trim( theMessage ) == "" )
		return;
		
	document.getElementById( "chatinput" ).value = "";	//	Clear input

	//ws.send( theUser + " joined the chat." );
	//var jsonMsg = JSON.stringify( {user:'Server', message:theUser + "joined the chat.", timestamp:d} );
	//ws.send( jsonMsg );
}

function onChatMessage(timestamp, user, message, withSound)
{
	var ts = new Date( timestamp );
	
	$("#chatbox")
		 .append( $('<tr>')
			 .append( $('<td>') 
				 .html( GetUserAndTooltipDiv( user, null, null, true, "" ) )
			 )
			 .append( $('<td>') 
				 .html( ('0'+ts.getHours()).slice(-2) + ":" + ('0'+ts.getMinutes()).slice(-2) )
				 .addClass('chatcelldate')
			 )
			 .append( $('<td>')
				 .html( linkify( message ) )
				 .addClass('chatcellmessage')
			 )
		 );
	
	if( $("#mutechat").prop( 'checked' ) == false && withSound )
	{
		scroll_chatcontainer();	
		playSound('media/pop');
	}
}

function onMessage(evt)
{
	// console.log("Recv: " + evt.data);
	var msgData = JSON.parse(evt.data);	//{ user, message }
	
	if( msgData.type == "history" )
	{
		var history = msgData.data;
		// console.log( msgData.data );
		
		for( var i = 0; i < history.length; ++i )
		{
			var innerMsg = history[i];
			//var innerMsg = JSON.parse(history[i]);
			// console.log(innerMsg);
			onChatMessage( innerMsg.timestamp, innerMsg.user, innerMsg.message, false );
		}
		scroll_chatcontainer();	
		return;
	}
	else if( msgData.type == "ping" )	//	dodgy strcmp shizzle tbd
	{
		return;
	}
	else
	{
		onChatMessage( msgData.timestamp, msgData.user, msgData.message, true );
	}
}

function onError(evt)
{
	console.error("WebSocket issue... " + evt.data);
	clearInterval( webSocketReconnectIntervalID );
	webSocketReconnectIntervalID = setInterval(attemptConnectWebSocket, 5000);
}

function onClose(evt)
{
	console.warn("Chat WebSocket closed... " + evt.data);
	//alert("WebSocket closed..." );
	clearInterval( webSocketReconnectIntervalID );
	webSocketReconnectIntervalID = setInterval(attemptConnectWebSocket, 5000);
}

/* this function initiates the chat; it executes when the chat page loads */
function init_chat( maxMsgs ) 
{
	if(!document.getElementById("chatinput")) {
		return;
	}

	// prevents the autofill function from starting
	document.getElementById("chatinput").setAttribute("autocomplete", "off");
	
	maxMessages = maxMsgs;
	
	attemptConnectWebSocket();
	// initiates updating the chat window
	//requestNewMessages();
}

/* function called when the Send button is pressed */
function sendMessage() {
	var theUser = RA_ReadCookie('RA_User');
	if( theUser == null )
		return;
	
	// save the message to a local variable and clear the text box
	var theMessage = document.getElementById("chatinput").value;
	if( trim( theMessage ) == "" )
		return;
		
	document.getElementById( "chatinput" ).value = "";	//	Clear input

	var d = new Date().getTime();
	
	var jsonMsg = JSON.stringify( {user:theUser, message:theMessage, timestamp:d} );
	ws.send( jsonMsg );
	// console.log( "sent: " + theUser + ": " + theMessage );
	
	// var posting = $.post( "ping_chat.php", { 
		// mode: 'SendAndRetrieveNew', 
		// id: encodeURIComponent(lastChatMessageID), 
		// name: encodeURIComponent(theUser),
		// message: encodeURIComponent(theMessage),
		// maxmsg: maxMessages
		// } );
	// posting.done( onChatPacketSend );
}

// function requestNewMessages() {
	// var posting = $.post( "ping_chat.php", { 
		// mode: 'RetrieveNew', 
		// id: lastChatMessageID, 
		// maxmsg: maxMessages 
		// } );
	// posting.done( onChatPacketReceive );
// }

function onChatPacketReceive( data ) {
	readMessages( data );
	//setTimeout("requestNewMessages();", updateInterval);	// restart sequence
}

function onChatPacketSend( data ) {
	readMessages( data );
}

/* function that processes the server's response when updating messages */
function readMessages( xmlDoc ) {
	var response = xmlDoc.documentElement;
	if( response != null )
	{
		// retrieve the arrays from the server's response     
		idArray = response.getElementsByTagName("id");
		nameArray = response.getElementsByTagName("name");
		timeArray = response.getElementsByTagName("time");
		pointsArray = response.getElementsByTagName("points");
		mottoArray = response.getElementsByTagName("motto");
		messageArray = response.getElementsByTagName("message");

		// add the new messages to the chat window
		displayMessages(idArray, nameArray, pointsArray, mottoArray, timeArray, messageArray);

		// the ID of the last received message is stored locally
		if( idArray.length>0 )
		{
			lastChatMessageID = idArray.item(idArray.length - 1).firstChild.data;
		}
	}
	else
	{
		//alert("response null");
	}
}

/* function that appends the new messages to the chat list  */
function displayMessages( idArray, nameArray, pointsArray, mottoArray, timeArray, messageArray ) {
  // each loop adds a new message
  for(var i=0; i<idArray.length; i++)
  {
	var id = idArray.item(i).firstChild.data.toString();
	if( $.inArray(id, receivedIDsList) == -1 )
	{	
		//	Not found
		receivedIDsList.push( id );
	}
	else
	{
		//	We've already received this message:
		continue;
	}
  
    // get the message details
    var time = timeArray.item(i).firstChild.data.toString();
    var name = nameArray.item(i).firstChild.data.toString();
    var points = pointsArray.item(i).firstChild.data.toString();
    var motto = mottoArray.item(i).firstChild.data.toString();
    var message = messageArray.item(i).firstChild.data.toString();
    // compose the HTML code that displays the message

	//	Inject HTML for links
	message = linkify( message );
	
	var d = new Date( parseInt( time )*1000 );	//	In UTC
	
	var dLocal = new Date();	//	In Local!
	dLocal.setUTCFullYear( d.getFullYear() );
	dLocal.setUTCMonth( d.getMonth() );
	dLocal.setUTCDate( d.getDate() );
	dLocal.setUTCHours( d.getHours() );
	dLocal.setUTCMinutes( d.getMinutes() );
	dLocal.setUTCSeconds( d.getSeconds() );
	
	var timeStr = "";
	timeStr += ("0" + dLocal.getUTCHours()).slice(-2);
	timeStr += ":";
	timeStr += ("0" + dLocal.getUTCMinutes()).slice(-2);

	var rowID = parseInt( idArray.item(i).firstChild.data.toString() );
	
	var insertRowHtml = "";
	if( rowID % 2 == 1 )
		insertRowHtml = "<tr class='alt'>";
	else
		insertRowHtml = "<tr>";
	
	var userHTML = GetUserAndTooltipDiv( name, points, motto, true, "" );
	
	insertRowHtml += "<td class='chatcell'>" + userHTML + "</td>";
	insertRowHtml += "<td class='chatcell chatcelldate'><span title='" + dLocal.toString() + "'><small>" + timeStr + "</small></span></td>";
	insertRowHtml += "<td class='chatcellmessage'>" + message.toString() + "</td>";
	
	insertRowHtml += "</tr>";
	
	$("#chatbox tr:last").after(insertRowHtml);
	
    //	add the new message to the chat list:
  }

	if( idArray.length > 0 )
	{
		if( idArray.length < 10 )
		{
			if( $("#mutechat").prop( 'checked' ) == false )
				playSound('media/pop');
		}
		else if( idArray.length == 50 )
		{
			$chatLink = "<tr><td></td><td colspan=2><a href='largechat.php'>See older chat...</a></td></tr>";
			$("#chatloadingfirstrow").replaceWith($chatLink);
		}
		
		//	Drop the chat scroll to bottom:
		scroll_chatcontainer();
	}
}
