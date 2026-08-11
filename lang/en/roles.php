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
        'reserved' => 'That name belongs to the role that holds everything, which the reconciliation writes and this screen does not.',
        'scope' => 'Weight',
    ],

    'wizard' => [
        'identity' => 'The role',
        'identity_hint' => 'What it is called, and what it is called by.',
        'abilities' => 'Abilities',
        'abilities_hint' => 'What whoever holds it will be able to do.',
        'review' => 'Review',
        'review_hint' => 'What is about to be written.',
        'reading' => ':name is about to be created granting :granted of the :total abilities the panel declares, and forbidding :forbidden.',
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

    'resource' => [
        'label' => 'Role',
        'plural' => 'Roles',
    ],

    'table' => [
        'abilities' => 'Abilities',
        'name' => 'Role',
        'coverage' => 'Reach',
        'reading' => ':granted of :total',
        'reaches_all' => 'Everything, through the wildcard',
        'holders' => 'Accounts',
        'updated' => 'Changed',
        'empty' => 'No role has been composed yet.',
    ],

    'stats' => [
        'roles' => 'Roles',
        'roles_note' => 'Composed on this screen.',
        'abilities' => 'Abilities declared',
        'abilities_note' => 'What the panel is able to ask about.',
        'forbidden' => 'Denials in force',
        'forbidden_note' => 'A denial beats any grant reaching the same ability.',
        'unassigned' => 'Accounts without a role',
        'unassigned_note' => 'They reach the panel and can do nothing in it.',
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
