<?php

namespace IKTO\PgiMigrationDirectoriesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ikto_pgi_migration_directories');
        $rootNode
            ->children()
            ->append($this->getMigrationsNode())
        ;

        return $treeBuilder;
    }

    protected function getMigrationsNode()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('migrations');

        $migrationsNode = $rootNode
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array')
        ;

        $migrationsNode
            ->children()
            ->scalarNode('dbname')->isRequired()->end()
            ->scalarNode('host')->defaultValue('localhost')->end()
            ->scalarNode('port')->defaultValue(5432)->end()
            ->scalarNode('user')->defaultNull()->end()
            ->scalarNode('password')->defaultNull()->end()
            ->scalarNode('base')->isRequired()->end()
            ->scalarNode('schema')->isRequired()->end()
            ->scalarNode('storage_schema')->isRequired()->end()
            ->scalarNode('desired_version')->isRequired()->end()
            ->end()
        ;

        return $rootNode;
    }
}
