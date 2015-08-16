<?php

function zendeskClient() {
	$zendeskClient = new Zendesk\API\Client('divvydose', 'jlorentzen@divvydose.com');
	$zendeskClient->setAuth('token', '1icflbgcgEIz9T7TV2viqkLFB2vF5TY93tZwNx9F');
	return $zendeskClient;
}


function zenDeskUser($email, $name) {
	$zendeskClient = zendeskClient();
	$response = $zendeskClient->users()->search(array('query' => $email));
	if ($response->users) {
		return $response->users[0]->id;
	}
	else {
		$response = $zendeskClient->users()->create(array('name' => $name, 'email' => $email, 'verified' => true));
		$response->user->id;
	}
}