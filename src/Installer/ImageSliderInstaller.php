<?php

declare(strict_types=1);

namespace Oksydan\IsImageslider\Installer;

use Context;
use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;

class ImageSliderInstaller
{
    /**
     * @var DatabaseYamlParser
     */
    private $databaseYaml;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Context
     */
    private $context;

    /**
     * @param Connection $connection
     * @param DatabaseYamlParser $databaseYaml
     * @param Context $context
     */
    public function __construct(Connection $connection, DatabaseYamlParser $databaseYaml, $context)
    {
        $this->connection = $connection;
        $this->databaseYaml = $databaseYaml;
        $this->context = $context;
    }

    private function getContainer()
    {
        if (null === $this->context->container) {
            $containerFinder = new ContainerFinder($this->context);
            $container = $containerFinder->getContainer();
            $this->context->container = $container;
        }

        return $this->context->container;
    }

    public function createTables(): bool
    {
        $databaseData = $this->databaseYaml->getParsedFileData();
        $container = $this->getContainer();
        $createTableAction = $container->get('oksydan.is_imageslider.installer.action_databse_create_table');
        $createTableAction
            ->setData($databaseData)
            ->buildQuery();

        $result = $createTableAction->execute();

        return $result;
    }

    /**
     * @return bool
     */
    public function dropTables(): bool
    {
        $databaseData = $this->databaseYaml->getParsedFileData();
        $container = $this->getContainer();
        $dropTableAction = $container->get('oksydan.is_imageslider.installer.action_databse_drop_table');
        $dropTableAction
            ->setData($databaseData)
            ->buildQuery();

        $result = $dropTableAction->execute();

        return $result;
    }
}
