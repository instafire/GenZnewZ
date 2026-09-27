<?php

return [
    'name' => 'Language Advanced',
    'description' => 'Advanced language features for multilingual content',
    'import' => [
        'rules' => [
            'id' => 'ID is required and must be a valid ID.',
            'name' => 'Name is required and must be a string with a maximum length of 255 characters.',
            'description' => 'Description must be a string with a maximum length of 400 characters if provided.',
            'content' => 'Content must be a string with a maximum length of 300,000 characters if provided.',
            'location' => 'Location must be a string with a maximum length of 255 characters if provided.',
            'floor_plans' => 'Floor plans must be a valid string if provided.',
            'faq_schema_config' => 'FAQ schema configuration must be a valid string if provided.',
            'faq_ids' => 'FAQ IDs must be a valid array if provided.',
        ],
    ],
    'export' => [
        'total' => 'Total',
    ],
    'import_model_translations' => ':model Translations',
    'export_model_translations' => ':model Translations',
    'import_description' => 'Import translations for :name from a CSV/Excel file.',
    'export_description' => 'Export translations for :name to a CSV/Excel file.',
];
