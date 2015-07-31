<?php

function zendeskClient() {
	$zendeskClient = new ZendeskAPI('divvydose', 'jlorentzen@divvydose.com');
	$zendeskClient->setAuth('token', '1icflbgcgEIz9T7TV2viqkLFB2vF5TY93tZwNx9F');
	return $zendeskClient;
}
