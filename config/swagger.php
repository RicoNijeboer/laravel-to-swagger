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
     * Defaults to half a day (or 12 hours).
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

        /*
         * When you group tags a default group will be created containing all tags that have not been grouped.
         * You can overwrite it's name here.
         *      https://github.com/RicoNijeboer/laravel-to-swagger#grouping-tags
         */
        'default-group' => null,

        /*
         * Groups of tags you want to be applied.
         *      https://github.com/RicoNijeboer/laravel-to-swagger#grouping-tags
         */
        'tag-groups' => [
            /*
            [
                'name' => 'User Management',
                'tags' => [ 'Users', 'Admin', 'API keys' ],
            ],
            */
        ],
    ],
];
