<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 * @author Alexander <iam.asm89@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sonata_core');

        // Keep compatibility with symfony/config < 4.2
        if (!method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->root('sonata_core');
        } else {
            $rootNode = $treeBuilder->getRootNode();
        }

        $this->addFlashMessageSection($rootNode);
        $this->addSerializerFormats($rootNode);

        $rootNode
            ->children()
                ->arrayNode('form')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('mapping')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')
                                    ->defaultValue(true)
                                ->end()
                                ->arrayNode('type')
                                    ->useAttributeAsKey('id')
                                    ->defaultValue([])
                                    ->prototype('scalar')->end()
                                ->end()

                                ->arrayNode('extension')
                                    ->useAttributeAsKey('id')
                                    ->defaultValue([])
                                    ->prototype('array')
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * Returns configuration for flash messages.
     */
    private function addFlashMessageSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->scalarNode('form_type')
                    ->defaultValue('standard')
                    ->validate()
                    ->ifNotInArray($validFormTypes = ['standard', 'horizontal'])
                        ->thenInvalid(sprintf(
                            'The form_type option value must be one of %s',
                            $validFormTypesString = implode(', ', $validFormTypes)
                        ))
                    ->end()
                    ->info(sprintf('Must be one of %s', $validFormTypesString))
                ->end()
                ->arrayNode('flashmessage')
                    ->useAttributeAsKey('message')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('css_class')->end()
                            ->arrayNode('types')
                                ->useAttributeAsKey('type')
                                ->prototype('array')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * Returns configuration for serializer formats.
     */
    private function addSerializerFormats(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('serializer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('formats')
                            ->prototype('scalar')->end()
                            ->defaultValue(['json', 'xml', 'yml'])
                            ->info('Default serializer formats, will be used while getting subscribing methods.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
