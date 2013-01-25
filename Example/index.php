<?php
/**
 * Dropbox Example usding SimpleOAuth and SimpleCurl
 * 
 * ----
 * 
 * Licensed under The GPL v3 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @package		Perecedero
 * @subpackage	examples
 * @license		GPL v3 License
 * @author		Ivan Lansky (@perecedero)
 * @link			https://github.com/perecedero/SimpleOAuth
 */


	//set your consumer_key and  shared_secret on Dropbox.php
	//set your real domain callback
	$callback = 'http://somedomain.local/SimpleOAuth/Example/index.php?state=getTokens';

	require_once 'Dropbox.php';
	$dropbox = new Dropbox();

	//auth the user
	if(!isset($_GET['state'])){
		
		$dropbox->auth($callback);
		
	} elseif ($_GET['state'] == 'getTokens' ) {
		$tokens = $dropbox->requestAccessToken();
		$dropbox->setTokens($tokens['oauth_token'], $tokens['oauth_token_secret']);
	}

	$res_get = $dropbox->getInfo();

	$res_put = $dropbox->putFile('file.txt');

	sleep(5);

	$dropbox->deleteFile('file.txt');

	echo 'Get Info response <br>';
	echo '<pre>'; print_r($res_get); echo '</pre><br><br>'; 

	echo 'Put File response <br>';
	echo '<pre>'; print_r($res_put); echo '</pre><br><br>'; 
?>
