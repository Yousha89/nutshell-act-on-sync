<?php
/**
 * Created by PhpStorm.
 * User: ahsuoy
 * Date: 4/10/2016
 * Time: 3:45 PM
 */

$slim_settings = function ($host) {
    $app_settings_list = [
        'nutshell-act-on-sync.tafhimui.com' => [
            'settings' => [
                'displayErrorDetails' => false,

                // Monolog settings
                'logger' => [
                    'name' => 'slim-app',
                    'path' => __DIR__ . '/logs/app.log',
                ],
            ]
        ],
        '127.0.0.1' => [
            'settings' => [
                'displayErrorDetails' => true,

                // Monolog settings
                'logger' => [
                    'name' => 'slim-app',
                    'path' => __DIR__ . '/logs/app.log',
                ],
            ],
        ],
        'localhost' => [
            'settings' => [
                'displayErrorDetails' => true,

                // Monolog settings
                'logger' => [
                    'name' => 'slim-app',
                    'path' => __DIR__ . '/logs/app.log',
                ],
            ],
        ]
    ];

    if (isset($app_settings_list[$host])) return $app_settings_list[$host];
    else return null;
};

$nutshell_api = function() {
    $nutApi = "3bd97fd464d4b89a3f9639c381fcfa7198af65be";
    return $nutApi;
};

$nutshell_user = function() {
    $nutUser = "youshafarokey@theportlandcompany.com";
    return $nutUser;
};