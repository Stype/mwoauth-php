<?php

include 'MWOAuthClient.php';

$consumerKey = 'b02833d71fe5700c891c531fe041c36c';
$consumerSecret = 'f2b6f6ccc47e9f6b298eaa74789897b98dc6a594';

$config = new MWOAuthClientConfig( 'https://localhost/wiki/index.php?title=Special:OAuth', true, false );
$config->canonicalServerUrl = 'http://localhost';
$cmrToken = new OAuthToken( $consumerKey, $consumerSecret );
$client = new MWOAuthClient( $config, $cmrToken );
list( $redir, $requestToken ) = $client->initiate();

echo "Point your browser to: $redir\n\n";
print "Enter the verification code:\n";
$fh = fopen( "php://stdin", "r" );
$line = fgets( $fh );

$accessToken = $client->complete( $requestToken, trim( $line ) );

// If we want to authenticate the user
$identity = $client->identify( $accessToken );
echo "Authenticated user {$identity->username}\n";

// Do a simple API call
echo "Getting user info: ";
echo $client->makeOAuthCall(
	$accessToken,
	'https://localhost/wiki/api.php?action=query&meta=userinfo&uiprop=rights&format=json'
);

// Make an Edit
$editToken = json_decode( $client->makeOAuthCall(
	$accessToken,
	'https://localhost/wiki/api.php?action=tokens&format=json'
) )->tokens->edittoken;

$apiParams = array(
	'action' => 'edit',
	'title' => 'Talk:Main_Page',
	'section' => 'new',
	'summary' => 'Hello World',
	'text' => 'Hi',
	'token' => $editToken,
	'format' => 'json',
);

$client->setExtraParams( $apiParams ); // sign these too

echo $client->makeOAuthCall(
	$accessToken,
	'https://localhost/wiki/api.php',
	true,
	$apiParams
);

