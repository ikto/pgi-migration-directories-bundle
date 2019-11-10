<?php

namespace IKTO\PgiMigrationDirectoriesBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class IktoPgiMigrationDirectoriesExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $migrations = $config['migrations'];

        foreach ($migrations as $migrationName => $migrationParams) {
            $definition = new ChildDefinition('pgi_migration_directories.discovery.abstract');
            $definition->setArguments([$migrationParams['base'], $migrationParams['schema']]);
            $definition->setPublic(true);
            $container->setDefinition('pgi_migration_directories.discovery.'.$migrationName, $definition);

            $definition = new ChildDefinition('pgi_migration_directories.dbh.abstract');
            $definition->setArguments([
                "host={$migrationParams['host']} port={$migrationParams['port']} dbname={$migrationParams['dbname']}",
                $migrationParams['user'],
                $migrationParams['password'],
            ]);

            $container->setDefinition('pgi_migration_directories.dbh.'.$migrationName, $definition);

            $definition = new ChildDefinition('pgi_migration_directories.managed_db.abstract');
            $definition->setArguments([
                new Reference('pgi_migration_directories.dbh.'.$migrationName),
                $migrationParams['schema'],
                $migrationParams['storage_schema'],
            ]);
            $definition->addMethodCall('setDesiredVersion', [$migrationParams['desired_version']]);

            $container->setDefinition('pgi_migration_directories.managed_db.'.$migrationName, $definition);

            $service_map = [
                'pgi_migration_directories.discovery.abstract' => 'pgi_migration_directories.discovery.'.$migrationName,
                'pgi_migration_directories.dbh.abstract' => 'pgi_migration_directories.dbh.'.$migrationName,
                'pgi_migration_directories.managed_db.abstract' => 'pgi_migration_directories.managed_db.'.$migrationName,
            ];

            $parent_definition = $container->getDefinition('pgi_migration_directories.command_service_locator.abstract');
            $definition = new ChildDefinition('pgi_migration_directories.command_service_locator.abstract');
            $new_argument = [];
            foreach ($parent_definition->getArgument(0) as $key => $argument) {
                if ($argument instanceof Reference && isset($service_map[(string) $argument])) {
                    $new_argument[$key] = new Reference($service_map[(string) $argument]);
                }
            }
            $definition->replaceArgument(0, $new_argument);
            $definition->addTag('container.service_locator');
            $container->setDefinition('pgi_migration_directories.command_service_locator.'.$migrationName, $definition);

            $definition = new ChildDefinition('pgi_migration_directories.command.migrate_schema.abstract');
            $definition->addMethodCall('setLocator', [new Reference('pgi_migration_directories.command_service_locator.'.$migrationName)]);
            $definition->addTag('console.command', ['command' => 'migrate:schema:' . $migrationName]);
            $container->setDefinition('pgi_migration_directories.command.migrate_schema.'.$migrationName, $definition);
        }
    }
}
