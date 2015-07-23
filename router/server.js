// Generated by CoffeeScript 1.8.0
var Connection, OPEN, WebSocketServer, app, bodyParser, connections, env, express, fs, https, httpsServer, request, trunc, wss, _ref,
  __slice = [].slice;

_ref = require('ws'), WebSocketServer = _ref.Server, OPEN = _ref.OPEN;

request = require('request');

env = require('./env');

fs = require('fs');

trunc = function(message) {
  var i, m, response, _i, _len;
  response = [];
  for (i = _i = 0, _len = message.length; _i < _len; i = ++_i) {
    m = message[i];
    if (m.length > 20) {
      response[i] = m.substr(0, 20) + '...';
    } else {
      response[i] = m;
    }
  }
  return response;
};

wss = null;

switch (env) {
  case 'prod':
    https = require('https');
    httpsServer = https.createServer({
      key: fs.readFileSync('/home/ec2-user/ssl_np.key'),
      cert: fs.readFileSync('/home/ec2-user/ssl_certificate.crt')
    });
    httpsServer.listen(8080);
    wss = new WebSocketServer({
      server: httpsServer
    });
    break;
  case 'dev':
    wss = new WebSocketServer({
      port: 8080
    });
}

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
        if (!_this.version) {
          _this.version = message;
          return console.log('version', _this.version);
        } else if (_this.version === '1') {
          _ref1 = message.split('\t'), messageId = _ref1[0], code = _ref1[1], params = 3 <= _ref1.length ? __slice.call(_ref1, 2) : [];
          return _this.onMessage(messageId, code, params);
        } else {
          return console.log('invalid version');
        }
      };
    })(this));
    counter = 0;
    ws.on('pong', function() {
      return --counter;
    });
    this.pingTimerId = setInterval(((function(_this) {
      return function() {
        if (counter >= 2) {
          console.log('timeout', _this.clientId);
          return _this.close();
        } else {
          ++counter;
          return ws.ping();
        }
      };
    })(this)), 30000);
  }

  Connection.prototype._respond = function() {
    var number, response;
    number = arguments[0], response = 2 <= arguments.length ? __slice.call(arguments, 1) : [];
    return this.send.apply(this, ['r', number].concat(__slice.call(response)));
  };

  Connection.prototype.send = function() {
    var message;
    message = 1 <= arguments.length ? __slice.call(arguments, 0) : [];
    console.log("[" + this.clientId + "]", 'send', trunc(message));
    return this.ws.send(message.join("\t"));
  };

  Connection.prototype.onMessage = function(number, code, params) {
    console.log("[" + this.clientId + "]", 'message', number, code, params);
    switch (code) {
      case '1':
        this.dbmsVersion = params[0];
        this.clientId = params[1];
        this.db = params[2];
        this.schemaSchema = params[3];
        connections[this.clientId] = this;
        return request.get("http://127.0.0.1/dbms/" + this.dbmsVersion + "/core/clientConnected.php?id=" + this.clientId, (function(_this) {
          return function() {
            return _this._respond(number);
          };
        })(this));
      case 'q':
        return request.get("http://127.0.0.1/dbms/" + this.dbmsVersion + "/core/main.php?db=" + this.db + "&schemaSchema=" + this.schemaSchema + "&clientId=" + this.clientId + "&pull=1", (function(_this) {
          return function(err, res, body) {
            return _this._respond(number, body);
          };
        })(this));
      case 'g':
        return request.get("http://127.0.0.1/dbms/" + this.dbmsVersion + "/core/main.php?resource=" + params[0] + "&db=" + this.db + "&schemaSchema=" + this.schemaSchema + "&clientId=" + this.clientId, (function(_this) {
          return function(err, res, body) {
            return _this._respond(number, body);
          };
        })(this));
      case 'u':
        return request.post("http://127.0.0.1/dbms/" + this.dbmsVersion + "/core/main.php?db=" + this.db + "&schemaSchema=" + this.schemaSchema + "&clientId=" + this.clientId, {
          form: {
            update: params[0]
          }
        }, (function(_this) {
          return function(err, res, body) {
            return _this._respond(number, body);
          };
        })(this));
      case 'U':
        return request.get("http://127.0.0.1/dbms/" + this.dbmsVersion + "/core/clientReceivedUpdate.php?db=" + this.db + "&schemaSchema=" + this.schemaSchema + "&id=" + this.clientId + "&updates=" + params[0], (function(_this) {
          return function(err, res, body) {
            return _this._respond(number, body);
          };
        })(this));
      case 'c':
        return request.get("http://127.0.0.1/dbms/" + this.dbmsVersion + "/core/setClientParam.php?id=" + this.clientId + "&key=" + params[0] + "&value=" + params[1], (function(_this) {
          return function(err, res, body) {
            return _this._respond(number, body);
          };
        })(this));
    }
  };

  Connection.prototype.close = function() {
    if (!this.closed) {
      this.closed = true;
      clearInterval(this.pingTimerId);
      this.ws.close();
      delete connections[this.clientId];
      console.log('close', this.clientId);
      return request.get("http://127.0.0.1/dbms/" + this.dbmsVersion + "/core/clientDisconnected.php?id=" + this.clientId, function(err, res, body) {
        return console.log(body);
      });
    }
  };

  return Connection;

})();

connections = {};

wss.on('connection', function(ws) {
  var conn;
  return conn = new Connection(ws);
});

//# sourceMappingURL=server.js.map
