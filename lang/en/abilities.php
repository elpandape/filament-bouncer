<?php

declare(strict_types=1);

return [

    'title' => 'Abilities',

    'ability' => 'Ability',

    'direct' => 'granted on this role',

    'broader' => 'Outlined: the role answers yes without a rule of its own naming the ability — it holds it through a broader one.',

    'nobody' => 'no rule of theirs reaches it',

    'empty' => 'There is nothing to report on yet: either you hold no abilities of your own, or no role has been created.',

    'retitle' => 'Rename',

    'retitle_note' => 'The title is read by people and by nothing else. The name the code asks the Gate, and the model it asks about, are declared in code and cannot be changed here.',

    'title_field' => 'Title',

    'retitled' => 'Renamed.',

    'unreconciled' => 'This ability has no row yet. Run filament-bouncer:reconcile first.',

    'declared' => 'The name the code asks the Gate, and the model it asks about, are declared by the code that asks — a policy method, a page, a widget, or the «custom» key of the configuration — and written by filament-bouncer:reconcile. Neither is rewritten here.',

    'narrow' => 'Narrow an ability',

    'narrow_note' => 'The plain rule — «may change posts» — is declared by the code that asks about it and written by filament-bouncer:reconcile, so it is not composed here. What the code has no way to say is how far the rule reaches: that is what this screen makes. A row narrowed to one record, or to what its holder owns, is one the reconciliation never speaks for, so --check does not fail on it and --prune does not take it away.',

    'narrow_required' => 'An ability that narrows nothing is the plain one, which the code declares and filament-bouncer:reconcile writes. Hold it down to what its holder owns, to one record, or to both.',

    'duplicate' => 'That row already exists. Open it rather than writing a second one: two rows saying the same thing are handed out and cleared separately, and the screen would only ever show you one of them.',

    'action_field' => 'Action',

    'only_owned_note' => 'The rule holds only for the records its holder owns.',

    'record_field' => 'One record',

    'record_note' => 'The key of the single record the rule holds for. Leave it empty to reach all of them.',

    'compose_note' => 'Written out as the choices are made, and yours to change. The title is read by people and by nothing else.',

    'owned_suffix' => 'only what they own',

    'record_suffix' => 'record :id',

    'reach' => 'Reach',

    'reach_all' => 'all of them',

    'narrowed_legend' => 'This rule reaches less far than the one the grid holds, so it has no cell there. Handing it out here writes exactly this row and leaves the plain one alone.',

    'name_field' => 'Name',

    'entity_field' => 'Model',

    'no_entity' => '— none —',

    'holders' => 'Roles',

    'declared_column' => 'Declared',

    'declared_yes' => 'by the code',

    'declared_no' => 'by nothing',

    'declared_apart' => 'outside the catalogue',

    'only_owned' => 'Only what its holder owns',

    'holders_section' => 'Who holds it',

    'holders_note' => 'The same rows the roles screen writes, seen from the other end. A cell here and the cell there are the same row of the same table.',

    'broader_short' => 'through a broader rule',

    'nobody_short' => 'nobody',

    'withheld' => 'The code no longer declares this ability, so there is nothing here to hand out.',

];
