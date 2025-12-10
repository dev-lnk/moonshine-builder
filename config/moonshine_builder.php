<?php

return [
    // Directory where schematic files in json, yaml, etc. are stored.
    'builds_dir' => base_path('builds'),

    // Base path for models directory.
    'base_model_path' => 'app/Models',

    // Notification of duplicate files of models and resources with a new generation.
    'is_confirm_replace_files' => true,

    // Ask about adding a new resource to the provider.
    'is_confirm_change_provider' => false,

    // Ask about adding a new resource to the menu.
    'is_confirm_change_menu' => false,
];
