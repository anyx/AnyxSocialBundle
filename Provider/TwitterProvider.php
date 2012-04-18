<?php

namespace Anyx\SocialBundle\Provider;

use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Anyx\SocialBundle\Authentication;

use Buzz\Browser;

/**
 * 
 */
class TwitterProvider extends OAuthProvider {

    /**
     * {@inheritDoc}
     */
    protected $options = array(
		'request_token_url'	=> 'https://api.twitter.com/oauth/request_token',
        'authorization_url' => 'https://api.twitter.com/oauth/authorize',
        'access_token_url'  => 'https://api.twitter.com/oauth/access_token',
        'user_data_url'		=> 'http://api.twitter.com/1/users/show.json',
	);

    /**
     * {@inheritDoc}
     */
    public function getUserData( Authentication\AccessToken $accessToken)
    {
		$response = $this->getBrowser()->get(
						$this->getOption('user_data_url') . '?screen_name=' . $accessToken->getParam('screen_name')
		);
		
		if ( $response->getStatusCode() !== 200 ) {
			throw new Authentication\Exception( 'Error retrieving user data' );	
		}
		
		return json_decode( $response->getContent(), true );
    }
	
	/**
     * {@inheritDoc}
     */
    public function getAccessToken( Request $request  )
    {
		$oauthToken = $request->get('oauth_token');
		
		if ( !empty( $oauthToken ) ) {

			$response = $this->request($this->getOption('access_token_url'), array(
				'oauth_verifier'		=> $request->get('oauth_verifier'),
				'oauth_token'			=> $request->get('oauth_token')
			));			

			$result = array();
			parse_str( $response->getContent(), $result );			

			if ( !array_key_exists( 'oauth_token_secret', $result ) ) {
				throw new Authentication\Exception( 'Access token not present in response' );
			}
			$token = $result['oauth_token_secret'];
			unset($result['oauth_token_secret'] );

			return new Authentication\AccessToken( $token, $result ); 
		}
			
		$requestToken = $this->getRequestToken( $request );
		
		$parameters = array_merge( array(
            'oauth_token'	=> $requestToken,
        ));

		$response = new RedirectResponse( $this->getOption('authorization_url') . '?' .  http_build_query($parameters) );

		$response->send();
	}
	
	/**
	 * 
	 */
	private function getRequestToken( Request $request ) {

		$response = $this->request(
						$this->getOption('request_token_url'),
						array(
							'oauth_callback' => $request->getUri()
						)
		);
		
		if ( $response->getStatusCode() != 200 ) {
			throw new Authentication\Exception( 'Can\'t get twitter request token' );
		}
		
		$result = array();
		parse_str( $response->getContent(), $result );

		if ( empty( $result ) || $result['oauth_callback_confirmed'] != 'true' ) {
			throw new Authentication\Exception( 'Can\'t get twitter request token' );
		}
	
		//store params?
		return $result['oauth_token'];
	}

	/**
	 *
	 * @param string $url
	 * @param array $params 
	 */
	private function request( $url, $additionalParams = array() ) {

		$params = array(
			'oauth_nonce'			=> time(),
			'oauth_signature_method'=> 'HMAC-SHA1',
			'oauth_timestamp'		=> time(),
			'oauth_consumer_key'	=> $this->getOption('client_id'),
            'oauth_version'			=> '1.0',
		);
		
		if ( !empty($additionalParams) ) {
			$params = array_merge($params, $additionalParams);
		}

		$params['oauth_signature'] = $this->getRequestSignature($url, $params );
		
		uksort($params, 'strcmp');
		
		return $this->getBrowser()->get( $url . '?' . http_build_query($params) );		
	}
	
	/**
	 *
	 * @param string $url
	 * @param array $params
	 * @param method $method
	 * @return string 
	 */
	private function getRequestSignature( $url, $params, $method = 'GET' ) {
		
		uksort($params, 'strcmp');
		
		$concatenatedParams = array();
		foreach($params as $k  => $v ) {
			$concatenatedParams[] = $k . "=" . urlencode($v); 
		}
		
		$concatenatedParams = implode( '&', $concatenatedParams );

		$secret = urlencode( $this->getOption('secret') ) . "&";
		
		$signatureBaseString = $method . "&".urlencode( $url )."&". urlencode( $concatenatedParams );

		return base64_encode( hash_hmac('SHA1', $signatureBaseString, $secret, true ) );			
	}
}