<?php

return [
    [
        'name' => 'Import Translations',
        'flag' => 'translations.import',
        'parent_flag' => 'tools.data-synchronize',
    ],
    [
        'name' => 'Export Translations',
        'flag' => 'translations.export',
        'parent_flag' => 'tools.data-synchronize',
    ],
    [
        'name' => 'Import Property Translations',
        'flag' => 'property-translations.import',
        'parent_flag' => 'tools.data-synchronize',
    ],
    [
        'name' => 'Export Property Translations',
        'flag' => 'property-translations.export',
        'parent_flag' => 'tools.data-synchronize',
    ],
];
