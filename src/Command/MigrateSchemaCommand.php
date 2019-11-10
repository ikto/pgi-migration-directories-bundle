<?php

namespace IKTO\PgiMigrationDirectoriesBundle\Command;

use IKTO\PgiMigrationDirectories\Database\ManagedDatabaseInterface;
use IKTO\PgiMigrationDirectories\Discovery\DiscoveryInterface;
use IKTO\PgiMigrationDirectories\MigrationPathBuilder\MigrationPathBuilder;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class MigrateSchemaCommand extends Command implements ServiceSubscriberInterface
{
    protected static $defaultName = 'migrate:schema';

    public function __construct(string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * @var ContainerInterface
     */
    protected $locator;

    /**
     * @required
     * @param ContainerInterface $locator
     */
    public function setLocator(ContainerInterface $locator): void
    {
        $this->locator = $locator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Perform database migration');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ManagedDatabaseInterface $db */
        $db = $this->locator->get('managed_db');

        $startingVersion = $db->getCurrentVersion();

        $output->writeln(sprintf('Current version: %d', $startingVersion));

        /** @var DiscoveryInterface $discovery */
        $discovery = $this->locator->get('discovery');
        $builder = new MigrationPathBuilder($discovery);
        $path = $builder->getMigrationPath($startingVersion, $db->getDesiredVersion());

        foreach ($path as $migration) {
            $db->openTransaction();
            $db->applyMigration($migration);
            $db->commitTransaction();
            $output->writeln(
                sprintf('Migrated from %d to %d', $migration->getStartingVersion(), $migration->getTargetVersion())
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'managed_db',
        ];
    }
}
