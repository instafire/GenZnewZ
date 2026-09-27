<?php

return [
    [
        'name' => 'Authors',
        'flag' => 'author.index',
    ],
    [
        'name' => 'Create',
        'flag' => 'author.create',
        'parent_flag' => 'author.index',
    ],
    [
        'name' => 'Edit',
        'flag' => 'author.edit',
        'parent_flag' => 'author.index',
    ],
    [
        'name' => 'Delete',
        'flag' => 'author.destroy',
        'parent_flag' => 'author.index',
    ],
];
