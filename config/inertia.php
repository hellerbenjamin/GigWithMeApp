<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Page Components
    |--------------------------------------------------------------------------
    |
    | Where Inertia looks for page components. This project capitalises the
    | directory ("js/Pages", matching the Vite glob in app.js), so the path is
    | overridden here — the package default is the lowercase "js/pages", which
    | makes `assertInertia(...)->component()` existence checks fail on
    | case-sensitive filesystems.
    |
    */

    'pages' => [

        'paths' => [resource_path('js/Pages')],

        'extensions' => ['js', 'jsx', 'svelte', 'ts', 'tsx', 'vue'],

    ],

];
