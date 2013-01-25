<?php

/**
 * Dropbox REST API Comunication 
 * 
 * ----
 * 
 * Licensed under The GPL v3 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @package		Perecedero
 * @subpackage	API
 * @license		GPL v3 License
 * @author		Ivan Lansky (@perecedero)
 * @link			https://github.com/perecedero/SimpleOAuth
 */

class Dropbox
{
	/**
	 * Local Copy of SimpleCurl Lib
	 * 
	 * @var object
	 * @access public
	 */
	private $Curl = null;

	/**
	 * Local Copy of OAuth Lib
	 * 
	 * @var object
	 * @access public
	 */
	private $OAuth = null;

	/**
	 * App consumer key
	 * 
	 * @var string
	 * @access public
	 */
	public $consumer_key = 'your_app_consumer_key';

	/**
	 * App shared secret
	 * 
	 * @var object
	 * @access public
	 */
	public $shared_secret = 'your_app_shared_secret';

	/**
	 * Constructor.
	 *
	 * @param string $oauth_token. Authentified user token
	 * @param string $oauth_token_secret. Authentified user token secret
	 * @access public
	 */
	public function __construct( $oauth_token = null,  $oauth_token_secret = null)
	{
		require_once 'SimpleCurl.php';
		$this->Curl = new SimpleCurl();

		require_once 'SimpleOAuth.php';
		$this->OAuth = new SimpleOAuth( $this->consumer_key, $this->shared_secret );

		if ($oauth_token && $oauth_token_secret){
			$this->OAuth->setTokens($oauth_token, $oauth_token_secret);
		}
	}

	/**
	 * Start user authentification to get user tokens.
	 *
	 * @param string $callback. callback URL
	 * @access public
	 */
	public function auth ( $callback = '' )
	{
		//2 step flow for Dropbox (oAuth) Authentification
		$url = "https://api.dropbox.com/1/oauth/request_token";
		$url_step2 = "https://www.dropbox.com/1/oauth/authorize";

		//prepare oAuth for a new call request
		$this->OAuth->init();

		//calculate signature and set it to outh values
		$oauth_signature = $this->OAuth->calcSignature("POST", $url);
		$this->OAuth->setValues('oauth_signature', $oauth_signature);

		//create auth header
		$headers = $this->OAuth->makeHeader();

		//make the call with SimpleCurl
		$res =  $this->Curl->call(array(
			'url' => $url,
			'post' => "",
			'parse.body' => 'raw',
			'header' => array(
				'Content-Length: 0',
				'Authorization: '.$headers
			)
		));
		if( strpos($res, 'error') !== false){
			print_r($res); exit;
		}


		//parse & obtain the tokens on $tokens array
		$res = explode("&",$res);

		$tokens_aux = explode("=",$res[0]);
		$tokens['oauth_token_secret'] = $tokens_aux[1];

		$tokens_aux = explode("=",$res[1]);
		$tokens['oauth_token'] = $tokens_aux[1];

		//add temporal oauth_token_secret to callback
		$con = (strpos($callback, '?') === false)?'?':'&';
		$callback .=  $con . 'oauth_token_secret=' . $tokens['oauth_token_secret'];

		//redirect to Dropbox
		$this->redirect( $url_step2 . '?oauth_token=' . $tokens['oauth_token'] . '&oauth_callback='. urlencode($callback) );
	}

	/**
	 * Request for permanent tokens using temporal
	 * tokens returned in auth function
	 *
	 * @access public
	 */
	public function requestAccessToken()
	{
		$url = "https://api.dropbox.com/1/oauth/access_token";

		//get temporal tokens
		$oauth_token = $_GET['oauth_token'];
		$oauth_token_secret = $_GET['oauth_token_secret'];

		//set temporal tokens
		$this->OAuth->setTokens($oauth_token,$oauth_token_secret);

		//prepare oAuth for a new call request
		//this will use the tokens set with setTokens
		$this->OAuth->init();

		//calculate signature and set it to outh values
		$oauth_signature = $this->OAuth->calcSignature("POST", $url);
		$this->OAuth->setValues('oauth_signature', $oauth_signature);

		//create auth header
		$headers = $this->OAuth->makeHeader();

		//make the call with SimpleCurl
		$res = $this->Curl->call(array(
			'url' => $url,
			'post' => "",
			'parse.body' => 'raw',
			'header' => array(
				'Content-Length: 0',
				'Authorization: '.$headers
			)
		));
		if( strpos($res, 'error') !== false){
			print_r($res); exit;
		}

		//parse & obtain the tokens on $tokens array
		$res = explode("&",$res);

		$tokens_aux = explode("=",$res[0]);
		$tokens['oauth_token_secret'] = $tokens_aux[1];

		$tokens_aux = explode("=",$res[1]);
		$tokens['oauth_token'] = $tokens_aux[1];

		return $tokens;
	}


	/**
	 * Make a GET request to dropbox api using oAtuh and the permanent tokens
	 *
	 * @access public
	 */
	public function getInfo()
	{
		$url = "https://api.dropbox.com/1/account/info";

		//prepare oAuth for a new call request
		//this will use the tokens set with setTokens
		$this->OAuth->init();

		//calculate signature and set it to outh values
		$oauth_signature = $this->OAuth->calcSignature("GET", $url);
		$this->OAuth->setValues('oauth_signature', $oauth_signature);

		//create auth header
		$headers = $this->OAuth->makeHeader();

		//make the call with SimpleCurl
		$res = $this->Curl->call(array(
			'url' => $url,
			'parse.body' => 'json.associative',
			'header' => array( 'Authorization: '.$headers )
		));
		return $res;
	}

	/**
	 * Make a PUT request to dropbox api using oAtuh and the permanent tokens
	 *
	 * @param string $file. full path to the file to upload
	 * @access public
	 */
	public function putFile($file)
	{
		//fix for no occidental characers, basename and pathinfo remove them from $file
		$pathinfo = explode('/', $file);
		$name = array_pop($pathinfo);

		$url = "https://api-content.dropbox.com/1/files_put/sandbox/" . urlencode($name);

		//prepare oAuth for a new call request
		//this will use the tokens set with setTokens
		$this->OAuth->init();

		//calculate signature and set it to outh values
		$oauth_signature = $this->OAuth->calcSignature("PUT", $url);
		$this->OAuth->setValues('oauth_signature', $oauth_signature);

		//create auth header
		$headers = $this->OAuth->makeHeader();

		//make the call with SimpleCurl
		$res = $this->Curl->call(array(
			'url' => $url,
			'upload.file.PUT' => $file,
			'parse.body' => 'json.associative',
			'header' => array( 'Authorization: '.$headers )
		));
		return $res;
	}

	/**
	 * Make a POST request with parameters to dropbox api using oAtuh and the permanent tokens
	 *
	 * @param string $file. name of the file to delete
	 * @access public
	 */
	public function deleteFile($file)
	{
		$url = "https://api.dropbox.com/1/fileops/delete";

		//prepare oAuth for a new call request
		//this will use the tokens set with setTokens
		$this->OAuth->init();

		//set the parameter to send via POST 
		$this->OAuth->setParams("root","sandbox");
		$this->OAuth->setParams("path", $file);

		//calculate signature and set it to outh values
		$oauth_signature = $this->OAuth->calcSignature("POST", $url);
		$this->OAuth->setValues('oauth_signature', $oauth_signature);

		//create auth header
		$headers = $this->OAuth->makeHeader();

		//create post body with params set with setParams
		$post = $this->OAuth->getPostBody();

		//make the call with SimpleCurl
		$res = $this->Curl->call(array(
			'url' => $url,
			'post' => $post,
			'header' => array( 'Authorization: '.$headers )
		));
		return $res;
	}

	//------------------

	public function  setTokens($oauth_token, $oauth_token_secret)
	{
		//set temporal tokens
		$this->OAuth->setTokens($oauth_token, $oauth_token_secret);
	}


	public function  redirect($url)
	{
		header('HTTP/1.1 302 redirect', true);
		header('Status: 302 redirect', true);
		header('Location: ' . $url, true);
		exit;
	}

}
?>
