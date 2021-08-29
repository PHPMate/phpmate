<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('doctrine', [
        'dbal' => ['url' => '%env(resolve:DATABASE_URL)%'],
        'orm' => [
            'auto_generate_proxy_classes' => true,
            'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
            'auto_mapping' => true,
            'mappings' => [
                'PHPMate' => [
                    'is_bundle' => false,
                    'type' => 'xml',
                    'dir' => '%kernel.project_dir%/src/Infrastructure/Persistence/Doctrine/Mapping',
                    'prefix' => 'PHPMate\Infrastructure\Persistence\Doctrine\Mapping',
                ],
            ],
        ],
    ]);
};