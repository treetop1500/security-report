<?php

namespace Treetop1500\SecurityReportBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('treetop1500_security_report');

        $rootNode
            ->children()
                ->scalarNode('key')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('show_output')
                    ->defaultTrue()
                ->end()
                ->scalarNode('delivery_method')
                    ->defaultValue('email')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('recipients')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->prototype("scalar")
                    ->end()
                ->end()
                ->arrayNode('allowable_ips')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->prototype("scalar")
                    ->end()
                ->end()
            ->end()
        ;


        return $treeBuilder;
    }
}
