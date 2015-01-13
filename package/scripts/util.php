<?php

require_once 'aps/2/runtime.php';
require_once 'api-client/autoload.php';

define('APS_DEVELOPMENT_MODE', 'on'); //dev mode

function getService($globals) {
	$l = \APS\Logger::get();
	static $service = null;
	if ($service !== null)
		return $service;
	$client = new Google_Client();
	$client->setApplicationName('GCalendar APS package');
	$service = new Google_Service_Calendar($client);
	$creds = new Google_Auth_AssertionCredentials($globals->serviceAccountName, array('https://www.googleapis.com/auth/calendar'), base64_decode(file_get_contents($globals->serviceAccountName)));
	$client->setAssertionCredentials($creds);
	if ($client->getAuth()->isAccessTokenExpired()) {
		$l->debug('Requesting new token...');
		$client->getAuth()->refreshTokenWithAssertion($creds);
	}
	return $service;
}

function removeOldEvents($globals) {
	$s = getService($globals);
	$c = \APS\Request::getController();
	$l = \APS\Logger::get();
	$l->debug('Removing old events...');
	foreach ($globals->contexts as $v) {
		foreach ($v->calendars as $v1) {
			foreach ($v1->events as $v2) {
				if ($v->eventTTL) {
					$s->events->delete($v1->googleId, $v2->googleId, array('sendNotifications' => false));
					$c->unregisterResource($v2);
				}
			}
		}
	}
}

if (!(array_key_exists('PROJECTS', $_SERVER) && (strpos($_SERVER['PROJECTS'], ' build ') === 0)))
	\APS\Logger::get()->setLogFile('log');
