# What is SimpleOAuth

SimpleOAuth is a easy way to create oAuth Authorization header for your APIs calls


## Requirements

 PHP 5+

## Usage

Basic Usage

	<?php
		require_once 'SimpleOAuth.php';
		$oauth = new SimpleOAuth( 'your_app_consumer_key', 'your_app_shared_secret' );

		//URL where make the request
		$url = 'https://api.dropbox.com/1/oauth/request_token';
		
		//prepare oAuth for a new call request
		$oauth->init();

		//calculate signature and set it to oauth values
		$oauth_signature = $oauth->calcSignature("PUT", $url);
		$oauth->setValues('oauth_signature', $oauth_signature);

		//create auth header
		$headers = $oauth->makeHeader();

		//output header
		echo  $headers;

Output

		OAuth oauth_signature_method="HMAC-SHA1", oauth_timestamp="1359070193", oauth_consumer_key="your_app_consumer_key", oauth_version="1.0", oauth_signature="r%2BRCWEeqAMZ%2FuDWTMWG01DYAIUo%3D", oauth_nonce="1359070193.4848"


URL with parameters and using user tokens

	<?php
		require_once 'SimpleOAuth.php';
		$oauth = new SimpleOAuth( 'your_app_consumer_key', 'your_app_shared_secret' );
		$oauth->setTokens('user_oauth_token', 'user_oauth_token_secret');

		//URL where make the request
		$url = 'https://api.dropbox.com/1/fileops/delete';
		
		//prepare oAuth for a new call request
		$oauth->init();

		//set the parameters that will be send via POST 
		$this->OAuth->setParams("root","sandbox");
		$this->OAuth->setParams("path", 'file.txt');

		//calculate signature and set it to oauth values
		$oauth_signature = $oauth->calcSignature("POST", $url);
		$oauth->setValues('oauth_signature', $oauth_signature);

		//create auth header
		$headers = $oauth->makeHeader();

		//get post body set on setParams
		$post = $this->OAuth->getPostBody();

		//output header && post
		echo  $headers;
		echo  $post;

Output

		OAuth oauth_timestamp="1359070783", oauth_consumer_key="your_app_consumer_key", oauth_version="1.0", oauth_token="user_oauth_token", oauth_nonce="1359070783.1928", oauth_signature="rQDUamGdviBMmFwozVdcYaKsj%2BI%3D", oauth_signature_method="HMAC-SHA1"
		root=sandbox&path=file.txt

Response report

## Example

On the Example folder there is a Dropbox example that use a combination of SimpleOAuth and SimpleCurl. This example made a user authentification obtaining user tokens, and then save and remove a file.
Set your real callback url on Index.php and also your app_consumer_key and  app_shared_secret on Dropbox.php before run the example
