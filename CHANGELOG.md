# Changelog

All notable changes to `elpandape/filament-bouncer` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html). From
`1.0.0`, breaking the public API takes a major bump — and the names abilities are stored
under count as public API, because they are rows in somebody's database.

## [5.3.1] - 2026-08-12

### Added

- **The abilities step of the wizard heads itself**, the way the approved design does:
  what the step is, where the catalogue comes from, and how to read a row — grant, say
  nothing, or forbid — before the first subject.

### Fixed

- **A role that says nothing about anything is refused at that step.** Every cell left
  neutral grants nothing, forbids nothing and answers no question, and the wizard used to
  carry it all the way to the review and write it anyway. Editing is untouched on
  purpose: clearing every cell back to neutral is how what a role holds is taken away.

## [5.3.0] - 2026-08-12

The screens matched against the approved design pair by pair, with custom Blade wherever
Filament's own components could not draw it.

### Added

- **The list of roles opens with four figures drawn as the design drew them** — the icon
  in a tinted box, the count beside it, red when it counts denials — and closes with the
  catalogue legend: how many abilities the bars measure against, and the three marks.
- **The reach column reads like the design**: a bar filling the column with the three
  counts coloured beneath it, and the wildcard saying so in words.
- **A locked row shows a padlock** where its menu would be — the privileged role and the
  one the reader holds — with the reason a hover away. Deleting lives in the row's own
  menu now, not spread across the row.
- **The identity step of the wizard says what it is doing**: a heading, a sentence, one
  field under the other with an example each, and an amber notice naming the protected
  role that no new role may be called after.
- **Saving lives inside the summary bar** on the edit screen, beside the counts it
  confirms, wired straight to the form. The bar is the one thing pinned to the screen
  while stances change, so the button that writes them belongs on it.
- **The record page of a role reads as a record**: identity as entries with the reach bar
  and its full legend, a card for what the role forbids — empty state and all — and the
  accounts holding it, each with the way to take the role back. The grid stays below,
  out of reach, so what a role holds is still read in the shape it is changed in.
- **The abilities list opens with its own four counts** — all, narrowed, wildcard,
  denials in use — and groups its rows under the model they decide about, the class
  under the heading, without offering the grouping controls: there is nothing else to
  group by.
- **How far a rule reaches is a badge**, and who holds it says in colour whether it is
  granted or forbidden to them.
- **Narrowing an ability starts from cards, not a dropdown**: one card per action the
  catalogue declares, and a sentence above the wizard that composes itself as the three
  choices are made — the same sentence the record page opens with once the rule exists.

### Removed

- **The weight mark beside each action.** The approved design never drew it; it was
  carried over from the matrix this grid replaced.
- **The delete button on the edit screen's header.** The design keeps everything
  destructive in the list row's menu, and both refusals still hold at the write.

### Upgrading

Run `php artisan filament:assets`. The four stat cards are drawn by the package now, so
an application that had restyled the old stats widget restyles `.fb-stat` instead.
Nothing else changes: no key renamed, no ability name moved, the form state keeps its
shape.

## [5.2.0] - 2026-08-11

5.1.0 gave the screens back their pieces. This gives them the measurements, taken from
the approved design's own stylesheet rather than read off a picture of it.

### Changed

- **A stance is a tinted chip, not a solid fill.** The chosen position now draws a pale
  ground, its mark in its own colour and a ring around it — which is what the design
  specified and what a picture of it does not tell you. Filled solid with a white glyph,
  the control read as a toolbar rather than as three words to choose between.
- **The rows keep the rhythm they were drawn with**: dashed rules between actions, the
  deeper indent under a subject, and taller controls. Approximating spacing by eye is how
  a screen ends up structurally right and visually anonymous.
- **The totals float over the page as a pill** — rounded, blurred, lifted — instead of a
  bar pinned to the edge of the card.
- **The catalogue's own card lost its heading and its sentence.** Both said what every row
  underneath says, and between them they pushed the first subject nearly a screenful down.
- **The name and the title take the full width**, as one band above the catalogue rather
  than a narrow card beside empty space.

### Added

- **The reach bar is on the screen that changes it**, not only on the one that reads it.
  Seeing how far a role already goes is the first thing anybody does before moving a
  single stance.

### Upgrading

Run `php artisan filament:assets`. Nothing else changes.

## [5.1.0] - 2026-08-11

The screens redrawn in 5.0.0 were faithful in structure and anonymous in look. This is
the design they were supposed to carry.

### Added

- **A subject can be drawn with an icon**, through a new `icons` key mapping a model
  class to an icon name. Anything not named there is drawn without one, which is what
  every panel gets until it says otherwise.
- **A subject says how many denials it holds**, in red, beside its count. Counted in the
  browser against the form's own state, so it moves while somebody is still deciding
  rather than after the save.
- **A denial reads as one wherever it is written**: the note on a forbidden row, and on a
  forbidden door, is red and sits beside the action instead of below it in the same
  amber every other note uses. A denial is not a warning among others — it beats every
  grant reaching the same ability.
- **Each of the four figures above the roles list carries an icon**, beside its label.

### Changed

- **A subject names the model it decides about**, not the policy that answers for it.
  Whoever hands out abilities recognises `App\Models\Post`; the policy behind it is a
  fact about the code, and the catalogue already carries that.
- **The presets fold into the subject's own heading**, behind one control, instead of
  standing as a row of three buttons above every subject's actions. On a panel of thirty
  resources that row was thirty lines competing with the only thing on the screen that
  matters.
- **The three stances read as one control again.** The group is outlined, the inactive
  positions are dimmer, and the chosen one lifts off the surface. "Not granted" no
  longer draws with more weight than "granted", which was exactly backwards.
- **The totals at the foot are chips**, each with its mark and its colour, rather than
  three plain numbers.

### Fixed

- **The pinned totals no longer cover a row for good.** The closing section is given room
  to scroll past them; without it the bar sat permanently over a subject heading, which
  is the one row nobody can scroll away from.

### Upgrading

Run `php artisan filament:assets` after updating: the stylesheet changed. Nothing else
does — no configuration key was renamed, no ability name moved, and the form state keeps
its shape. To draw icons beside your subjects, republish the configuration or add the
`icons` key to the copy you already have.

## [5.0.0] - 2026-08-11

The screen layer, cut out and drawn again from nothing. A stance is now chosen rather
than cycled to, and the classes that drew the old matrix went with it — which is the
whole of why this is a major.

### Changed

- **The roles grid is sections of rows, not a matrix.** A subject is a collapsible
  section, an action is a row, and each row carries one control with three positions —
  granted, not granted, forbidden — chosen directly. In a column three buttons never
  fitted, so the stance was a shape the reader decoded; and cycling a single cell meant
  reaching a denial by passing through a grant, a rule that existed on screen for a
  moment and never on purpose.
- **The weight of an action moved from a tinted column band to a mark on its own row**,
  there being no columns left to band. It exists for the reason it always did: "see a
  list" and "delete for good" must not look like the same decision.
- **The catalogue opens whole only when it is short.** Sixty rows or fewer and every
  section is open, because a screen showing only headings reads as broken and the fold
  costs a click on every subject somebody came to change; past that it arrives folded,
  because three buttons a row would otherwise draw five hundred at once.
- **Composing a role is a wizard of three steps** — identity, abilities, review — ending
  on a plain sentence of what is about to be written, because handing out abilities
  deserves reading once before it is done. Narrowing an ability takes the same three
  steps, and refuses one thing it did not before: a second row saying what one already
  says, because the screen only ever shows one of them.
- **The roles list draws how far each role reaches**: a bar of granted, forbidden and
  left alone against the whole catalogue, beside the accounts holding the role. A role
  reaching everything through the wildcard is drawn full and says why in words, because
  it holds no rule of its own for any cell and a bar from its grants alone would read as
  nothing at all.
- **The abilities list gathers its rows under the model they decide about**, because the
  name is the least distinguishing part of a rule — a dozen models all have a `view`.
  The reconciliation column keeps its three answers.
- **An ability's record page no longer calls the row a grant or a denial**, because that
  was false: the row is the thing itself, and granting and forbidding live in the pivot
  and belong to a holder — the same row is quite properly granted to one role and
  forbidden to another. The mark now goes beside each holder, on the record page and in
  the list.

### Added

- **A summary at the foot of the grid** — granted, forbidden, not granted — counted
  where the change happens, while it happens.
- **Presets per subject**: reading, everything, nothing. Only reading carries a list of
  its own; a shortcut for withdrawing or for the irreversible is a shortcut nobody
  should have.
- **Four figures above the roles list**: the roles, the abilities the panel declares,
  the denials in force, and the accounts that reach the panel holding no role at all.
  The last two are there precisely because no row below can show them.
- `Store\RoleCoverage`, how a role stands against the whole catalogue counted once —
  `granted`, `forbidden`, `neutral`, `total`, `reachesAll` — because four screens ask
  the same question and each counting it again would walk the catalogue four times over
  for a single row.
- The deletes the screens hide are armed, not just drawn: tests drive one by hand
  through every door a request could reach it by — the row, the selection, the record
  page, the edit page — and assert the row still standing.
- The domain is proven through its own API. The policies, the store and the privileged
  role were only ever exercised by mounting a screen, which proves a rule of
  authorisation sideways: the day the screen changes, the guarantee goes without
  anything turning red.

### Fixed

- **The navigation icon and group take the shapes Filament declares again**
  (`string|BackedEnum|Htmlable|null` and `string|UnitEnum|null`). Narrowed to strings,
  an application naming its icons with a backed enum met a type error raised from inside
  the sidebar, with the whole panel down and nothing on screen to say why. Both are
  pinned with an enum, which a string signature cannot carry.
- **A doomed rule refuses a stance at the write, not only on the screen.** A row the
  catalogue no longer declares withheld its cells and wrote them anyway, so a request
  that armed one made grants the next `--prune` would take away along with the row,
  silently. The drawing and the writing now ask one question about which rows are on
  their way out, so the two cannot come to disagree.
- **The privileged role's name cannot be claimed.** The screen refuses to edit that
  role, and without this the same refusal was a way to take its name hostage: composing
  a role under it granted nobody anything and left behind a role nobody could edit or
  delete from here. The reconciliation is what writes that role, so this screen never
  had business naming it.

### Removed

- `Filament\Concerns\FillsAbilityHolders`. Its work is done by
  `Filament\Concerns\PresentsAbility`, which also carries the reach onto the form.
- `Filament\Resources\Abilities\Schemas\AbilityComposer`. Its work is done by
  `Filament\Resources\Abilities\Schemas\NarrowAbility`.
- The view-facing methods that described the old matrix — `AbilityGrid::getTabs()`,
  `getActionColumns()`, `getBands()` and their siblings, and all six readers on
  `AbilityHolders`. Both fields keep their names, their place in the schemas and the
  shape of their form state; what changed is what a view may ask them, because there is
  no matrix left to describe.

### Kept

- `RolesField` and `RolesRelationManager`, by name and namespace: an application already
  using either changes nothing. So are the stored ability names, the configuration keys
  and the shape of the grid's form state — subject, action, stance — which is the shape
  the store reads and writes.

### Upgrading

Run `php artisan filament:assets` after updating: the stylesheet was rewritten with the
screens. A view published from 4.x with `--tag=filament-bouncer-views` no longer matches
what the fields provide — republish it and carry your edits over. Anything consuming the
two removed classes moves to the replacements named above; anything else — configuration,
stored abilities, the plugin registration — is as it was.

One thing worth grepping for: **the roles field answers to `roles` in the form state now,
not `filament_bouncer_roles`.** `RolesField::NAME` carries the new name, so code reading
the constant needs no change — but a test that filled the old string as a literal will
now fill a key nobody reads, and stay green while the roles it named go unassigned. That
is not hypothetical: it is exactly what happened to the first application upgraded.

## [4.4.0] - 2026-08-11

Roles are handed to a person from that person's screen, which until now only the console
could do.

### Added

- **A roles relation manager**, for the tab on an account's own screen: what it holds, and
  the two buttons that change it. The writes go through Bouncer's `assign()` and
  `retract()` rather than through the relation, because an assignment is a row of its own
  and not a column, and both are followed by a refresh — nothing in Bouncer invalidates
  its cache on a write, and one Livewire request reads after it writes.
- **A roles field**, for the screen where an account is created and a relation manager has
  no record to hang off. The privileged role is offered **disabled** rather than hidden to
  whoever may not hand it on, so its holders stay legible instead of being a gap, and the
  write is taken over here rather than left to the form, so a request naming that role by
  hand is refused the same way the screen refuses it.
- `PrivilegedRole::mayBeHandedOutBy()` and `PrivilegedRole::isLastHolder()`. The privileged
  role is handed on only by somebody who already holds it, and is never taken off its last
  holder: a way back in that nobody holds is not one.
- `Contracts\HoldsRoles`, which writes down what Bouncer's own trait already provides.
  Typed for the analyser and not at runtime, so no application has to implement it.

### Note

Nothing is narrowed to what the person filling the screen in holds. Being trusted with the
roles screen is the whole of the trust, as of `4.0.0`; the privileged role is the single
exception, and it is about the way back in rather than about privilege.

## [4.3.0] - 2026-08-11

Who owns what is a decision the application makes, and now it can say so.

### Added

- **An `ownership` key**, a map of model to column. Bouncer asks who owns a record on
  every check it answers about one, whether or not a single ability was ever held down to
  what its holder owns; told nothing it guesses a column named after whoever is asking and
  reads it through the model, which under `Model::shouldBeStrict()` throws from inside a
  view. Left empty nobody owns anything and the guess is out of reach. A named model reads
  its column out of the attributes the record actually carries, so one loaded without it
  answers no rather than throwing.
- `Support\Ownership`, which registers it, and `Exceptions\InvalidOwnership`, thrown while
  the application boots for a mangled entry — that one fails towards letting people
  through, so it is not left to be discovered.

### Fixed

- The comment describing the roles policy sat above the call that loads the translations,
  four statements away from what it described.

## [4.2.0] - 2026-08-11

A role holding the wildcard no longer reads as a role that can do nothing.

### Changed

- **A cell reached by a broader rule draws the tick, not the dash.** The dash was true of
  the row and false of the reader's question: a role holding nothing but the wildcard has
  no rule of its own for any cell in the grid, so every cell drew a dash and the screen
  said the role could do nothing at all. The tick is outlined rather than filled — the
  answer is yes, and the rule behind it is not this row's — and pressing it fills it in,
  which is the row taking that answer on itself.
- The label a screen reader hears says when a cell is answered by something broader.
- The roles form no longer describes a catalogue narrowed to what the person filling it in
  holds, which stopped being true in `4.0.0`.

### Added

- `AbilityGrid::getBroader()`, the cells a role answers yes to without a rule of its own
  naming the ability.

## [4.1.0] - 2026-08-10

The abilities screen makes the one row the code cannot write.

### Added

- **A rule can be narrowed from the screen.** Pick a model, pick an action, and hold it
  down to what its holder owns, to one record, or to both. Those are the rows the
  reconciliation deliberately never speaks for, so `--check` does not fail on them and
  `--prune` does not take them away — which is what makes offering the button honest. The
  plain row stays out of reach: that one the catalogue owns.
- The title writes itself as the choices are made, and stays editable.
- `create` on `AbilityRowPolicy`, so narrowing is governed like everything else. **The
  catalogue grows by one ability, so a deploy needs `filament-bouncer:reconcile`.**
- A **Reach** column on the list, saying how far each row goes. `update` on posts and
  `update` on the posts you wrote are the same two words and different rules.
- `RoleAbilities::stanceOnRow()` and `saveRow()`, which read and write one stored row
  instead of one catalogue entry.
- `AbilityStore::speaksFor()` and `isRestricted()`.

### Changed

- **The Declared column now has three answers, not two.** A row the reconciliation never
  spoke for — a wildcard, a narrowed rule — used to read "by nothing", the same warning as
  genuine drift that `--prune` will delete. It now reads "outside the catalogue".
- The holders panel writes a narrowed row **as itself**. Matching it to the catalogue by
  name would have found the plain rule and handed out that instead.
- The README no longer describes the narrowing that `4.0.0` removed.

### Fixed

- **"by the code" was true of every plain row, including drift.** The column asked the
  store whether the row existed, which it always did, instead of asking the catalogue
  whether it was declared. Nothing could ever be reported as undeclared.

### Removed

- `AbilityStore::find()`, whose only caller was that column.

## [4.0.0] - 2026-08-10

Whoever may work the screen hands out anything.

### Changed

- **The grid is no longer narrowed to what the person filling it in holds.** The policy
  decides who may be on the screen, and that is the only question asked: somebody who may
  edit roles hands out every ability the panel declares — including the wildcard, and
  including to themselves. This is how `yadahan/nova-bouncer` does it, whose abilities
  field carries no `relatableQuery`, no `authorize` and no `canSee`.
- The abilities screen offers every row the catalogue still declares, to anyone the policy
  let in.

### Fixed

- **A partial save cleared every cell it did not mention.** Harmless while the catalogue
  was narrowed and every form sent all of it; fatal once it was not, because the abilities
  screen sends exactly one cell — writing from that side would have taken every other
  ability of the role with it. Silence is not an instruction: a cell the state does not
  carry is left where it was.

### Removed

- `EditableCatalog`. `RoleAbilities` takes the catalogue registry instead.

### Kept

- A cell for something the panel does not declare still changes nothing: the save is
  driven off the catalogue, not off the request.

## [3.0.0] - 2026-08-10

The abilities screen is a resource, like roles.

### Changed

- **The abilities page is now a resource**, with a list, a detail screen and a form. The
  list is a real table — searchable, sortable, filterable — and its columns follow the
  design: the title, the name the code asks, the model, who holds it **and how**, and
  whether the code still declares it.
- The detail and edit screens carry the panel of holders: one ability, every role, each
  a cell that walks the three stances. **It writes the same rows the roles screen
  writes** — a cell here and the cell there are the same row of the same table — through
  the roles store, one role at a time, so this side inherits the same guarantees.

### Added

- A policy for the ability model, so the screen is governed like everything else. It
  carries no `create` and no `delete`, on purpose.
- The list flags every row the catalogue no longer declares. Such a row is not a mistake
  in itself — an application may check a name of its own — but it is drift, and `--prune`
  removes it without asking twice.

### Fixed

- **Nobody owns an ability.** With the screen registered, Bouncer starts asking about
  ownership of the ability rows themselves and reaches for `abilities.user_id`; under
  strict Eloquent the screen dies inside a Filament view naming a column nobody ever
  wrote. The same fix the roles table got in 1.0.2.

### Removed

- The `Abilities` page added in 2.1.0. The resource replaces it, and keeps its
  configuration keys.

### Not included, on purpose

- **Creating an ability.** An ability is a method on a policy, a page, a widget or a name
  in the `custom` configuration, and `filament-bouncer:reconcile` is what writes it. One
  made from a form would be a row the catalogue does not declare, which `--check` fails
  on and `--prune` deletes.

## [2.1.0] - 2026-08-10

### Added

- **An abilities screen: the other axis.** Not what a role can do, but who can do a
  thing — abilities down the side, roles across the top, which is the roles grid read
  sideways. For each holder it says whether somebody granted it or it merely fell out of
  a broader rule, which is the question the roles screen cannot answer without being
  opened once per role.
- It is read only, and has to be: an ability is a method on a policy, a page, a widget or
  a name in configuration, and the reconciliation is what puts it in the store. Offering
  to create one would be offering to store a name no `can()` will ever ask about.
- Its icon, sort order and slug are configurable under `abilities`; it shares the
  navigation group with the roles resource, because they are two sides of one thing.

### Changed

- The screen is a component of the panel like any other, so it governs itself and joins
  the catalogue. Every panel gains one ability, `page:…-abilities`, and somebody has to
  be granted it before the screen can be reached.

## [2.0.2] - 2026-08-10

### Fixed

- **The models grid grew a `use` column that no row in it could fill.** The catalogue's
  action list is the union across every kind, and an ability declared in configuration
  answers `use`, which no model does. Harmless before the kinds were split into tabs;
  since then it was a column of nothing. The columns are the union of what the gridded
  subjects declare, and the bands are counted from those.

## [2.0.1] - 2026-08-10

### Fixed

- **The grid was unreadable on a dark panel.** The stylesheet named the light tokens
  directly, so the subject names came out near-black on near-black and the tab bar was a
  white slab. Every colour now comes from a token set declared twice, and the dark theme
  tints its bands with the mid shade instead of the light one, which would have been a
  near-white slab.
- The switch is Filament's `dark` class alone. A `prefers-color-scheme` query would have
  darkened the grid inside a page somebody deliberately kept light — and Filament stamps
  that class for a system choice too, so nothing is lost.

## [2.0.0] - 2026-08-10

The grid is a table now, and the package draws it itself.

### Changed

- **A cell is one button that walks the three stances**, filled when this row is the
  rule, outlined when a broader rule reaches it or a rule it cannot write restricts it,
  pale when nothing says anything. Three buttons never fitted a column and stacking them
  made the grid as tall as the catalogue is wide.
- **The header and the first column stay put.** A row is unreadable once its name has
  scrolled off, and a column is unreadable once its heading has.
- Each subject carries the policy that answers for it, each column the method it asks,
  and each band its weight — read before any of it is read in detail.
- Tabs are the package's own, because Filament's live outside a field.

### Removed

- `Stance::color()`, `Stance::colors()`, `Stance::icon()`, `Stance::icons()` and
  `AbilityScope::color()`. Nothing draws from them any more: the colours are in the
  stylesheet, keyed by scope, and the marks are in the view. This is the whole reason
  for the major.

### Added

- A view, a stylesheet and a form field of the package's own. The stylesheet is
  registered with Filament, so `filament:assets` publishes it beside the panel's own and
  no build step is asked of the application; it reads from the panel's colour tokens and
  defines no utility class, so nothing depends on the consuming application's Tailwind.
- The view is publishable with `--tag=filament-bouncer-views`.

### Upgrading

Run `php artisan filament:assets` after updating. Nothing else changes: the stored
ability names, the configuration and the shape of the form state are all as they were.

## [1.5.1] - 2026-08-10

### Fixed

- **A cell overflowed into the column beside it.** Measured on a real panel: three marks
  side by side are 134 pixels wide and a column of a catalogue with eight actions is 103,
  so the headings stopped lining up with what they name. The marks are stacked again.
  They stack far shorter than the words did, which is what 1.4.0 bought; it did not buy
  room to lay them out in a row.

## [1.5.0] - 2026-08-10

### Added

- **An «All» column: the grant that covers a whole model.** Bouncer spells it `*` against
  the model, which is what `allow()->toManage()` writes. Until now it could only be made
  from tinker, and the grid could only report it — as an inherited stance on every other
  cell of the row. It is a column of its own now, tinted like the irreversible band it
  belongs to, and it grants the actions the model's policy has not been given yet.
- The column appears only where somebody can fill it, and a grant smuggled into the
  request by an authority who cannot manage that model writes nothing. Same rule as
  every other cell: nobody hands out what they do not hold.

### Unchanged on purpose

- The whole-model grant is kept out of the catalogue the reconciliation walks. Nothing
  ever checks it — the Gate is asked `view` or `delete`, and Bouncer matches this row on
  the way — so it is not an ability the panel asks about and must not be created as one.

## [1.4.0] - 2026-08-10

### Changed

- **A cell is read as a mark, not as three words.** A cell is one of as many columns as
  the catalogue has actions, so on any real panel it is about a hundred pixels wide.
  Three words never fitted and had to be stacked, which made the grid as tall as the
  catalogue is wide. Three marks fit side by side, joined.
- The word is not lost. It becomes the button's accessible name and its tooltip, so a
  screen reader announces it and a pointer reveals it — pinned by a test, because an
  icon-only control that says nothing to either is not a saving, it is a regression.

### Unchanged on purpose

- Pages, widgets and custom abilities keep their words. Those are read as lists, where
  there is a whole row to spend and nothing to line the cell up against.

## [1.3.0] - 2026-08-10

### Fixed

- **A grant over only what a role owns read as a grant over everything.** Bouncer keeps a
  grant over a whole model, a grant over one record and a grant over what the holder owns
  in three separate rows, and `allow()->to($name, Post::class)` matches exactly the first
  of them. The grid read all three and reported them as the plain stance, so a role that
  could delete only its own posts read as one that could delete anybody's.
- **That cell could not be turned off.** Clearing it removed a row that was never there,
  and the screen repainted the stance it had just cleared. The stances now read the row
  the grid writes, and nothing else.

### Added

- The store counts the rules the grid never offers, and the cell names them: the ones
  covering only what the role owns, and the ones about single records. Skipping them was
  necessary; staying silent about them would have been its own lie.
- The three notices on a cell now compose, so a stance can be reached through a broader
  rule and still be restricted elsewhere without either fact hiding the other.

### Guaranteed

- **A save never destroys what the grid never offered.** Pinned by a test, because the
  grid clearing a cell must leave a role's owned and per-record rules exactly where they
  were.

## [1.2.0] - 2026-08-10

### Fixed

- **The grid claimed a role lacked abilities it plainly held.** A role granted
  `everything()` keeps no row naming any single ability and still answers yes to all of
  them, so every cell read "not granted" about a role that could do anything. The cell
  now says when a stance is reached through a broader rule, and when a broader denial
  beats a grant made in the cell itself. This was the one lie a screen like this must
  not tell, and it had been told since the grid existed.

### Added

- The store can be asked what a role really answers, rather than only what its own rows
  say. Bouncer's clipboard is asked rather than the identifiers being matched here by
  hand, so the answer on screen and the answer at the Gate come from the same code.

### Unchanged on purpose

- The buttons still hold the row that names the ability exactly and nothing else,
  because that is the row they write back. Resolving them against the broader rules
  would make clearing a cell silently do nothing.

## [1.1.0] - 2026-08-10

### Added

- **The grid divides into tabs.** Resources and models keep their matrix; pages, widgets
  and abilities declared in configuration are read as lists, because each answers exactly
  one question and a grid one column wide is a worse way to read a list than a list is.
  A panel with a dozen pages no longer pushes its resources off the top of the screen.
- Tab labels in both languages, and a count on each tab.

### Unchanged on purpose

- One tab is not a tab. A panel that only exposes resources reads exactly as it did
  before, with no heading nobody asked for.

## [1.0.3] - 2026-08-10

### Fixed

- **The grid was unreadable on any real panel.** Three buttons carrying words were laid
  side by side inside a cell one column wide, and from about four actions onwards they
  overlapped into a smear. They are stacked now, and the section takes the whole width
  instead of half of it. Found by looking at it in a browser, which no test does.
- **The detail screen offered an Edit button that led to a refusal.** Filament does not
  ask the resource before drawing its header actions, so on the role you hold yourself
  and on the one that holds everything the button was shown and then answered 403. It is
  hidden now, as the equivalent action in the table already was. The same goes for the
  delete button on the edit screen.

## [1.0.2] - 2026-08-10

### Fixed

- **The roles screen died in any application running Eloquent strictly.** Bouncer asks
  about ownership on every check and, told nothing, guesses a `user_id` column on the
  record — which the roles table has never had. Lax applications got a null and never
  noticed; strict ones got a `MissingAttributeException` from inside a Blade view, naming
  a column nobody ever wrote. The package now says out loud that nobody owns a role.
- The test suite runs Eloquent strictly from now on. It did not before, which is the only
  reason this shipped: eleven existing tests reproduce the crash the moment the setting is
  turned on.

### Documented

- The ownership guess and how to answer it once for your own models.
- That `ownedVia()` cannot be called with a single argument at all — the form the
  documentation shows for setting a global rule uses the closure as an array key and
  raises a `TypeError`.

## [1.0.1] - 2026-08-10

### Fixed

- **The policy written for the user model itself did not parse.** Both the person asking
  and the record being asked about were called `$user`, which is a compile error rather
  than a subtle one, so the file could not be loaded at all. The record now falls back to
  `$model`, the same way Laravel's own generator does. Found by installing `1.0.0` into an
  application and running the generator against its user model.
- The generated files are now handed to PHP itself to check, rather than to the
  tokeniser. Two parameters alike tokenise perfectly well and only fall over on compile,
  which is why the original test passed.

## [1.0.0] - 2026-08-10

The loop is closed and the surface is frozen. Nothing new here beyond the decisions the
road to it left open, now settled in writing.

### Decided

- **Nobody hands on an ability they do not hold, not even by delegation.** Separating what
  you hold from what you may hand on is a real feature and it breaks the one invariant
  that makes the screen safe. Adding it later needs an explicit list of delegators and
  tests that attack them.
- **Abilities on a single record, or on what somebody owns, stay out.** Bouncer stores
  both and the reconciliation already refuses to touch either, so nothing is foreclosed;
  they are absent because a grid of subjects against actions has no honest place to draw
  them.
- **Constraints are never surfaced.** They are persisted and never evaluated, so offering
  them would be writing decorative JSON.
- **A model with a policy but no resource joins the catalogue through `models`.** That is
  the answer for the ones that live in a vendor package, passkeys being the case that
  raised the question.
- **The way back in is the reconcile command**, which is re-runnable, idempotent, and
  allowed in production: it only ever adds, and `--prune` is the one destructive flag.

### Added

- `filament-bouncer:assign`, the last step of the way back in: a role nobody holds opens
  no doors, and reaching for tinker to undo a lockout means reaching for it while locked
  out. It refuses a role that does not exist rather than creating one, which is the
  guarantee Bouncer itself gave up when it started creating roles it could not find.
- The stored ability names are now part of the compatibility promise.

## [0.6.0] - 2026-08-10

The words, and a pass over the public surface.

### Added

- English and Spanish translations under the package's own domain, publishable with
  `--tag=filament-bouncer-translations`.
- A `labels` setting that beats them, for an application whose people call these things
  something else.
- A readable fallback beneath both, so that an action a policy invented reads sensibly
  with nothing translated first.
- Counting in the singular or the plural as the count requires, on screen and in the
  console.

### Changed

- `Stance` no longer carries its own words; they come from the resolution chain. Its
  colours stay where they were.
- `illuminate/filesystem` is now a declared dependency, which it has been in practice
  since the policy writer arrived.

## [0.5.0] - 2026-08-10

The panel closes. Until now nothing was actually shut: Filament asks a policy, and where
there is no policy it asks nobody and lets everybody through.

### Added

- A base policy that answers from Bouncer's clipboard rather than from the Gate, and
  carries no actions of its own, so that the methods a policy file declares are exactly
  the abilities its model offers.
- `filament-bouncer:policy`, which writes one for every resource of the panel whose model
  has none, leaves alone anything already there, and lets an application keep its own stub.
- A policy for the role model, registered by the package, so that the screen handing out
  abilities is itself governed by one. An application registering its own wins.
- `AuthorizesPage` and `AuthorizesWidget`, for the components that have no policy to ask.
- A boot guard that refuses to let a panel finish booting with a page or a widget that
  authorises nobody. It throws in production too; the `ignore` list is the way to say a
  component really is open to everybody.
- `--check` now also fails on a resource whose model has no policy, which closes the loop:
  the code declares, the catalogue derives, the store is reconciled, and the guard and the
  check refuse to let the three drift apart.

### Decided

- **No bulk actions on the roles table, and no `deleteAny` in the role policy.** Filament
  authorises a bulk delete once for the whole selection, and the two refusals keeping the
  privileged role and your own role out of reach live on the resource, so a bulk delete
  would walk past both. An ability nothing asks about has no business on the grid.

### Changed

- The roles screen now takes a grant to reach, like everything else. After upgrading,
  assign somebody the privileged role before expecting to get in.

## [0.4.0] - 2026-08-10

Explicit denials, which is the reason this package exists rather than the one built on
`spatie/laravel-permission`.

### Added

- A third stance in every cell. A role now grants, says nothing, or forbids, and a denial
  beats a grant from another role and one made straight to the user.
- The same three states on the detail screen, so a role is read in the shape it is written.

### Decided

- **Forbidding is offered on exactly the abilities granting is offered on.** It is arguable
  that restricting is the smaller power and could be handed out more freely; the decision
  went the other way, because a denial nobody can lift afterwards locks people out of
  something the person setting it was never trusted with.

### Changed

- The grid's state is now a stance per cell rather than a boolean. Anything reading or
  writing that state has to be updated.

## [0.3.0] - 2026-08-10

The roles screen: subjects down the side, actions across the top.

### Added

- A Filament plugin registering a roles resource, with a list, a create screen, an edit
  screen and a detail screen that shows the same grid out of reach.
- A grid of one checkbox per subject and action, its columns grouped by scope and its group
  headings tinted, so that reading a list and deleting for good cannot look alike.
- Three refusals, each checked where the write happens and not only on the button: nobody
  hands out an ability they do not hold themselves, nobody edits a role they hold, and
  nobody edits the role that holds everything.
- A `privileged_role` setting, and a reconcile run that makes sure that role exists and
  holds the wildcard — the way back in when handing out abilities has itself been handed
  away. `--check` fails while it is missing.

### Not included

No policies: until they arrive, a panel that has none of its own still lets anybody who
reaches it manage roles. No explicit denials yet either — a cell is ticked or it is not.

## [0.2.0] - 2026-08-10

The catalogue: what the panel is able to ask about, derived from the code that asks.

### Added

- A catalogue built by walking a panel's resources, pages and widgets. A resource
  contributes one ability per method its model's policy declares; a page and a widget
  contribute one each; a model with no policy contributes nothing.
- Four scopes — reading, writing, withdrawing, irreversible — that every action is sorted
  into, so that a screen can weigh them differently.
- Configuration for the panel to walk, for models with a policy but no resource, for
  abilities no component declares, and for components to leave out.
- `filament-bouncer:reconcile`, which creates the missing abilities in a single insert,
  reports the ones the catalogue no longer declares, deletes them with `--prune`, and with
  `--check` writes nothing and fails on any difference.

### Not included

No Filament resource and no screens: roles are still edited by hand. No policies, no panel
guard, and no migration path from `spatie/laravel-permission`.

## [0.1.0] - 2026-08-10

The first published version. It is deliberately a walking skeleton: its purpose is to prove
the release pipeline end to end — repository, continuous integration, tag, Packagist, and a
`composer require` that boots inside a real Filament panel — before any feature depends on
it.

### Added

- A service provider registered through package discovery.
- A publishable configuration file covering how the roles resource presents itself in the
  panel: icon, navigation group, sort order and slug.
- A test suite on Orchestra Testbench pinning that the provider registers, that the settings
  merge under their own key, and that Bouncer's tables come up.
- Continuous integration for code style, static analysis and tests.

### Not included

No Filament resource, no screens, no permission catalogue, no synchronisation command, no
panel guard, and no migration path from `spatie/laravel-permission`. All of that is later
work.

[5.0.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v5.0.0
[4.4.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v4.4.0
[4.3.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v4.3.0
[4.2.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v4.2.0
[4.1.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v4.1.0
[4.0.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v4.0.0
[3.0.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v3.0.0
[2.1.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v2.1.0
[2.0.2]: https://github.com/elpandape/filament-bouncer/releases/tag/v2.0.2
[2.0.1]: https://github.com/elpandape/filament-bouncer/releases/tag/v2.0.1
[2.0.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v2.0.0
[1.5.1]: https://github.com/elpandape/filament-bouncer/releases/tag/v1.5.1
[1.5.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v1.5.0
[1.4.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v1.4.0
[1.3.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v1.3.0
[1.2.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v1.2.0
[1.1.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v1.1.0
[1.0.3]: https://github.com/elpandape/filament-bouncer/releases/tag/v1.0.3
[1.0.2]: https://github.com/elpandape/filament-bouncer/releases/tag/v1.0.2
[1.0.1]: https://github.com/elpandape/filament-bouncer/releases/tag/v1.0.1
[1.0.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v1.0.0
[0.6.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v0.6.0
[0.5.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v0.5.0
[0.4.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v0.4.0
[0.3.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v0.3.0
[0.2.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v0.2.0
[0.1.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v0.1.0
