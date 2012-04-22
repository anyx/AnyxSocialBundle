<?php

namespace Anyx\SocialBundle\Authentication;

use Anyx\SocialBundle\Provider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * 
 */
class Manager {
	
	/**
	 * 
	 */
	protected $providerFactory;

	/**
	 *
	 * @param Provider\Factory $factory 
	 */
	public function __construct( Provider\Factory $factory ) {
		$this->providerFactory = $factory;
	}
	
	/**
	 *
	 * @return Anyx\SocialBundle\Provider\Factory 
	 */
	public function getProviderFactory() {
		return $this->providerFactory;
	}

	/**
	 *
	 * @param type $service
	 * @param Request $request 
	 * @return Anyx\SocialBundle\Authentication\AccessToken
	 */
	public function getAccessToken( $service, Request $request ) {

		$provider = $this->getProviderFactory()->getProvider( $service );

		return $provider->getAccessToken( $request );
	}
}
