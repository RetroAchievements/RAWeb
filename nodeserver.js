'use strict';

var WebSocketServer = require('ws').Server;	//	pulls in required files
var http = require('http');
var express = require('express');

//var gamesession = require('./gamesession.js');

var app = express();							//	app is an instance of an express application
app.set('port', 5000);

//app.set('port', (process.env.PORT || 8080));
//app.set('port', (process.env.PORT || 5000));


//	Revoke webserver
//app.use(express.static(__dirname + '/'));

//var gamesession = require('./gamesession.js');

//var cool = require('cool-ascii-faces');

//var pg = require('pg');	//	db
//var mysql = require('mysql');	//	https://www.npmjs.com/package/mysql
//var db = mysql.createConnection({
//	host: 'us-cdbr-iron-east-02.cleardb.net',
//	user: 'bdebe864d424a6',
//	password: '1cd91759',
//	database: 'heroku_9a6b08c70f01ff7'
//});

//console.log("process.env.PORT is " + process.env.PORT);
// views is directory for all template files
//app.set('views', __dirname + '/views');
//app.set('view engine', 'ejs');

//app.get('/', function(request, response) {
//	//response.render('pages/index');
//	var result = ''
//	var times = process.env.TIMES || 5
//	for(i=0; i < times; i++)
//		result += cool();
	
//	result += "</br><a href='/db'>DB</a>";
//	response.send(result);
//});

//app.get('/cool', function(request, response) {
//	response.send(cool());
//});

////	Listen on the app's port, just to log out what port we are running on
////	Binds and listens for connections on the specified host and port. This method is identical to Node’s http.Server.listen().
//app.listen(app.get('port'), function() {
//	console.log('Node app is running on port', app.get('port'));
//});

//app.get('/db', function (request, response) {
//	//pg.connect(process.env.DATABASE_URL, function (err, client, done) {
//	//	console.log(err);
//	//	client.query('SELECT * FROM test_table', function (err, result) {
//	//		done();
//	//		if (err) {
//	//			console.error(err);
//	//			response.send("Error " + err);
//	//		}
//	//		else {
//	//			response.render('pages/db', { results: result.rows });
//	//		}
//	//	});
//	//});
//	db.connect();
//	//db.query('SELECT 1 + 1 AS solution', function (err, rows, fields) {
//	//	if (err)
//	//		throw err;
//	//	result = 'Solution is ' + rows[0].solution;
//	//	//console.log(result);
//	//});
	
//	response.send(result);
//	db.end();
//});

//	Redis:
var redis = require('redis'),
	client = redis.createClient();
	
client.subscribe('earnachievement');

client.on('message', function(channel, message) {
	console.log("Received message " + message );
	wss.onearnachievement(message);
} );

var server = http.createServer(app);
server.listen(app.get('port'));
console.log("server listening on %d", app.get('port'));

var wss = new WebSocketServer({ server: server });	//	allocates a wss
console.log("Websocket server created");

var clients = [];

wss.broadcast = function broadcast(data) {
	//wss.clients.forEach(function each(client) {}
	//		client.send(data);
	//	});
	for(var i = 0; i < wss.clients.length; ++i )
	{
		if( wss.clients[i].readyState == wss.clients[i].OPEN )
			wss.clients[i].send(data);
	}
};

var showAchievements = false;

wss.onearnachievement = function(achData) {
	
	if( showAchievements ) {
		var timestamp = new Date().getTime();
		var newMsg = { user: "RetroAchievements", message: achData, timestamp:timestamp };
		wss.broadcast( JSON.stringify( newMsg ) );	//	ping it back out
	}
}

var history = [ {user: "Server", message: "Server Started!", timestamp:new Date().getTime()} ];

wss.on("connection", function(ws) {
	//console.log("websocket connection open!");
	clients.push( ws.origin );
	
	function SendPing() {
		var msg = JSON.stringify( { type: 'ping', data: 'ping' } );
		ws.send(msg, function () { });
	}
	
	//	Setup something to do every 1000ms
	var intervalID = setInterval(SendPing, 10000);
	
	
	//	If/When this connection drops, log and remove the interval function
	ws.on("close", function () {
		//console.log("websocket connection close...");
		clearInterval(intervalID);
		
		//	Remove client from clients
		var idx = clients.indexOf(ws.origin);
		if( idx > -1 )
			clients.splice(idx, 1);
	});
	
	//	If/When this connection drops, log and remove the interval function
	ws.on("error", function () {
		//console.log("websocket connection close...");
		clearInterval(intervalID);
		
		//	Remove client from clients
		var idx = clients.indexOf(ws.origin);
		if( idx > -1 )
			clients.splice(idx, 1);
	});
	
    // send back chat history
    if (history.length > 0) {
        ws.send( JSON.stringify( { type: 'history', data: history } ) );
		//console.log("Sending history size " + history.length );
    }
	
	ws.on("message", function (msg) {

		var msgData = JSON.parse(msg);	//{ user, message, timestamp }

		console.log("[" + msgData.timestamp + "] " + msgData.user + ": " + msgData.message);

		if (msgData.message == '/clients') {
			var timestamp = new Date().getTime();
			var newMsg = {user: "Server", message: clients.length + " chatters present!", timestamp: timestamp};
			wss.broadcast(JSON.stringify(newMsg));
		}
		else if (msgData.message == '/achievements') {
			showAchievements = !showAchievements;

			var timestamp = new Date().getTime();
			var newMsg = {
				user: "Server",
				message: showAchievements ? "Now Showing Achievements" : "Disabled Showing Achievements",
				timestamp: timestamp
			};
			wss.broadcast(JSON.stringify(newMsg));	//	ping it back out
		}
		else {
			history.push(msgData);
			history = history.slice(-100);

			wss.broadcast(msg);	//	ping it back out
		}
	});
});



//app.post('session', function (request, response) {
//	//	Add to gamesession...?
	
//)};