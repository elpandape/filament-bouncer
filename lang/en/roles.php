<?php

declare(strict_types=1);

return [

    'form' => [
        'abilities_note' => 'Everything the panel declares. Whoever may work this screen hands out any of them, including to themselves.',
        'name' => 'Name',
        'title' => 'Title',
        'empty' => 'You hold no abilities of your own, so there is nothing here to hand on.',
        'inherited' => 'Held through a broader rule, not granted here.',
        'overruled' => 'Granted here, but a broader rule forbids it.',
        'restricted_owned' => 'It also holds a rule here for only what it owns, which the grid leaves untouched.',
        'restricted_records' => 'It also holds a rule here about one record, which the grid leaves untouched.|It also holds rules here about :count records, which the grid leaves untouched.',
        'manage' => 'Manage',
        'forbidden_count' => '{1} 1 forbidden|[2,*] :count forbidden',
        'model_count' => ':granted of :total',
        'requires_stance' => 'Grant or forbid at least one ability before continuing.',
        'reserved' => 'That name belongs to the role that holds everything, which the reconciliation writes and this screen does not.',
        'name_placeholder' => 'e.g. support',
        'name_help' => 'Lowercase and dashes. It is the role name in Bouncer, the one written in the code.',
        'title_placeholder' => 'User support',
        'title_help' => 'How it is shown in listings and record pages. Left blank on creation, Bouncer derives it from the name.',
        'protected_notice' => '<b>:name</b> is the protected role: only somebody who already holds it hands it out, and nobody takes it off its last holder. No new role may bear the name.',
        'save' => 'Save changes',
        'cancel' => 'Cancel',
    ],

    'create' => [
        'subtitle' => 'Name it and say what whoever holds it may do. Nothing is saved until you create it.',
    ],

    'summary' => [
        'granted' => '{1} 1 granted|[2,*] :count granted',
        'forbidden' => '{1} 1 forbidden|[2,*] :count forbidden',
        'neutral' => '{1} 1 not granted|[2,*] :count not granted',
    ],

    'grid' => [
        'subject' => 'Subject',
        'clear' => 'Clear',
        'undeclared' => 'not declared',
        'preset_read' => 'Read only',
        'note_legend' => 'The dot marks a rule narrowed to what its holder owns or to a single record: this screen neither writes nor removes it, so its box says less than the role can do.',
        'hint' => 'One click cycles the three stances; hold Shift to go back. A denial beats any grant arriving through another role.',
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
        'scope' => 'Tenant',
        'title' => 'Title',
        'created' => 'Created',
        'name' => 'Role',
        'reaches_all' => 'Everything, through the wildcard',
        'updated' => 'Changed',
        'empty' => 'No role has been composed yet.',
        'search' => 'Search by name or title',
        'locked' => 'This role is not worked on from here: it is the way back in, or one you hold.',
    ],

    'record' => [
        'identity_note' => 'The name the code asks by, and the one read on screen.',
        'title_empty' => 'No title',
        'metadata' => 'Metadata',
        'scope' => 'Tenant',
        'scope_global' => 'Global',
        'abilities_heading' => 'Abilities',
        'abilities_note' => 'What this role grants and what it forbids, and nothing else.',
        'orphans_heading' => 'Orphan abilities',
        'narrowed_heading' => 'Narrowed',
        'narrowed_note' => 'Composed by hand on the abilities screen. The grid of this role neither writes nor removes them.',
        'owned' => 'only what it owns',
        'record_gone' => 'gone',
        'silent_spelled' => 'Says nothing about :names.',
        'silent_counted' => 'Says nothing about another :count subjects.',
        'silent_more' => 'See which',
        'and' => 'and',
        'tags_empty' => 'This role grants nothing and forbids nothing.',
        'orphans_loose' => 'No subject',
        'orphans_none' => 'Nothing to lose',
        'orphans_some' => 'About to be lost',
        'orphans_note_none' => 'Nothing this role says points at an ability the code has stopped declaring.',
        'orphans_note_some' => 'The next sync with --prune takes them, and what they granted with them.',
        'identity' => 'Identity',
        'name' => 'Name',
        'title' => 'Title',
        'updated' => 'Updated',
        'created' => 'Created',
        'forbidden_heading' => 'Forbidden',
        'forbidden_empty' => 'This role forbids nothing.',
        'forbidden_note' => 'A denial beats any grant arriving through another role.',
        'holders_heading' => 'Users with this role',
        'holders_empty' => 'Nobody holds this role.',
        'retract' => 'Take the role away',
        'last_holder' => 'The way back in is never taken off its last holder.',
    ],

    'coverage' => [
        'catalog' => 'Coverage over the :total abilities of the catalogue',
        'granted' => '{1} granted|granted',
        'forbidden' => '{1} forbidden|forbidden',
        'neutral' => 'not defined',
        'of' => 'of :total',
    ],

    'relation' => [
        'title' => 'Roles',
        'assign' => 'Assign a role',
        'assign_submit' => 'Assign',
        'retract' => 'Take away',
        'empty' => 'This account holds no role.',
    ],

    'field' => [
        'label' => 'Roles',
        'note' => 'What this account will be able to do. A role it may not be handed is shown but cannot be ticked.',
    ],

];
