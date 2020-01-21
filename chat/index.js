require('dotenv').config({ path: `${__dirname}/.env` });

const http = require('http');
const express = require('express');
const redis = require('redis');
const logger = require('pino')({
  useLevelLabels: true,
  timestamp: () => `,"time":"${new Date()}"`,
});
const WebSocketServer = require('ws').Server;

const app = express();
app.set('port', process.env.WEBSOCKET_PORT);

const server = http.createServer(app);
server.listen(app.get('port'));

const wss = new WebSocketServer({ server }, () => {
});
logger.info('Websocket listening on %d', app.get('port'));

const client = redis.createClient(process.env.REDIS_PORT, '127.0.0.1');
client.subscribe('earnachievement');
client.on('message', (channel, message) => {
  // logger.info(`Received message ${message}`);
  wss.onearnachievement(message);
});

const clients = [];

wss.broadcast = function broadcast(data) {
  // logger.info('broadcast to %d clients', wss.clients.size);
  wss.clients.forEach((connectedClient) => {
    if (connectedClient.readyState === connectedClient.OPEN) {
      connectedClient.send(data);
    }
  });
};

let showAchievements = false;

wss.onearnachievement = (achData) => {
  if (showAchievements) {
    const timestamp = new Date().getTime();
    const newMsg = { user: 'RetroAchievements', message: achData, timestamp };
    wss.broadcast(JSON.stringify(newMsg)); // ping it back out
  }
};

let history = [{ user: 'Server', message: 'Server Started!', timestamp: new Date().getTime() }];

wss.on('connection', (ws) => {
  clients.push(ws);

  function SendPing() {
    const msg = JSON.stringify({ type: 'ping', data: 'ping' });
    ws.send(msg, () => {
    });
  }

  // Setup something to do every 1000ms
  const intervalID = setInterval(SendPing, 10000);

  // If/When this connection drops, log and remove the interval function
  ws.on('close', () => {
    logger.info('websocket connection close...');
    clearInterval(intervalID);

    // Remove client from clients
    const idx = clients.indexOf(ws);
    if (idx > -1) clients.splice(idx, 1);
  });

  // If/When this connection drops, log and remove the interval function
  ws.on('error', () => {
    // logger.info("websocket connection close...");
    clearInterval(intervalID);

    // Remove client from clients
    const idx = clients.indexOf(ws);
    if (idx > -1) clients.splice(idx, 1);
  });

  // send back chat history
  if (history.length > 0) {
    ws.send(JSON.stringify({ type: 'history', data: history }));
    // logger.info("Sending history size " + history.length );
  }

  ws.on('message', (msg) => {
    const msgData = JSON.parse(msg); // { user, message, timestamp }

    // logger.info(`[${msgData.timestamp}] ${msgData.user}: ${msgData.message}`);
    const timestamp = new Date().getTime();

    let newMsg;
    if (msgData.message === '/clients') {
      newMsg = { user: 'Server', message: `${clients.length} chatters present!`, timestamp };
      wss.broadcast(JSON.stringify(newMsg));
    } else if (msgData.message === '/achievements') {
      showAchievements = !showAchievements;
      newMsg = {
        user: 'Server',
        message: showAchievements ? 'Now Showing Achievements' : 'Disabled Showing Achievements',
        timestamp,
      };
      wss.broadcast(JSON.stringify(newMsg)); // ping it back out
    } else {
      history.push(msgData);
      history = history.slice(-100);
      wss.broadcast(msg); // ping it back out
    }
  });
});
