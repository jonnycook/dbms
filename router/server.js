// Generated by CoffeeScript 1.8.0
var Connection, OPEN, WebSocketServer, app, bodyParser, connections, express, request, wss, _ref,
  __slice = [].slice;

_ref = require('ws'), WebSocketServer = _ref.Server, OPEN = _ref.OPEN;

request = require('request');

wss = new WebSocketServer({
  port: 8080
});

express = require('express');

bodyParser = require('body-parser');

request = require('request');

app = express();

app.use(bodyParser({
  limit: '50mb'
}));

app.listen(3001);

process.on('uncaughtException', function(err) {
  return console.log(err);
});

app.post('/push', function(req, res) {
  var clientId, _i, _len, _ref1, _ref2;
  _ref1 = req.body.clientIds;
  for (_i = 0, _len = _ref1.length; _i < _len; _i++) {
    clientId = _ref1[_i];
    if ((_ref2 = connections[clientId]) != null) {
      if (typeof _ref2.send === "function") {
        _ref2.send('u', req.body.update);
      }
    }
  }
  return res.end();
});

Connection = (function() {
  function Connection(ws) {
    var counter;
    this.ws = ws;
    console.log('new connection');
    ws.on('close', (function(_this) {
      return function() {
        return _this.close();
      };
    })(this));
    ws.on('error', (function(_this) {
      return function() {
        return _this.close();
      };
    })(this));
    ws.on('message', (function(_this) {
      return function(message) {
        var code, messageId, params, _ref1;
        _ref1 = message.split('\t'), messageId = _ref1[0], code = _ref1[1], params = 3 <= _ref1.length ? __slice.call(_ref1, 2) : [];
        return _this.onMessage(messageId, code, params);
      };
    })(this));
    counter = 0;
    ws.on('pong', function() {
      return --counter;
    });
    this.pingTimerId = setInterval(((function(_this) {
      return function() {
        if (counter >= 2) {
          return _this.close();
        } else {
          ++counter;
          return ws.ping();
        }
      };
    })(this)), 5000);
  }

  Connection.prototype._respond = function() {
    var number, response;
    number = arguments[0], response = 2 <= arguments.length ? __slice.call(arguments, 1) : [];
    return this.send.apply(this, ['r', number].concat(__slice.call(response)));
  };

  Connection.prototype.send = function() {
    var message;
    message = 1 <= arguments.length ? __slice.call(arguments, 0) : [];
    console.log('send', this.clientId, message);
    return this.ws.send(message.join("\t"));
  };

  Connection.prototype.onMessage = function(number, code, params) {
    console.log('message', code, params);
    switch (code) {
      case '1':
        this.clientId = params[0];
        connections[this.clientId] = this;
        this.db = params[1];
        return request.get("http://127.0.0.1:3000/client/connected?id=" + this.clientId, (function(_this) {
          return function() {
            return _this._respond(number);
          };
        })(this));
      case 'q':
        console.log(this.db, this.clientId);
        return request.get("http://127.0.0.1:3000/pull?db=" + this.db + "&clientId=" + this.clientId, (function(_this) {
          return function(err, res, body) {
            return _this._respond(number, body);
          };
        })(this));
      case 'g':
        return request.get("http://127.0.0.1:3000" + params[0] + "?db=" + this.db + "&clientId=" + this.clientId, (function(_this) {
          return function(err, res, body) {
            return _this._respond(number, body);
          };
        })(this));
      case 'u':
        return request.post("http://127.0.0.1:3000/?db=" + this.db + "&clientId=" + this.clientId, {
          form: {
            update: params[0]
          }
        }, (function(_this) {
          return function(err, res, body) {
            return _this._respond(number, body);
          };
        })(this));
    }
  };

  Connection.prototype.close = function() {
    clearTimeout(this.pingTimerId);
    this.ws.close();
    delete connections[this.clientId];
    console.log('close', this.clientId);
    return request.get("http://127.0.0.1:3000/client/disconnected?id=" + this.clientId, function(err, res, body) {
      return console.log(body);
    });
  };

  return Connection;

})();

connections = {};

wss.on('connection', function(ws) {
  var conn;
  return conn = new Connection(ws);
});

//# sourceMappingURL=server.js.map
