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
	 */
	public function getAccessToken( $service, Request $request ) {

		$provider = $this->getProviderFactory()->getProvider( $service );

		return $provider->getAccessToken( $request );
	}
}
