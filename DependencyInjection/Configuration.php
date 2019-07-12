<?php

namespace Acme\AvroBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{


    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('acme_avro');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('avro_registry_link')->end()
                ->scalarNode('aaa')->end()
            ->end()
        ;

        return $treeBuilder;
    }

























}
