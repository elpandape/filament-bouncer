<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'Ability',
        'plural' => 'Abilities',
    ],

    'narrow' => 'Narrow an ability',

    'saved' => 'What each role says about this rule has been written.',

    'form' => [
        'rule' => 'The rule',
        'name' => 'Name',
        'entity' => 'Model',
        'no_entity' => '— none —',
        'reach' => 'Reach',
        'title' => 'Title',
        'title_note' => 'Read by people and by nothing else.',
        'declared_note' => 'The name the code hands the Gate, and the model it hands along with it, are declared by the code that asks — a policy method, a page, a widget, or the «custom» key of the configuration — and written by filament-bouncer:reconcile. Neither is rewritten here.',
        'holders' => 'Who holds it',
        'holders_note' => 'The same rows the roles screen writes, seen from the other end. A cell here and a cell there are the same row of the same table.',
        'holders_empty' => 'No role has been composed yet.',
        'withheld' => 'The code no longer declares this ability, so there is nothing here to hand out. The next filament-bouncer:reconcile --prune takes the row away, and every grant pointing at it.',
    ],

    'reach' => [
        'all' => 'All of them',
        'owned' => 'Only what its holder owns',
        'record' => 'One record',
        'record_reading' => 'Record :id',
    ],

    'declared' => [
        'label' => 'Declared',
        'declared' => 'by the code',
        'drifted' => 'by nothing',
        'apart' => 'outside the catalogue',
        'declared_note' => 'The code declares this ability, so the reconciliation keeps it.',
        'drifted_note' => 'Nothing declares this ability any more. The next filament-bouncer:reconcile --prune takes it away, and every grant pointing at it with it.',
        'apart_note' => 'This rule was never the reconciliation\'s to declare, so --check does not fail on it and --prune does not take it away.',
    ],

    'wizard' => [
        'ability' => 'The ability',
        'ability_hint' => 'What may be done, and to what.',
        'reach' => 'Reach',
        'reach_hint' => 'How far the rule goes.',
        'review' => 'Review',
        'review_hint' => 'What is about to be written.',
        'subject' => 'Model',
        'action' => 'Action',
        'reach_field' => 'The rule holds for',
        'record' => 'Record',
        'record_note' => 'The key of the single record the rule holds for.',
        'title' => 'Title',
        'reading' => 'About to be written: :rule, reaching :reach.',
        'nothing' => 'nothing chosen yet',
    ],

    'refusals' => [
        'narrow' => 'A rule that narrows nothing is the plain one, which the code declares and filament-bouncer:reconcile writes. Hold it down to what its holder owns, or to a single record.',
        'duplicate' => 'That row already exists. Open it rather than writing a second one: two rows saying the same thing are handed out and cleared apart, and this screen would only ever show you one of them.',
        'unknown' => 'The catalogue declares no such pair, and Bouncer never complains about a name nobody declared — it answers no, for ever, to everybody.',
        'record_needs_model' => 'A rule about a single record needs a model to look the record up in, and this ability is about none.',
    ],

    'table' => [
        'name' => 'Ability',
        'entity' => 'Model',
        'reach' => 'Reach',
        'holders' => 'Held by',
        'holder' => ':role — :stance',
        'empty' => 'Nothing has been reconciled yet. Run filament-bouncer:reconcile.',
    ],

];
