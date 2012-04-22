<?php

namespace Anyx\SocialBundle\Provider;

use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpFoundation\Request;
use Anyx\SocialBundle\Authentication;

use Buzz\Browser;


/**
 * OAuthProvider
 *
 */
abstract class OAuthProvider
{
    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var Buzz\Browser
     */
    protected $browser;

    /**
	 * 
	 * @param Request $request
	 * @throws Anyx\SocialBundle\Authentication\Exception
	 * 
	 * @return Anyx\SocialBundle\Authentication\AccessToken;
     */
    abstract public function getAccessToken( Request $request );

	/**
	 * 
	 * @param Authentication\AccessToken $accessToken
	 * @return array
	 */
	abstract public function getUserData( Authentication\AccessToken $accessToken );

	/**
     * @param Buzz\Client\ClientInterface $httpClient
     * @param array                       $options
     */
    public function __construct( Browser $browser, array $options )
    {
		$this->browser = $browser;
		
		$this->options = array_merge($this->options, $options);
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->getOption('redirect_uri');
    }

    /**
     * Retrieve an option by name
     *
     * @throws InvalidArgumentException When the option does not exist
     * @param string                    $name The option name
     * @return mixed                    The option value
     */
    public function getOption($name)
    {
		if (!array_key_exists($name, $this->options)) {
            throw new \InvalidArgumentException(sprintf('Unknown option "%s"', $name));
        }

        return $this->options[$name];
    }

	/**
	 * 
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 *
	 * @param string $name
	 * @param mixed $value 
	 */
	public function setOption( $name, $value ) {
		$this->options[$name] = $value;
	}

	/**
	 *
	 * @return Buzz\Browser
	 */
	public function getBrowser() {
		return $this->browser;
	}
	
    /**
     * {@inheritDoc}
     */
    protected function getAuthorizationUrl()
    {
        $parameters = array(
            'response_type' => 'code',
            'client_id'     => $this->getOption('client_id'),
            'scope'         => $this->getOption('scope'),
            'redirect_uri'  => $this->getRedirectUri()
        );

        return $this->getOption('authorization_url').'?'.http_build_query($parameters);
    }
	
	/**
	 * 
	 */
	protected function getSessionKey() {
		return 'Login' . get_class( $this );
	}
}