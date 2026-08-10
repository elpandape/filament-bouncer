<?php

declare(strict_types=1);

return [

    'created' => 'Created :count ability.|Created :count abilities.',
    'deleted' => 'Deleted :count ability, and every grant that pointed at it.|Deleted :count abilities, and every grant that pointed at one.',
    'kept' => 'Left :count ability in place that the catalogue no longer declares. Pass --prune to delete it.|Left :count abilities in place that the catalogue no longer declares. Pass --prune to delete them.',
    'matches' => 'The store matches the catalogue.',
    'missing' => 'Missing from the store',
    'extra' => 'Stored but no longer declared',
    'open' => 'Open to everybody, because their model has no policy',
    'privileged' => 'The privileged role [:name] is missing, or no longer holds the wildcard.',
    'policies' => [
        'written' => 'written',
        'kept' => 'already there',
        'none' => 'Every resource in the panel already has a policy.',
        'next' => 'Run filament-bouncer:reconcile so the abilities these policies declare come into being.',
    ],

];
