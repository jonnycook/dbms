{Server:WebSocketServer, OPEN:OPEN} = require 'ws'
request = require 'request'

fs = require 'fs'

trunc = (message) ->
	for m, i in message
		if m.length > 20
			message[i] = m.substr(0, 20) + '...'
	message


https = require 'https'

httpsServer = https.createServer
	key:fs.readFileSync '/home/ec2-user/ssl_np.key'
	cert:fs.readFileSync '/home/ec2-user/ssl_certificate.crt'
httpsServer.listen 8080

wss = new WebSocketServer server:httpsServer



express = require 'express'
bodyParser = require 'body-parser'
request = require 'request'

app = express()
app.use bodyParser limit:'50mb'
app.listen 3001

process.on 'uncaughtException', (err) ->
	console.log err

app.post '/push', (req, res) ->
	for clientId in req.body.clientIds
		connections[clientId]?.send? 'u', req.body.update
	res.end()

class Connection
	constructor: (@ws) ->
		console.log 'new connection'

		ws.on 'close', =>
			@close()

		ws.on 'error', =>
			@close()

		ws.on 'message', (message) =>
			# console.log message
			if !@version
				@version = message
				console.log 'version', @version
			else if @version == '1'
				[messageId, code, params...] = message.split '\t'
				@onMessage messageId, code, params
			else
				console.log 'invalid version'

		counter = 0
		ws.on 'pong', ->
			--counter

		@pingTimerId = setInterval (=>
			if counter >= 2
				console.log 'timeout', @clientId
				@close()
			else
				++ counter
				ws.ping()
		), 30000


	_respond: (number, response...) ->
		# @ws.send "r\t#{number}\t#{response.join '\t'}"
		console.log "response #{number}"
		@send 'r', number, response...

	send: (message...) ->
		console.log 'send', @clientId, trunc message
		@ws.send message.join "\t"

	onMessage: (number, code, params) ->
		console.log 'message', code, params
		switch code
			when '1'
				@dbmsVersion = params[0]
				@clientId = params[1]
				@db = params[2]

				connections[@clientId] = @

				request.get "http://127.0.0.1/dbms/#{@dbmsVersion}/core/clientConnected.php?id=#{@clientId}", =>
					@_respond number

			when 'q'
				request.get "http://127.0.0.1/dbms/#{@dbmsVersion}/core/main.php?db=#{@db}&clientId=#{@clientId}&pull=1", (err, res, body) =>
					@_respond number, body

			when 'g'
				request.get "http://127.0.0.1/dbms/#{@dbmsVersion}/core/main.php?resource=#{params[0]}&db=#{@db}&clientId=#{@clientId}", (err, res, body) =>
					@_respond number, body

			when 'u'
				request.post "http://127.0.0.1/dbms/#{@dbmsVersion}/core/main.php?db=#{@db}&clientId=#{req.query.clientId}", form:{update:params[0]}, (err, res, body) =>
					@_respond number, body

			when 'U'
				request.get "http://127.0.0.1/dbms/#{@dbmsVersion}/core/clientReceivedUpdate.php?db=#{@db}&id=#{@clientId}&updates=#{params[0]}", (err, res, body) =>
					@_respond number, body

			when 'c'
				request.get "http://127.0.0.1/dbms/#{@dbmsVersion}/core/setClientParam.php?id=#{@clientId}&key=#{params[0]}&value=#{params[1]}", (err, res, body) =>
					@_respond number, body

	close: ->
		if !@closed
			@closed = true
			clearInterval @pingTimerId
			@ws.close()
			delete connections[@clientId]
			console.log 'close', @clientId
			request.get "http://127.0.0.1/dbms/#{@dbmsVersion}/core/clientDisconnected.php?id=#{@clientId}", (err, res, body) ->
				console.log body

connections = {}

wss.on 'connection', (ws) ->
	conn = new Connection ws
