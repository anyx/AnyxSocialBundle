<?php

namespace Anyx\SocialBundle\Authentication;

/**
 * 
 */
class AccessToken {

	/**
	 *
	 * @var string 
	 */
	private $token;
	
	/**
	 * @var array
	 */
	private $params;
	
	/**
	 *
	 * @param string $token
	 * @param array $params 
	 */
	public function __construct( $token, array $params = array() ) {
		
		if ( empty( $token ) ) {
			throw new \InvalidArgumentException( 'Token is missing' );
		}
		
		$this->token = $token;
		$this->params = $params;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getToken() {
		return $this->token;
	}

	/**
	 *
	 * @return array
	 */
	public function getParams() {
		return $this->params;
	}
	
	/**
	 *
	 * @param string $name 
	 */
	public function hasParam( $name ) {
		return array_key_exists($name, $this->params);
	}
	
	/**
	 *
	 * @param string $name 
	 */
	public function getParam( $name ) {
		
		if ( !$this->hasParam($name) ) {
			throw new \InvalidArgumentException( "Param '$name' not present in access token" );
		}
		
		return $this->params[$name];
	}
	
	/**
	 *
	 * @return type 
	 */
	public function __toString() {
		return $this->getToken();
	}
}