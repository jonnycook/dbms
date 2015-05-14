<?php

$route = '/:model/:id';
$resource = '/Model/1';

preg_match_all('/:([a-z]+)/', $route, $matches);
$paramNames = $matches[1];

$pattern = preg_replace_callback('/:([a-z]+)/', function($matches) {
	if ($matches[1] == 'model') {
		return '(Model)';
	}
	else {
		return '(.*?)';
	}
}, $route);


if (preg_match("#^$pattern\$#", $resource, $matches)) {
	foreach ($paramNames as $i => $paramName) {
		$params[$paramName] = $matches[$i + 1];
	}
}