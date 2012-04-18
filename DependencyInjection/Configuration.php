<?php

namespace Anyx\SocialBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('anyx_social');

		$rootNode
			->children()
				->arrayNode('services')
					->useAttributeAsKey('services')
					->prototype('array')
						->children()
							->scalarNode('provider')
							->end()	
							->scalarNode('client_id')
							->end()	
							->scalarNode('secret')
							->end()	
							->scalarNode('scope')
							->end()	
							->arrayNode('fields_map')
									->beforeNormalization()
										->ifTrue(function($fieldsMap) {
											return count( array_diff( array_keys( $fieldsMap ), array( 'accountId', 'userName' ))) > 0;
										})
										->then(function($fieldsMap) {
											foreach( $fieldsMap as $accountField => $userFieldName ) {
												if( !in_array( $accountField, array( 'accountId', 'userName' ) ) ) {
													//unset( $fieldsMap[$accountField] );
												}
											}
											return $fieldsMap;
										})
										->end()
								->children()
									->scalarNode('accountId')
									->end()	
									->scalarNode('userName')
									->end()
							->end()
				->end();

        return $treeBuilder;
    }
}
