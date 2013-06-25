<?php
return array(
    'view_manager' => array(
        'template_path_stack' => array(
            'zenddevelopertools' => __DIR__ . '/../view',
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'DeveloperToolsController' => 'ZendDeveloperTools\Controller\DeveloperToolsController',
        )
    ),
);
