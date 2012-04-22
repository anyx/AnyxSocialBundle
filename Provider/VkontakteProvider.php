<?php

namespace Anyx\SocialBundle\Provider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Anyx\SocialBundle\Authentication;

/**
 * 
 */
class VkontakteProvider extends OAuthProvider {

    /**
     * {@inheritDoc}
     */
    protected $options = array(
        'authorization_url' => 'http://oauth.vk.com/authorize',
        'access_token_url'  => 'https://oauth.vk.com/access_token',
        'user_data_url'		=> 'https://api.vk.com/method/users.get',
	);	
	
    /**
	 * 
	 * @param Request $request
	 * @todo refactoring
	 * 
	 * @return Anyx\SocialBundle\Authentication\AccessToken;
     */
    public function getAccessToken( Request $request ) {

		if ( $request->get('error', false) != false ) {
			if ( $request->get('error', 'access_denied') ) {
				throw new Authentication\UserDeniedException( 'User rejected authorization' );
			}
			throw new Authentication\Exception( 'Autorization error' );
		}
		
		if ( $request->get('code') == null ) {
			$response = new RedirectResponse( $this->getAuthorizationUrl() );
			return $response->send();
		}

		$parameters = array(
            'code'          => $request->get('code'),
            'client_id'     => $this->getOption('client_id'),
            'client_secret' => $this->getOption('secret'),
            'redirect_uri'  => $this->getRedirectUri(),
        );

		$response = $this->getBrowser()->call(
				$this->getOption('access_token_url') . '?' . http_build_query($parameters),
				'GET'
		);

		$content = json_decode( $response->getContent(), true );
		
		if ( !is_array( $content ) || !array_key_exists('access_token', $content ) ) {
			throw new Authentication\Exception( 'Access token not present in response' );
		}

		$token = $content['access_token'];
		unset( $content['access_token'] );
		
		return new Authentication\AccessToken( $token, $content );
	}

	/**
	 * 
	 * @param Authentication\AccessToken $accessToken
	 * @return array
	 */
	public function getUserData( Authentication\AccessToken $accessToken ) {

		$params = array(
			'access_token'	=> $accessToken->getToken(),
			'uid'			=> $accessToken->getParam('user_id')
		);
		
		$userFields = $this->getOption('user_fields');
		if ( !empty( $userFields ) ) {
			$params['fields'] = $userFields;
		}
		
		$url = $this->getOption('user_data_url').'?'.http_build_query($params);

        $content = $this->getBrowser()->call($url, 'GET')->getContent();

		return json_decode( $content, true );
	}
}