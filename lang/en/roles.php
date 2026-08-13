<?php

declare(strict_types=1);

return [

    'form' => [
        'role' => 'Role',
        'name' => 'Name',
        'title' => 'Title',
        'empty' => 'You hold no abilities of your own, so there is nothing here to hand on.',
        'inherited' => 'Held through a broader rule, not granted here.',
        'overruled' => 'Granted here, but a broader rule forbids it.',
        'restricted_owned' => 'It also holds a rule here for only what it owns, which the grid leaves untouched.',
        'restricted_records' => 'It also holds a rule here about one record, which the grid leaves untouched.|It also holds rules here about :count records, which the grid leaves untouched.',
        'manage' => 'Everything about it',
        'forbidden_count' => '{1} 1 forbidden|[2,*] :count forbidden',
        'model_count' => ':granted of :total',
        'requires_stance' => 'Grant or forbid at least one ability before continuing.',
        'reserved' => 'That name belongs to the role that holds everything, which the reconciliation writes and this screen does not.',
        'name_placeholder' => 'e.g. support',
        'name_help' => 'Lowercase and dashes. It is the role\'s <code>name</code> in Bouncer.',
        'title_placeholder' => 'e.g. Customer support',
        'title_help' => 'How it reads on listings and record pages.',
        'protected_notice' => '<b>:name</b> is the protected role: only somebody who already holds it hands it out, and nobody takes it off its last holder. No new role may bear the name.',
        'save' => 'Save changes',
        'cancel' => 'Cancel',
    ],

    'wizard' => [
        'subtitle' => 'Three steps: identity, permissions and review. Nothing is saved until the end.',
        'identity' => 'The role',
        'identity_hint' => 'What it is called, and what it is called by.',
        'identity_heading' => 'The role\'s identity',
        'identity_note' => 'The name is the internal identifier; the title is what people see.',
        'abilities' => 'Abilities',
        'abilities_heading' => 'Abilities',
        'abilities_note' => 'The catalogue derives from the policies. For each action: grant it (✓), say nothing (–) or forbid it (✗). A denial beats any grant reaching the same ability from another role.',
        'abilities_hint' => 'What whoever holds it will be able to do.',
        'review' => 'Review',
        'review_heading' => 'Review',
        'review_note' => 'What is about to be written, subject by subject.',
        'review_hint' => 'What is about to be written.',
    ],

    'review' => [
        'silent' => 'Nothing said: the role holds back',
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

    'list' => [
        'subtitle' => 'The panel\'s security posture at a glance: who may do what, and what is forbidden.',
    ],

    'table' => [
        'name' => 'Role',
        'coverage' => 'Reach',
        'reaches_all' => 'Everything, through the wildcard',
        'holders' => 'Accounts',
        'updated' => 'Changed',
        'empty' => 'No role has been composed yet.',
        'search' => 'Search by name or title',
        'legend' => 'coverage over the :total abilities of the catalogue',
        'locked' => 'This role is not worked on from here: it is the way back in, or one you hold.',
    ],

    'edit' => [
        'holders' => '{0} 0 users with this role|{1} 1 user with this role|[2,*] :count users with this role',
        'updated' => 'updated :when',
    ],

    'record' => [
        'identity' => 'Identity',
        'name' => 'Name',
        'title' => 'Title',
        'holders' => 'Users holding the role',
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

    'stats' => [
        'roles' => 'Roles',
        'abilities' => 'Abilities declared',
        'forbidden' => 'Denials in force',
        'unassigned' => 'Accounts without a role',
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
