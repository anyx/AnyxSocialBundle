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
        'user_data_url'		=> 'https://api.twitter.com/1.1/users/show.json',
	);

	/**
     * {@inheritDoc}
     */
    public function getUserData( Authentication\AccessToken $accessToken)
    {
        $url = $this->getOption('user_data_url');

        $response = $this->authRequest(
                            $accessToken,
                            $url,
                            'GET',
                            array('screen_name' => $accessToken->getParam('screen_name'))
        );

        if ( $response->getStatusCode() !== 200 ) {
			throw new Authentication\Exception( 'Error retrieving user data' );	
		}
		
		return json_decode( $response->getContent(), true );
    }
	
	/**
     * {@inheritDoc}
     */
    public function getAccessToken( Request $request )
    {
		$oauthToken = $request->get('oauth_token');

		if ( $request->get( 'denied', false ) != false ) {
			throw new Authentication\UserDeniedException( 'User rejected authorization' );
		}

        if ( !empty( $oauthToken ) ) {

            $response = $this->request($this->getOption('access_token_url'), 'GET', array(
				'oauth_verifier'	=> $request->get('oauth_verifier'),
				'oauth_token'		=> $request->get('oauth_token')
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
     * @param string $url
     * @param type $params
     * @param type $headers
     */
    private function call( $url, $method = 'GET', array $params = array(), array $headers = array() )
    {
        if (!empty( $params ) ) {
            $url .= '?' . http_build_query($params);
        }

        return $this->getBrowser()->call($url, $method, $headers);
    }

    /**
	 * 
	 */
	private function getRequestToken( Request $request )
    {
        $response = $this->request(
                $this->getOption('request_token_url'),
                'POST',
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
	protected function authRequest( Authentication\AccessToken $accessToken, $url, $method = 'GET', $requestParams = array() )
    {
        return $this->request(
                $url,
                $method,
                $requestParams,
                array(
                    'oauth_token' => $accessToken->getParam('oauth_token')
                ),
                $accessToken->getToken()
        );
	}

    /**
     *
     * @param string $url
     * @param string $method
     * @param array $requestParams
     * @param array $headers
     */
    private function request( $url, $method = 'GET', array $requestParams = array(), array $headers = array(), $accessToken = null )
    {
        $params = array_merge(array(
			'oauth_nonce'			=> time(),
			'oauth_signature_method'=> 'HMAC-SHA1',
			'oauth_timestamp'		=> time(),
			'oauth_consumer_key'	=> $this->getOption('client_id'),
            'oauth_version'			=> '1.0',
		), $headers);

        $params['oauth_signature'] = $this->getRequestSignature($url, $method, array_merge($params, $requestParams), $accessToken );

        if (!empty( $requestParams ) ) {
            $url .= '?'. http_build_query($requestParams);
        }

        return $this->getBrowser()->call( $url, $method, $this->generateAuthorizationHeaders( $params ) );
    }

    /**
     *
     * @param array $params
     * @return array
     */
    private function generateAuthorizationHeaders( array $params )
    {
        return array(
            'Authorization' => 'OAuth ' .  $this->joinParams($params)
        );
    }

    /**
     *
     * @param string $url
     * @param string $method
     * @param array $params
     * @param string $accessToken
     * @return string
     */
	private function getRequestSignature( $url, $method = 'GET', array $params = array(), $accessToken = '')
    {
		uksort($params, 'strcmp');

        $concatenatedParams = array();
		foreach($params as $k  => $v ) {
			$concatenatedParams[] = $k . "=" . urlencode($v); 
		}

        $concatenatedParams = implode( '&', $concatenatedParams );

		$secret = urlencode( $this->getOption('secret') ) . "&" . urlencode( $accessToken );

        $signatureBaseString = $method . "&".urlencode( $url )."&". urlencode( $concatenatedParams );

        return base64_encode( hash_hmac('SHA1', $signatureBaseString, $secret, true ) );
	}

    /**
     *
     * @param array $params
     */
    private function joinParams(array $params)
    {
        uksort($params, 'strcmp');

        $concatenatedParams = array();
		foreach($params as $k  => $v ) {
			$concatenatedParams[] = $k . '="' . urlencode($v) .'"';
		}

        $concatenatedParams = implode( ', ', $concatenatedParams );

        return $concatenatedParams;
    }
}