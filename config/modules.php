<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled modules
    |--------------------------------------------------------------------------
    | List module folder names (PascalCase) in the order you want them to
    | appear in the sidebar. ModuleDiscoveryServiceProvider will boot each
    | module's ServiceProvider in this order.
    |
    | Example:
    |   'enabled' => ['Core', 'POS', 'Ecommerce', 'Account', 'HR'],
    */
    'enabled' => [
        // 'Core',
        // 'POS',
        // 'Ecommerce',
        // 'Account',
        // 'HR',
        // 'Inventory',
        // 'CRM',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules base path
    |--------------------------------------------------------------------------
    | Absolute path to the directory that contains all module folders.
    | Defaults to a Modules/ folder at the Laravel project root.
    */
    'path' => base_path('Modules'),

];
