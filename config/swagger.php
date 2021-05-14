<?php

return [
    'database'         => [
        /*
         * The database connection to use for all Swagger entries.
         */
        'connection' => null,
    ],

    /*
     * The delay in seconds before a route should be re-evaluated.
     * Defaults to half a day.
     */
    'evaluation-delay' => 43200,

    /*
     * The Open API info, see "Metadata" on the link below.
     *   https://swagger.io/docs/specification/basic-structure/
     */
    'info'             => [
        'title'       => 'Laravel to Swagger',
        'description' => null,
        'version'     => '0.0.1',
    ],

    /*
     * The Open API servers, see "Servers" on the link below.
     *   https://swagger.io/docs/specification/basic-structure/
     */
    'servers'          => [
        [
            'url'         => 'http://api.example.com/v1',
            'description' => null,
        ],
    ],

    'redoc' => [
        /*
         * Which version of Redoc to use.
         */
        'version' => 'v2.0.0-rc.53',
    ],
];
