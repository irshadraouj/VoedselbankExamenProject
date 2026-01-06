<?php

use \Dunique\AntwanVanBoheemen\Service\Request as RequestService;

return [
    'name'              => 'Dunique',
    'description'       => 'Dunique module',
    'version'           => '1.0.0',
    'author'            => 'Antwan van Boheemen',
    'author_url'        => 'https://dunique.nl',
    'namespace'         => 'Dunique\AntwanVanBoheemen',
    'settings_exist'    => false,
    'services' => [
        'Helper' => fn ($addon) => new \Dunique\AntwanVanBoheemen\Service\HelperService(),
        'Request' => fn ($addon) => new \Dunique\AntwanVanBoheemen\Service\RequestService(),
        // 'Middleware' => fn ($addon) => new \Dunique\AntwanVanBoheemen\Service\Middleware(),
        'Response' => fn ($addon) => new \Dunique\AntwanVanBoheemen\Service\ResponseService(),
        'Form' => fn ($addon) => new \Dunique\AntwanVanBoheemen\Service\FormService(),
        'File' => fn ($addon) => new \Dunique\AntwanVanBoheemen\Service\FileService(),
    ],
    'fieldtypes'        => [
        'dunique_cp_heading' => [
            'name' => 'Dunique - CP Heading',
            'compatibility' => 'text',
        ],
    ], 
];
