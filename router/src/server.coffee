{Server:WebSocketServer, OPEN:OPEN} = require 'ws'
request = require 'request'

env = require './env'

fs = require 'fs'

trunc = (message) ->
	response = []
	for m, i in message
		if m.length > 20
			response[i] = m.substr(0, 20) + '...'
		else
			response[i] = m
	response


wss = null

switch env
	when 'prod'
		https = require 'https'

		httpsServer = https.createServer
			key:fs.readFileSync '/home/ec2-user/ssl_np.key'
			cert:fs.readFileSync '/home/ec2-user/ssl_certificate.crt'
		httpsServer.listen 8080

		wss = new WebSocketServer server:httpsServer
	when 'dev'
		wss = new WebSocketServer port:8080



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
	@id: 1
	constructor: (@ws) ->
		@id = Connection.id++
		console.log 'new connection', @id

		ws.on 'close', =>
			@close 'connectionEnded'

		ws.on 'error', =>
			@close 'error'

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
				@close 'timeout'
			else
				++ counter
				ws.ping()
		), 1000*5


	_respond: (number, code, response...) ->
		# @ws.send "r\t#{number}\t#{response.join '\t'}"
		# console.log "response #{number}"
		@send 'r', number, code, response...

	send: (message...) ->
		console.log "[#{@clientId}]", 'send', trunc message
		@ws.send message.join "\t"

	onMessage: (number, code, params) ->
		console.log "[#{@clientId}]", 'message', number, code, params
		switch code
			when '1'
				@dbmsVersion = params[0]
				@clientId = params[1]
				@db = params[2]
				@schemaVersion = params[3]

				connections[@clientId] = @

				request.get "http://127.0.0.1/dbms/#{@dbmsVersion}/core/clientConnected.php?id=#{@clientId}",(err, res, body) =>
					if body == 'invalidClientId'
						@_respond number, 1
					else
						@_respond number, 0

			when 'q'
				request.get "http://127.0.0.1/dbms/#{@dbmsVersion}/core/main.php?db=#{@db}&schemaVersion=#{@schemaVersion}&clientId=#{@clientId}&pull=1", (err, res, body) =>
					if body == 'invalidClientId'
						@_respond number, 1
					else
						@_respond number, 0, body

			when 'g'
				request.get "http://127.0.0.1/dbms/#{@dbmsVersion}/core/main.php?resource=#{params[0]}&db=#{@db}&schemaVersion=#{@schemaVersion}&clientId=#{@clientId}", (err, res, body) =>
					if body == 'invalidClientId'
						@_respond number, 1
					else
						@_respond number, 0, body

			when 'u'
				request.post "http://127.0.0.1/dbms/#{@dbmsVersion}/core/main.php?db=#{@db}&schemaVersion=#{@schemaVersion}&clientId=#{@clientId}", form:{update:params[0]}, (err, res, body) =>
					if body == 'invalidClientId'
						@_respond number, 1
					else
						@_respond number, 0, body

			when 'U'
				request.get "http://127.0.0.1/dbms/#{@dbmsVersion}/core/clientReceivedUpdate.php?db=#{@db}&schemaVersion=#{@schemaVersion}&id=#{@clientId}&updates=#{params[0]}", (err, res, body) =>
					if body == 'invalidClientId'
						@_respond number, 1
					else
						@_respond number, 0

			when 'c'
				request.get "http://127.0.0.1/dbms/#{@dbmsVersion}/core/setClientParam.php?id=#{@clientId}&key=#{params[0]}&value=#{params[1]}", (err, res, body) =>
					if body == 'invalidClientId'
						@_respond number, 1
					else
						@_respond number, 0



	close: (reason) ->
		console.log "CLOSE #{@id} #{reason}"
		if !@closed
			@closed = true
			clearInterval @pingTimerId
			@ws.close()
			if connections[@clientId] == @
				delete connections[@clientId]
				console.log 'close', reason, @clientId
				request.get "http://127.0.0.1/dbms/#{@dbmsVersion}/core/clientDisconnected.php?id=#{@clientId}", (err, res, body) ->
					console.log body

connections = {}

wss.on 'connection', (ws) ->
	conn = new Connection ws
