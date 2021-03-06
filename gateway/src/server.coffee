express = require 'express'
bodyParser = require 'body-parser'
request = require 'request'

app = express()
app.use bodyParser limit:'50mb'
app.listen 3000

app.get '/client/connected', (req, res) ->
	request "http://localhost/dbms/core/clientConnected.php?id=#{req.query.id}&token=#{req.query.token}", (err, httpRes, body) ->
		res.end()

app.get '/client/disconnected', (req, res) ->
	request "http://localhost/dbms/core/clientDisconnected.php?id=#{req.query.id}",(err, httpRes, body) ->
		res.send body
		# res.end()

app.get '/pull', (req, res) ->
	console.log req.query
	request "http://localhost/dbms/core/main.php?db=#{req.query.db}&clientId=#{req.query.clientId}&pull=1", (err, httpRes, body) ->
		res.send body

app.get '/schema', (req, res) ->
	res.header 'Access-Control-Allow-Origin', '*'
	request "http://localhost/dbms/core/main.php?schema=1&db=#{req.query.db}", (err, httpRes, body) ->
		res.send body

app.get '*', (req, res) ->
	request "http://localhost/dbms/core/main.php?resource=#{req.path}&db=#{req.query.db}&clientId=#{req.query.clientId}", (err, httpRes, body) ->
		res.send body

app.post '/', (req, res) ->
	console.log req.body.update
	request.post "http://localhost/dbms/core/main.php?db=#{req.query.db}&clientId=#{req.query.clientId}", form:{update:req.body.update}, (err, httpResponse, body) ->
		res.send body
