<?php

declare(strict_types=1);

return [

    'form' => [
        'role' => 'Role',
        'name' => 'Name',
        'title' => 'Title',
        'abilities' => 'Abilities',
        'description' => 'Only the abilities you hold yourself are shown, because those are the only ones you are able to hand on — or to take away.',
        'empty' => 'You hold no abilities of your own, so there is nothing here to hand on.',
        'inherited' => 'Held through a broader rule, not granted here.',
        'overruled' => 'Granted here, but a broader rule forbids it.',
        'restricted_owned' => 'It also holds a rule here for only what it owns, which the grid leaves untouched.',
        'restricted_records' => 'It also holds a rule here about one record, which the grid leaves untouched.|It also holds rules here about :count records, which the grid leaves untouched.',
        'manage' => 'All',
    ],

    'tabs' => [
        'subjects' => 'Resources & models',
        'pages' => 'Pages',
        'widgets' => 'Widgets',
        'custom' => 'Custom',
    ],

    'table' => [
        'abilities' => 'Abilities',
    ],

];
