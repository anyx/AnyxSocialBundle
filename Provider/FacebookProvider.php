<?php

namespace Anyx\SocialBundle\Provider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Anyx\SocialBundle\Authentication;

class FacebookProvider extends OAuthProvider {

    /**
     * {@inheritDoc}
     */
    protected $options = array(
        'authorization_url' => 'https://www.facebook.com/dialog/oauth',
        'access_token_url'  => 'https://graph.facebook.com/oauth/access_token',
        'user_data_url'         => 'https://graph.facebook.com/me',
	);

    /**
     * {@inheritDoc}
     */
    public function getAccessToken(Request $request )
    {
		
		if ( $request->get('code') == null ) {
			$response = new RedirectResponse( $this->getAuthorizationUrl() );
			return $response->send();
		}
		
		$parameters = array(
            'code'          => $request->get('code'),
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->getOption('client_id'),
            'client_secret' => $this->getOption('secret'),
            'redirect_uri'  => $this->getRedirectUri(),
        );

		$url = $this->getOption('access_token_url');

		$response = $this->getBrowser()->call( $url . '?' . http_build_query($parameters), 'GET' );

		$content = array();
		parse_str( $response->getContent(), $content );
		
		if ( !is_array( $content ) || !array_key_exists('access_token', $content ) ) {
			throw new Authentication\Exception( 'Access token not present in response' );
		}
		
		return new Authentication\AccessToken( $content['access_token'] );
    }
	
    /**
     * {@inheritDoc}
     */
    public function getUserData( Authentication\AccessToken $accessToken )
    {
		if ($this->getOption('user_data_url') === null) {
            return $accessToken;
        }

        $url = $this->getOption('user_data_url').'?'.http_build_query(array(
            'access_token' => $accessToken->getToken()
        ));

        $content = $this->getBrowser()->call($url, 'GET')->getContent();

		return json_decode( $content, true );
    }
	
    /**
     * {@inheritDoc}
     */
    private function getAuthorizationUrl()
    {
        $parameters = array(
            'response_type' => 'code',
            'client_id'     => $this->getOption('client_id'),
            'scope'         => $this->getOption('scope'),
            'redirect_uri'  => $this->getRedirectUri()
        );

        return $this->getOption('authorization_url').'?'.http_build_query($parameters);
    }
}