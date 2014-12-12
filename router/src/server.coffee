{Server:WebSocketServer, OPEN:OPEN} = require 'ws'
request = require 'request'

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
	constructor: (@ws) ->
		console.log 'new connection'

		ws.on 'close', =>
			@close()

		ws.on 'error', =>
			@close()

		ws.on 'message', (message) =>
			[messageId, code, params...] = message.split '\t'
			@onMessage messageId, code, params

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
		), 5000


	_respond: (number, response...) ->
		# @ws.send "r\t#{number}\t#{response.join '\t'}"
		@send 'r', number, response...

	send: (message...) ->
		console.log 'send', @clientId, message
		@ws.send message.join "\t"

	onMessage: (number, code, params) ->
		console.log 'message', code, params
		switch code
			when '1'
				@clientId = params[0]
				connections[@clientId] = @
				@db = params[1]
				request.get "http://127.0.0.1:3000/client/connected?id=#{@clientId}", =>
					@_respond number

			when 'q'
				console.log @db, @clientId
				request.get "http://127.0.0.1:3000/pull?db=#{@db}&clientId=#{@clientId}", (err, res, body) =>
					@_respond number, body

			when 'g'
				request.get "http://127.0.0.1:3000#{params[0]}?db=#{@db}&clientId=#{@clientId}", (err, res, body) =>
					@_respond number, body

			when 'u'
				request.post "http://127.0.0.1:3000/?db=#{@db}&clientId=#{@clientId}", form:{update:params[0]}, (err, res, body) =>
					@_respond number, body

	close: ->
		if !@closed
			@closed = true
			clearInterval @pingTimerId
			@ws.close()
			delete connections[@clientId]
			console.log 'close', @clientId
			request.get "http://127.0.0.1:3000/client/disconnected?id=#{@clientId}", (err, res, body) ->
				console.log body

connections = {}

wss.on 'connection', (ws) ->
	conn = new Connection ws
