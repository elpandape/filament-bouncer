<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'Ability',
        'plural' => 'Abilities',
    ],

    'form' => [
        'rule' => 'Rule',
        'rule_note' => 'What the code asks the Gate, and how that reads on screen.',
        'reach' => 'Reach',
        'reach_note' => 'What the rule decides about. The three answers together are what tell it apart from another of the same name.',
        'restrictions' => 'Restrictions',
        'restrictions_note' => 'What Bouncer keeps in the «options» column.',
        'tenant' => 'Tenant',
        'metadata' => 'Metadata',

        'name' => 'Name',
        'name_from_list' => 'The actions the panel declares today: its policy methods, plus the wildcard.',
        'name_by_hand' => 'A name the catalogue does not declare yet. Bouncer never reports a typo: it answers no, for ever.',
        'take_name' => 'Write it by hand',
        'pick_name' => 'Pick from the list',
        'any_action' => 'Any action (:wildcard)',

        'model' => 'Model',
        'model_note' => 'The models the panel puts on screen, plus the wildcard.',
        'model_none' => 'None: the rule speaks of no model',
        'any_model' => 'Any model',

        'record' => 'Record',
        'record_note' => 'Fences the rule to one record of that model. Left empty it reaches them all.',
        'record_missing' => 'There is no record with that id',

        'owned' => 'Only what is owned',
        'owned_note' => 'Fences the rule to what its holder owns, per the «ownership» key of the configuration.',

        'scope' => 'Tenant',
        'scope_note' => 'Empty means global. With a tenant, only this screen sees it.',
        'scope_global' => 'Global',

        'options' => 'Options',
        'options_note' => 'Bouncer keeps its constraint tree here, under the «constraints» key. It is one level deep: a nested value would be flattened on save.',
        'options_key' => 'Key',
        'options_value' => 'Value',
        'options_empty' => 'No restrictions',

        'created' => 'Created',
        'updated' => 'Modified',

        'twin' => 'There is already an identical ability: same name, same model, same record, same «only owned» and same tenant. Two identical rows are granted and withdrawn separately, so whoever withdrew one would believe the rule was gone.',
    ],

    'table' => [
        'name' => 'Name',
        'title' => 'Title',
        'title_empty' => 'No title',
        'reach' => 'Reach',
        'reach_record' => 'Record #:id',
        'reach_any' => 'Any model',
        'model_none' => 'No model',
        'owned' => 'Only what is owned',
        'narrow' => 'Fence',
        'narrow_note' => 'Compose another rule like this one, fenced to a record',
        'empty' => 'The catalogue has written no abilities yet',
        'empty_note' => 'They are written by filament-bouncer:reconcile out of what the panel\'s policies declare.',
    ],

    'record' => [
        'name_empty' => 'No title',
        'model_none' => 'None',
        'record_all' => 'All',
    ],

    'probe' => [
        'label' => 'Try it',
        'heading' => 'What the Gate answers',
        'note' => 'Who to ask about. Nothing is written: this is Bouncer\'s answer right now.',
        'close' => 'Close',
        'holder' => 'Who',
        'roles' => 'Roles',
        'accounts' => 'Accounts',
        'record' => 'About the record',
        'record_note' => 'Left empty it asks about the whole model. A rule fenced to what its holder owns only answers yes about a record.',
        'unaskable' => 'This rule cannot be asked about: it names a model that no longer loads, so the Gate would answer no for the wrong reason. Mend it before trying it.',
        'choose' => 'Pick a role or an account.',
        'yes' => 'Yes. The Gate answers that they can.',
        'no' => 'No. The Gate answers that they cannot.',
    ],

    'declared' => [
        'label' => 'Declared',
        'declared' => 'by the code',
        'drifted' => 'by nothing',
        'apart' => 'outside the catalogue',
        'declared_note' => 'The code declares this ability, so the reconciliation keeps it.',
        'drifted_note' => 'Nothing declares this ability any more. The next filament-bouncer:reconcile --prune takes it away, and every grant pointing at it with it.',
    ],

    'health' => [
        'twin' => [
            'label' => 'Duplicate',
            'question' => 'Is there another row like it?',
            'note' => 'Another row says exactly the same thing. They are granted and withdrawn separately: whoever withdraws one will believe the rule is gone.',
            'yes' => 'Yes: #:id says exactly the same thing.',
            'no' => 'No: no other row says the same.',
        ],
        'ghost-model' => [
            'label' => 'Ghost model',
            'question' => 'Does the model load?',
            'note' => 'The model it speaks of can no longer be loaded. Bouncer answers no, for ever, without ever saying the class is not there.',
            'yes' => 'Yes: :class.',
            'no' => 'No: «:class» can no longer be loaded.',
            'none' => 'It speaks of no model.',
            'any' => 'It speaks of any model.',
        ],
        'ghost-record' => [
            'label' => 'Ghost record',
            'question' => 'Does the record it fences exist?',
            'note' => 'It is fenced to a record that has been deleted. Should that id be reused, the rule wakes up pointing at something else.',
            'yes' => 'Yes: record #:id is there.',
            'no' => 'No: record #:id is gone.',
            'none' => 'It fences no record.',
            'unknown' => 'Cannot be told: the model does not load.',
        ],
        'invisible' => [
            'label' => 'Invisible',
            'question' => 'Can the whole system see it?',
            'note' => 'It carries a tenant: the roles grid does not show it and Bouncer does not answer it. This screen is the only one that shows it.',
            'yes' => 'Yes: it is global, so the whole system sees it.',
            'no' => 'No: it carries tenant :scope, and only this screen shows it.',
        ],
        'heading' => 'Health',
        'note' => 'Four things no other screen checks.',
        'column' => 'Health',
        'clean' => 'Nothing wrong',
        'widget' => [
            'heading' => 'Health of the abilities',
            'sound' => ':total abilities, and none with anything to look at.',
            'ailing' => ':total abilities, :ailing with something to look at.',
            'nothing' => 'Nothing to look at',
            'look' => 'See which',
        ],
    ],

];
