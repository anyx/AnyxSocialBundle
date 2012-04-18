<?php

namespace Anyx\SocialBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AnyxSocialExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
		$container->setParameter( 'anyx_social.services', $config['services']);
		
		foreach ( $config['services'] as $serviceId => &$serviceConfig ) {
			$providerClass = 'anyx_social.provider.' . $serviceId . '.class';
			$serviceConfig['class'] = $container->getParameter($providerClass);
		}
		
		//Providers
		$container->setParameter('anyx_social.providers.config',  $config['services']);
		$container->getDefinition('anyx_social.provider.factory')
				->addArgument('%anyx_social.providers.config%');
	}
}
