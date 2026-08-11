<?php

declare(strict_types=1);

return [

    'form' => [
        'role' => 'Role',
        'name' => 'Name',
        'title' => 'Title',
        'abilities' => 'Abilities',
        'description' => 'Everything the panel declares. Whoever may work this screen hands out any of it, including to themselves.',
        'empty' => 'You hold no abilities of your own, so there is nothing here to hand on.',
        'inherited' => 'Held through a broader rule, not granted here.',
        'overruled' => 'Granted here, but a broader rule forbids it.',
        'restricted_owned' => 'It also holds a rule here for only what it owns, which the grid leaves untouched.',
        'restricted_records' => 'It also holds a rule here about one record, which the grid leaves untouched.|It also holds rules here about :count records, which the grid leaves untouched.',
        'manage' => 'Everything about it',
        'undeclared' => 'The policy behind this subject does not declare this action.',
        'collapse' => 'Show or hide the actions of this subject',
        'model_count' => ':granted of :total',
        'scope' => 'Weight',
    ],

    'presets' => [
        'label' => 'Set every action',
        'read' => 'Reading only',
        'all' => 'Everything',
        'none' => 'Nothing',
    ],

    'summary' => [
        'granted' => '{1} 1 granted|[2,*] :count granted',
        'forbidden' => '{1} 1 forbidden|[2,*] :count forbidden',
        'neutral' => '{1} 1 not granted|[2,*] :count not granted',
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

    'relation' => [
        'title' => 'Roles',
        'assign' => 'Assign a role',
        'assign_submit' => 'Assign',
        'retract' => 'Take away',
        'role' => 'Role',
        'empty' => 'This account holds no role.',
    ],

    'field' => [
        'label' => 'Roles',
        'note' => 'What this account will be able to do. A role it may not be handed is shown but cannot be ticked.',
    ],

];
