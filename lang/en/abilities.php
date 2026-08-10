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

    'declared' => 'Abilities are declared by the code that asks about them — a policy method, a page, a widget, or the «custom» key of the configuration — and written by filament-bouncer:reconcile. One created here would be a row the catalogue does not declare, which --check would fail on and --prune would delete.',

    'name_field' => 'Name',

    'entity_field' => 'Model',

    'no_entity' => '— none —',

    'holders' => 'Roles',

    'declared_column' => 'Declared',

    'declared_yes' => 'by the code',

    'declared_no' => 'by nothing',

    'only_owned' => 'Only what its holder owns',

    'holders_section' => 'Who holds it',

    'holders_note' => 'The same rows the roles screen writes, seen from the other end. A cell here and the cell there are the same row of the same table.',

    'broader_short' => 'through a broader rule',

    'nobody_short' => 'nobody',

    'withheld' => 'This ability is not one you hold yourself, so it is not yours to hand on.',

];
