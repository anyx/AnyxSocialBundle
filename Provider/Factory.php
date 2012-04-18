<?php

namespace Anyx\SocialBundle\Provider;

use Symfony\Component\DependencyInjection\Container;
use Buzz\Browser;

/**
 * 
 */
class Factory {
	
	/**
	 *
	 */
	protected $servicesConfig; 	
	
	/**
	 *
	 * @var 
	 */
	protected $browser;


	/**
	 *
	 * @param array $servicesConfig 
	 */
	public function __construct( Browser $browser, array $servicesConfig ) {
		$this->servicesConfig = $servicesConfig;
		$this->browser = $browser;
		foreach ($servicesConfig as $name => $config) {
			$this->setProviderConfig($name, $config);
		}
	}

	/**
	 * 
	 */
	public function getServices() {
		return array_keys( $this->servicesConfig );
	}

	/**
	 *
	 * @param string $name
	 * @param array $options 
	 */
	public function setProviderConfig( $name, array $options ) {
		if ( !array_key_exists('class', $options) ) {
			throw new \InvalidArgumentException('Provider class is not present in provider options');
		}
		
		$this->servicesConfig[$name] = $options;
	}
			
	/**
	 *
	 * @param string $service
	 * @throws InvalidArgumentException
	 * 
	 * @return Anyx\SocialBundle\Provider\OAuthProvider
	 */
	public function getProvider( $name ) {
		if ( !array_key_exists($name, $this->servicesConfig ) ) {
			throw new \InvalidArgumentException( "Service '$name' is not registered" );
		}
	
		$providerClass = $this->getProviderClass($name);
		
		return new $providerClass( $this->browser, $this->getProviderOptions($name) );
	}

	/**
	 * 
	 */
	public function setProvidersOption( $option, $value ) {
		foreach( $this->servicesConfig as $service => &$options ) {
			$options[$option] = $value;
		}
	}

	/**
	 *
	 * @param string $name
	 * @throws \InvalidArgumentException 
	 */
	protected function getProviderOptions( $name ) {
		if ( !array_key_exists($name, $this->servicesConfig) ) {
			throw new \InvalidArgumentException("Provider '$name' is not registered ");
		}
		
		$options = $this->servicesConfig[$name];
		unset($options['class']);
		
		return $options;
	}
	
	/**
	 *
	 * @param string $name
	 * @return string
	 * @throws \InvalidArgumentException 
	 */
	protected function getProviderClass( $name ) {
		if ( !array_key_exists($name, $this->servicesConfig) ) {
			throw new \InvalidArgumentException("Provider '$name' is not registered ");
		}

		return $this->servicesConfig[$name]['class'];
	}
}