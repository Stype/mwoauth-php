<?php

include 'MWOAuthClient.php';
$consumerKey = '';
$consumerSecret = '';

// Configure the connection to the wiki you want to use. Passing title=Special:OAuth as a
// GET parameter makes the signature easier. Otherwise you need to call
// $client->setExtraParam('title','Special:OAuth/whatever') for each step.
// If your wiki uses wgSecureLogin, the canonicalServerUrl will point to http://
$config = new MWOAuthClientConfig(
	'http://en.wikipedia.beta.wmflabs.org/w/index.php?title=Special:OAuth', // url to use
	false, // do we use SSL? (we should probably detect that from the url)
	false // do we validate the SSL certificate? Always use 'true' in production.
);
$config->canonicalServerUrl = 'http://en.wikipedia.beta.wmflabs.org';
// Optional clean url here (i.e., to work with mobile), otherwise the
// base url just has /authorize& added
$config->redirURL = 'http://en.wikipedia.beta.wmflabs.org/wiki/Special:OAuth/authorize?';
$cmrToken = new OAuthToken( $consumerKey, $consumerSecret );
$client = new MWOAuthClient( $config, $cmrToken );

session_start();

if ( !isset( $_GET['action'] ) ) {
	echo "<html><body><p><a href='webdemo.php?action=init'>Start OAuth</a></p></body></html>";
	exit;
} else {
	$action = $_GET['action'];
}


if ( $action == 'init' ) {

	// Step 1 - Get a request token
	list( $redir, $requestToken ) = $client->initiate();
	$_SESSION['oauthreqtoken'] = "{$requestToken->key}:{$requestToken->secret}";

	// Step 2 - Have the user authorize your app.
	header( "Location: $redir" );
	exit;

} elseif ( $action == 'finish' ) {

	$verifyCode = $_GET['oauth_verifier'];
	$recKey = $_GET['oauth_token'];
	list( $requestKey, $requestSecret ) = explode( ':', $_SESSION['oauthreqtoken'] );
	$requestToken = new OAuthToken( $requestKey, $requestSecret );
	unset( $_SESSION['oauthreqtoken'] );

	//check for csrf
	if ( $requestKey !== $recKey ) {
		die( "CSRF detected" );
	}

	// Step 3 - Exchange the request token and verification code for an access token
	$accessToken = $client->complete( $requestToken,  $verifyCode );

	// You're done! Setup your application's session state. Keep the accessToken
	// to use for later calls by your application into MediaWiki.
	session_regenerate_id();
	$identity = $client->identify( $accessToken );
	$_SESSION['oauthtoken'] = "{$accessToken->key}:{$accessToken->secret}";
	$_SESSION['username'] = $identity->username;

	// Redirect to your application's main entry point
	header( "Location: webdemo.php?action=info" );

	exit;
} elseif ( $action == 'info' ) {
	// This is what you're app should do for logged in users
	if ( !isset( $_SESSION['username'] ) || !isset( $_SESSION['oauthtoken'] ) ) {
		die( "Lost Session, <a href='webdemo.php?action=init'>start over</a>" );
	}

	list( $accessKey, $accessSecret ) = explode( ':', $_SESSION['oauthtoken'] );
	$accessToken = new OAuthToken( $accessKey, $accessSecret );

	// Check their current identity
	$identity = $client->identify( $accessToken );
	echo "<pre>\n";
	echo "Authenticated as user {$identity->username}\n";

	// Do a simple API call as the user
	echo "Getting user info: ";
	echo $client->makeOAuthCall(
		$accessToken,
		'https://localhost/wiki/api.php?action=query&meta=userinfo&uiprop=rights&format=json'
	);
	echo "</pre>\n";
	echo "<a href='webdemo.php?action=logout'>Logout</a>";
	exit;

} elseif ( $action == 'logout' ) {
	session_destroy();
	echo "<a href='webdemo.php?action=init'>Start Over</a>";
}

