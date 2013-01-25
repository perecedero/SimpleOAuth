<?php

/**
 * SimpleOAuth - easy way to create oAuth Authorization header for your APIs calls
 * 
 * ----
 * 
 * Licensed under The GPL v3 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @package		Perecedero
 * @subpackage	misc
 * @license		GPL v3 License
 * @author		Ivan Lansky (@perecedero)
 * @link			https://github.com/perecedero/SimpleOAuth
 */
 
class SimpleOAuth
{
	/**
	 * Strores the parameters pased by GET or POST
	 *
	 * @var array
	 * @access private
	 */
	private $oauth_params = array();

	/**
	 * Strores the values of oauth header parts (oauth_nonce,
	 * oauth_signature_method, etc) 
	 *
	 * @var array
	 * @access private
	 */
	private $oauth_values = array();

	/**
	 * Strores aplication keys
	 *
	 * @var array
	 * @access private
	 */
	private $keys = array('consumer_key'=>null, 'shared_secret'=>null);

	/**
	 * Strores the user tokens
	 *
	 * @var array
	 * @access private
	 */
	private $tokens = null;

	/**
	 * Construct the object with app keys
	 *
	 * @param string $consumer_key. 
	 * @param string $shared_secret. 
	 * @access public
	 */
	public function __construct($consumer_key = null, $shared_secret = null)
	{
		$this->keys['consumer_key'] = $consumer_key;
		$this->keys['shared_secret'] = $shared_secret;
	}

	/**
	 * Set User tokens
	 *
	 * @param string $oauthToken. 
	 * @param string $oauthTokenSecret. 
	 * @access public
	 */
	public function setTokens($oauthToken, $oauthTokenSecret)
	{
		$this->tokens = array('oauth_token'=>$oauthToken, 'oauth_token_secret'=>$oauthTokenSecret);
	}

	/**
	 * Prepare the object to generate a new auth header
	 * if user tokens are present they will be used too 
	 *
	 * @access public
	 */
	public function init()
	{
		date_default_timezone_set('UTC');

		$timestamp = strtotime("now");
		$nonce = $timestamp + $this->randomFloat();

		//load default values
		$this->oauth_params =array();

		$this->oauth_values = array(
			"oauth_nonce|" . $nonce,
			"oauth_signature_method|" . "HMAC-SHA1",
			"oauth_timestamp|" . $timestamp,
			"oauth_consumer_key|" . $this->keys['consumer_key'],
			"oauth_version|" . "1.0"
		);

		if($this->tokens ) {
			$a_pair = array_shift($this->oauth_values);
			array_push($this->oauth_values, 'oauth_token' . '|' . $this->tokens['oauth_token']);
			array_push($this->oauth_values, $a_pair);
		}
	}

	/**
	 * Set a new value for the auth header
	 *
	 * @param string $key.  name of the property
	 * @param string $value. value for the property
	 * @access public
	 */
	public function setValues($key, $value)
	{
		//set the pair name|value
		$a_pair= array_shift($this->oauth_values);
		array_push($this->oauth_values, $key.'|'.$value);
		array_push($this->oauth_values, $a_pair);
	}

	/**
	 * Set a new POST/GET param value on the auth header
	 *
	 * @param string $key.  name of the param
	 * @param string $value. value for the param
	 * @access public
	 */
	public function setParams($key, $value)
	{
		array_push($this->oauth_params, rawurlencode($key).'|'.rawurlencode($value));
	}

	/**
	 * Calculate oAuth signature value
	 *
	 * @param string $method.  method used on the oAuth call
	 * @param string $url. URL for the oAuth call
	 * @access public
	 */
	public function calcSignature($method, $url)
	{
		// read RFC 5849 (oAuth 1.0)
		// http://tools.ietf.org/html/rfc5849#section-3.4.1

		//url without get params
		$url_aux = explode('?',$url);
		$urlWithoutParams = $url_aux[0];

		//oauth token secret (if user is loged-in)
		$ots = '';
		if($this->tokens) {
			$ots = $this->tokens['oauth_token_secret'];
		}

		//first base string setings method & base string URI
		$base_string = array ($method, urlencode($urlWithoutParams)); 

		//Request Parameters
		$params = array_merge($this->oauth_params, $this->oauth_values); 
		sort($params);
		for($i=0; $i< count($params); $i++){
			$params[$i] = str_replace("|","=",$params[$i]);
		}
		$params = implode("&",$params);
		$params = urlencode($params);

		//add request parameters to base string
		array_push($base_string, $params);
		$base_string = implode("&",$base_string);

		//use HMAC-SHA1 to encript it 
		$signature = $this->hmac_sha1($this->keys['shared_secret']."&".$ots, $base_string);
		$signature = base64_encode($signature);

		return $signature;
	}

	/**
	 * Return the Authorization header value
	 *
	 * @access public
	 */
	public function makeHeader(){

		//get values
		$values = $this->oauth_values;

		//make it as name="value"
		for($i =0; $i< count($values); $i++)
		{
			$a_pair = explode("|",$values[$i]);
			$a_pair[1]= '"' . rawurlencode($a_pair[1]) .  '"';
			$values[$i] = implode('=',$a_pair);
		}

		//add info
		$values[0]='OAuth ' . $values[0];

		//make header
		$heather = implode(', ',$values);
		return $heather;
	}

	/**
	 * format  and return POST parameters to can use they on the oAuth call
	 *
	 * @access public
	 */
	public function getPosTBody(){
		$result = array();

		$params = $this->oauth_params;
		for($i=0; $i< count($params); $i++) 
		{
			$a_pair = explode("|",$params[$i]);
			$result[$a_pair[0]] = $a_pair[1];
			$params[$i] = implode('=',$a_pair);
		}
		ksort($result);
		$params = implode('&',$params);
		return $params;
	}

	/**
	 * create a random numbre for oauth nonce value
	 *
	 * @param integer $min. 
	 * @param integer $max.
	 * @access private
	 */
	private function randomFloat($min = 0, $max = 1)
	{
		return $min + mt_rand() / mt_getrandmax() * ($max - $min);
	}

	/**
	 * perform a hmac_sha1 encode the the given values
	 *
	 * @param string $key. 
	 * @param string $data.
	 * @access private
	 */
	private function hmac_sha1($key, $data)
	{
		return pack('H*', sha1(
		(str_pad($key, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
		pack('H*', sha1((str_pad($key, 64, chr(0x00)) ^
		(str_repeat(chr(0x36), 64))) . $data))));
	}
}
?>
