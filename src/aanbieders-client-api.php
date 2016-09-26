<?php
/*
 * Aanbieders API Class
 * This class is a wrapper for making a curl based request to the api of aanbieders.be
 * Documentation for the API, see: https://apihelp.econtract.be/
 *
 * Contact for support:
 * Aanbieders API Support <api_support@aanbieders.be>
 */

if ( !function_exists( 'curl_init' ) )
{
	throw new Exception( 'Aanbieders.be Client Library requires the CURL PHP extension.' );
}
if ( !function_exists( 'json_decode' ) )
{
	throw new Exception( 'Aanbieders.be Client Library requires the JSON PHP extension.' );
}

class Aanbieders {

	private $key, $secret;
	private $host, $abcid;

	/*
	 * valid options: json, object, array
	 */
	public $outputtype = 'json';

	/**
	 * Constructor : Initializes the instance
	 *
	 * @param array $config	API configuration
	 */
	public function __construct( $config ) {

		// check $key, $secret and $hashmethod
		if ( !$config[ 'key' ] )
		{
			throw new Exception( 'Invalid key' );
		}


		if ( !$config[ 'secret' ] )
		{
			throw new Exception( 'Invalid secret' );
		}

		$this->key = $config[ 'key' ];
		$this->secret = $config[ 'secret' ];

		if ( $config[ 'staging' ] )
		{
			$this->host = 'http://eco-api.aanbieders.staging-001.econtract.prvw.eu';
		}
		else
		{
			$this->host = 'https://api.econtract.be';
		}

		//check for/create a unique id for tracking purposes
		$this->abcid = '';
		if ( !headers_sent() )
		{
			if ( !isset( $_COOKIE[ 'abcid' ] ) )
			{
				$this->abcid = uniqid();
			}
			else
			{
				$this->abcid = $_COOKIE[ 'abcid' ];
			}

			//store cookie for 200 days
			setcookie( 'abcid', $this->abcid, time() + 17280000, '/' );			
		}

	}

	/**
	 * gets default usages for elec and/or gas
	 *
	 * @param	array $params	the required params to make a comparing
	 * @return array Array with usage results
	 */
	public function usages( $params ) {

		$url = $this->host . "/usages.json";
		return $this->doCall( $url, $params, 'GET' );
	}

	/**
	 * Compares products based on parameters
	 *
	 * @param	array $params	the required params to make a comparing
	 * @return array Array with comparing results
	 */
	public function compare( $params ) {

		$url = $this->host . "/comparison.json";

		return $this->doCall( $url, $params, 'GET' );
	}
 
	/**
	 * Compares products based on parameters
	 *
	 * @param	array $params	the required params to make a comparing
	 * @param array  $comparisonId Id of the comparison to be read
	 * @return array Array with comparing results
	 */
	public function readComparison( $params, $comparisonId ) {
		$url = $this->host . "/comparison/view/{$comparisonId}.json";
		return $this->doCall( $url, $params, 'GET' );
	}
 
	/**
	 *  Get a list of 1 or more products and related information
	 *
	 * @param array  $params Array of parameters
	 * @param array  $productid Array of 1 or more product id's
	 *
	 */
	public function getProducts( $params, $productid = array( ) ) {

		$url = $this->host . "/products.json";
		// analyze product id's and build the url and query parameters
		if ( $productid && is_array( $productid ) && ($number_of_product_ids = count( $productid )) > 0 )
		{
			if ( $number_of_product_ids == 1  && is_numeric( $productid[ 0 ] ))
			{
				$url = $this->host . "/products/" . $productid[ 0 ] . ".json";
			}
			else
			{
				$params[ 'productid' ] = implode( ' ', $productid );
			}
		}
		else if ( $productid && is_numeric( $productid ) )
		{
			$url = $this->host . "/products/" . $productid . ".json";
		}

		return $this->doCall( $url, $params, 'GET' );
	}

	/**
	 * Placing order on Aanbieders.be system
	 * @param array $params array with data for order
	 * @return object:
	 */
	public function setOrder( $params ) {
		$url = $this->host . "/orders.json";

		$options = $params[ 'opt' ];
		if ( count( $params[ 'opt' ] ) > 1 )
		{
			foreach ( $options as $option )
			{
				$params[ 'opt[' . $option . ']' ] = $option;
			}
		}
		else
		{
			$params[ 'opt[]' ] = $params[ 'opt' ][ 0 ];
		}
		unset( $params[ 'opt' ] );

		return $this->doCall( $url, $params, 'GET' );
	}

	/**
	 * Get list of suppliers based on given parameters
	 * @param array $params array with parameters for suppliers
	 * @return object:
	 */
	public function getSuppliers( $params ) {
		
		$url = $this->host . '/suppliers.json';
		
		return $this->doCall( $url, $params, 'GET' );
		
	}
	
	/**
	 * Get list of product options based on given option IDs
	 * @param array $params array with IDs of options
	 * @return object:
	 */
	public function getOptions( $params ) {
		
		$url = $this->host . '/options.json';
		
		return $this->doCall( $url, $params, 'GET' );
		
	}

	
	/**
	 * Get list of affiliates based on given affiliate IDs
	 * @param array $params array with IDs of affiliates
	 * @return object:
	 */
	public function getAffiliates( $params ) {
		
		$url = $this->host . '/affiliates.json';
		
		return $this->doCall( $url, $params, 'GET' );
		
	}

	/**
	 * Get list of product promotions based on given promotion IDs
	 * @param array $params array with IDs of promotions
	 * @return object:
	 */
	public function getPromotions( $params ) {
		
		$url = $this->host . '/promotions.json';
		
		return $this->doCall( $url, $params, 'GET' );
		
	}

	/**
	 * Get list of the latest reviews
	 * @param array $params 
	 * @return object:
	 */
	public function getReviews( $params ) {
		
		$url = $this->host . '/reviews.json';
		
		return $this->doCall( $url, $params, 'GET' );
		
	}

	/**
	 * Generate a contract for your order
	 * @param array $params array with all parameters you receive from the order API
	 * @return object
	 */
	public function getContract( $params){
		
		if(!is_array($params)){
			//transform object into array recursively with json_ functions
			$params = json_decode(json_encode($params), true);
		}
		
		
		$url = $this->host.'/orders/generate.json';
		
		return $this->doCall( $url, $params, 'POST');
		
	}

	/**
	 * Magic get function to get the properties
	 * @throws Exception ifthe property you try to get is not defined
	 */
	public function _get( $property ) {

		switch ( $property )
		{
			case 'key' :
				return $this->key;
				break;
			case 'secret' :
				return $this->secret;
				break;
			case 'apikey' :
				return $this->apikey;
				break;
			default :
				throw new Exception( 'Invalid property' );
				break;
		}
	}

	/**
	 * Magic set function to set the properties
	 * @throws Exception if the property you try to set is not defined.
	 */
	public function _set( $property, $value ) {

		switch ( $property )
		{
			case 'key' :
				$this->key = $value;
				break;
			case 'secret' :
				$this->secret = $value;
				break;
			case 'apikey' :
				$this->apikey = $value;
				break;
			default :
				throw new Exception( 'Invalid property' );
				break;
		}
	}

	/**
	 * Do a call to given url with parameters
	 *
	 * @param string $url	Url of the resource
	 * @param array $parameters Required and optional parameters for the resource
	 * @param string $method The HTTP-method, default is POST
	 * @return array with request response
	 */
	private function doCall( $url, $parameters = array( ), $method = 'POST' ) {

		$parameters[ 'key' ] = $this->key;
		$parameters[ 'time' ] = time();
		// create a nonce
		
		$nonce = md5( uniqid());
		$parameters[ 'nonce' ] = $nonce;

		// create the api key
		$parameters[ 'ip' ] = $this->getIp();
		$parameters[ 'apikey' ] = hash_hmac( 'sha1', $this->key, $this->secret . $parameters[ 'time' ] . $parameters[ 'nonce' ] );

		// add the unique id
		$parameters[ 'abcid' ] = $this->abcid;

		// curl handler
		$curl_handle = curl_init();

		// set curl options
		// return the result so we can catch it as a variable
		curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, true );
		
		if ( $method == 'POST' )
		{
			// do a POST call
			// generate parameters
			$post_params = '';
			foreach ( $parameters as $key => $value )
			{
				if(is_array($value)){
					foreach($value as $k => $k_val){
						$post_params .= $key . '['.$k.']=' . $k_val . '&';
					}
				}
				else{
					$post_params .= $key . '=' . $value . '&';
				}
			}
			
			rtrim( $post_params, '&' );
			curl_setopt( $curl_handle, CURLOPT_POST, count( $parameters ) );
			curl_setopt( $curl_handle, CURLOPT_POSTFIELDS, $post_params );
			curl_setopt( $curl_handle, CURLOPT_URL, $url );
		}
		else if ( $method == 'GET' )
		{
			// do a GET call
			curl_setopt( $curl_handle, CURLOPT_HTTPGET, true );

			// do the request
			$query = http_build_query( $parameters, '', '&' );
			curl_setopt( $curl_handle, CURLOPT_URL, $url . '?' . $query );
		}

		// do request
		$response = curl_exec( $curl_handle );
		// close
		curl_close( $curl_handle );
		switch ( $this->outputtype )
		{
			default:
			case 'json':
				return $response;
				break;
			case 'object':
				return json_decode( $response );
				break;
			case 'array':
				return $this->object_to_array( json_decode( $response ) );
				break;
		}
		return json_decode( $response );
	}

	public function setOutputType( $type ) {
		if ( !in_array( $type, array( 'json', 'object', 'array' ) ) )
		{
			throw new Exception( 'Invalid outputtype' );
		}
		else
		{
			$this->outputtype = $type;
		}
	}

	/**
	 * converting an object to an array
	 * @param object $obj
	 * @return array
	 * 
	 */
	function object_to_array( $obj ) {


		$arr = array( );


		$arrObj = is_object( $obj ) ? get_object_vars( $obj ) : $obj;
		foreach ( $arrObj as $key => $val )
		{
			$val = (is_array( $val ) || is_object( $val )) ? $this->object_to_array( $val ) : $val;
			$arr[ $key ] = $val;
		}
		return $arr;
	}

	/**
	 * Getting real ip of requester
	 * @return string $ip
	 */
	private function getIp() {
		if ( !empty( $_SERVER[ 'HTTP_CLIENT_IP' ] ) )
		{
			$ip = $_SERVER[ 'HTTP_CLIENT_IP' ];
		}
		elseif ( !empty( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) )
		{
			$ip = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
		}
		elseif(isset($_SERVER[ 'REMOTE_ADDR' ])) 
		{
			$ip = $_SERVER[ 'REMOTE_ADDR' ];
		} 
		else 
		{
			$ip = '';
		}

		return $ip;
	}

	function getDnb( $zip, $language ) {

		$params[ 'zip' ] = $zip;
		$params[ 'lang' ] = $language;

		$url = $this->host . '/dnb.json';

		return $this->doCall( $url, $params, 'GET' );
	}

	function getDualfuelpack( $electricity_id, $gas_id ) {

		$params[ 'electricity_id' ] = $electricity_id;
		$params[ 'gas_id' ] = $gas_id;

		$url = $this->host . '/dualfuelpack.json';

		return $this->doCall( $url, $params, 'GET' );
	}


	function multi_implode( $glue, $array ) {
		$ret = '';

		foreach ( $array as $item )
		{
			if ( is_array( $item ) )
			{
				$ret .= $this->multi_implode( $glue, $item ) . $glue;
			}
			else
			{
				$ret .= $item . $glue;
			}
		}

		$ret = substr( $ret, 0, 0 - strlen( $glue ) );

		return $ret;
	}

	/**
	 * 
	 * @return boolean
	 */
	function validate_ean( $value ) {
		if ( strlen( $value ) != 18 )
			return false;

		$check = false;
		for ( $i = 1; $i <= 17; $i++ )
		{
			$check = ($check + (1 + 2 * ($i % 2)) * intval( substr( $value, $i - 1, 1 ) )) % 10;
		}

		$check = (10 - $check) % 10;
		return (intval( $check ) == intval( substr( $value, 17, 1 ) ));
	}

}