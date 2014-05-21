<?php

include 'MWOAuthClient.php';

$consumerKey = 'b02833d71fe5700c891c531fe041c36c';
$consumerSecret = 'f2b6f6ccc47e9f6b298eaa74789897b98dc6a594';

// Configure the connection to the wiki you want to use. Passing title=Special:OAuth as a
// GET parameter makes the signature easier. Otherwise you need to call
// $client->setExtraParam('title','Special:OAuth/whatever') for each step.
// If your wiki uses wgSecureLogin, the canonicalServerUrl will point to http://
$config = new MWOAuthClientConfig(
	'https://localhost/wiki/index.php?title=Special:OAuth', // url to use
	true, // do we use SSL? (we should probably detect that from the url)
	false // do we validate the SSL certificate? Always use 'true' in production.
);
$config->canonicalServerUrl = 'http://localhost';

$cmrToken = new OAuthToken( $consumerKey, $consumerSecret );
$client = new MWOAuthClient( $config, $cmrToken );

// Step 1 - Get a request token
list( $redir, $requestToken ) = $client->initiate();

// Step 2 - Have the user authorize your app. Get a verifier code from them.
// (if this was a webapp, you would redirect your user to $redir, then use the 'oauth_verifier'
// GET parameter when the user is redirected back to the callback url you registered.
echo "Point your browser to: $redir\n\n";
print "Enter the verification code:\n";
$fh = fopen( "php://stdin", "r" );
$verifyCode = trim( fgets( $fh ) );

// Step 3 - Exchange the request token and verification code for an access token
$accessToken = $client->complete( $requestToken,  $verifyCode );

// You're done! You can now identify the user, and/or call the API (examples below) with $accessToken


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

