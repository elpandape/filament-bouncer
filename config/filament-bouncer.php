<?php

declare(strict_types=1);

return [

    /*
     * The panel whose resources, pages and widgets declare the catalogue. Leaving this
     * null uses the panel the application marked as its default, which is the right
     * answer for the single-panel applications this package is built for.
     */
    'panel' => null,

    /*
     * Whether this installation scopes Bouncer's rows to a tenant.
     *
     * It decides whether the tenant is part of the abilities screen's vocabulary — a column, a
     * field, an entry — or something that is simply not there, and whether a row carrying one is
     * reported as an anomaly. Leaving this null asks the panel whether Filament's own tenancy is
     * turned on for it, which is the one signal that is both about this panel and settled at boot.
     */
    'tenancy' => null,

    /*
     * How the roles resource presents itself inside the panel. These are the consuming
     * application's decisions, not the package's: the group may be called something
     * else, the order depends on what else that panel holds, and the icon on whichever
     * family that project already uses.
     */
    'navigation' => [
        'icon' => null,
        'group' => null,
        'sort' => null,
        'slug' => 'security/roles',
    ],

    /*
     * How the abilities screen presents itself. It shares the navigation group with the
     * roles resource, because they are the two sides of one thing.
     */
    'abilities' => [
        'icon' => null,
        'sort' => null,
        'slug' => 'security/abilities',
    ],

    /*
     * Which actions weigh the same. The grid groups its columns by these and tints the
     * headings, so that "see a list" cannot sit next to "delete for good" looking like
     * the same decision. Anything not named here counts as a write.
     */
    'scopes' => [
        'read' => ['viewAny', 'view'],
        'withdraw' => ['delete', 'deleteAny'],
        'irreversible' => ['forceDelete', 'forceDeleteAny'],
    ],

    /*
     * Which column says a record belongs to somebody, model by model.
     *
     * Bouncer asks about ownership on every check it answers about a record, whether or
     * not a single ability was ever held down to what its holder owns. Told nothing about
     * a model it guesses a column named after whoever is asking — `user_id` — and under
     * `Model::shouldBeStrict()` reading a column that is not there throws, from inside a
     * view, naming a column nobody ever wrote.
     *
     * Left empty nobody owns anything, and the guess is out of reach. Name a model here
     * and its column is read only when the record carries it, so one loaded without it
     * answers no rather than throwing. Column names, never closures: this file is cached.
     */
    'ownership' => [
        // \App\Models\Post::class => 'author_id',
    ],

    /*
     * Models that have a policy but no resource in the panel, and would otherwise never
     * reach the catalogue. A model that lives in a vendor package is the usual case.
     */
    'models' => [
        // \Laravel\Passkeys\Models\Passkey::class,
    ],

    /*
     * Abilities no component declares, as a map of name to scope. Anything listed here
     * has to be asked about by hand somewhere in the application, because nothing else
     * will: Bouncer answers "no" to an ability nobody was ever granted, and never warns
     * that the name does not exist.
     */
    'custom' => [
        // 'impersonate-users' => 'write',
    ],

    /*
     * Resources, pages and widgets the catalogue leaves out. This is the escape hatch
     * for a component that is deliberately open to everyone who reaches the panel.
     */
    'ignore' => [
        // \Filament\Pages\Dashboard::class,
    ],

    /*
     * The words the grid is read in, when yours differ from the package's. Anything set
     * here wins over the package's translations, which in turn win over the readable
     * form of the method name — so an action your own policy invented reads sensibly
     * without anything being translated first.
     */
    'labels' => [
        'actions' => [
            // 'viewAny' => 'Browse',
        ],
        'scopes' => [],
        'stances' => [],
    ],

    /*
     * An icon for each entity on the roles screen, keyed by model class. An entity
     * named here is drawn with it; anything else is drawn without one. Names only —
     * this file is cached, and `config:cache` throws on a closure.
     */
    'icons' => [
        // \App\Models\User::class => 'heroicon-o-users',
    ],

    /*
     * The role that holds everything. The roles screen refuses to edit it, and the
     * reconcile command makes sure it exists and is granted the wildcard. That is what
     * makes it the way back in when a mistake leaves nobody able to hand out abilities.
     *
     * Leaving it null means there is no such way back.
     */
    'privileged_role' => null,

];
