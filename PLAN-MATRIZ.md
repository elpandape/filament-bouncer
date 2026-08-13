# Plan — La rejilla pasa a matriz, y El Amauta devuelve su banco de trabajo

> Este plan es lo único que ve quien lo implemente. Lo que no esté escrito aquí no existe.

## Estado

`elpandape/filament-bouncer` 5.4.0 dibuja el catálogo como **una tarjeta por sujeto con una
fila por acción**. Sobre el catálogo de El Amauta —4 sujetos × 7 acciones— eso son 28 filas
apiladas.

El Amauta construyó en paralelo, sobre un recurso propio en `/admin/security/roles-crud`, la
misma pantalla como **tabla de sujetos × acciones con la columna del sujeto fija**: 4 filas.
Se miró contra datos reales, se corrigió sobre capturas y quedó aprobada.

**Ese código es la especificación de este plan.** No hay otra referencia de diseño: los planes
anteriores del paquete se retiraron al quedar superados, y nada de lo que decían vuelve por
esta puerta. Donde este documento no llegue, se mira el código de El Amauta —los archivos
están nombrados en cada fase— y no un diseño anterior.

## Lo aprobado, que es lo que hay que reproducir

1. **Matriz con la columna del sujeto fija y desplazamiento horizontal.** Las columnas son la
   unión de las acciones que declare cualquier Policy y crecen solas; una fila cuyo sujeto se
   ha ido de la pantalla no se puede leer.
2. **Cuatro grupos en pestañas** (recursos y modelos · páginas · widgets · personalizadas), con
   la barra oculta si solo hay un grupo, y el recuento de cada pestaña resaltado en cuanto su
   grupo concede algo. Solo el primero es rejilla; los otros tres contestan una acción cada uno
   y van como lista.
3. **Una casilla por celda que recorre las tres posturas** con un clic, y al revés con Mayús.
   Vacía se abstiene, ✓ concede, ✗ prohíbe. El tick punteado es un cuarto dibujo y no una
   postura: dice que el rol contesta que sí ahí por una regla más amplia. Y el punto tenue no
   es una casilla vacía: es una acción que esa Policy no declara, que nadie puede conceder ni
   prohibir.
   - La palabra de la postura va en `aria-label` y en `title` de cada casilla. Es lo que
     sostiene la lectura para quien no ve el color, ahora que la celda es un solo control y no
     tres botones.
4. **Bajo el nombre del sujeto, su Policy.** Es lo que hace decidible la fila: sus columnas son
   los métodos de esa clase y de ninguna otra, así que quien se pregunte por qué falta una
   columna sabe qué archivo abrir.
5. **La regla acotada se dice con un punto en la esquina de la casilla**, con el texto entero
   en su `title` y su `aria-label`, y una leyenda al pie que **solo aparece si hay alguna** —una
   leyenda sobre algo que no está en pantalla enseña a no leer las leyendas—. No cabe dentro:
   la casilla mide 22 px.
6. **Un solo atajo, «Solo lectura», exclusivo.** Concede lo que el ámbito de lectura declare y
   **calla el resto de esa fila**: si sumara sin quitar sería «lectura además de lo que ya
   hubiera», que es lo contrario de lo que su nombre promete. Por fila aparece al apuntarla,
   reservando su sitio con la opacidad para que la fila no dé un salto, y visible siempre donde
   no hay puntero fino. En la esquina de la tabla va fijo y se le suma «Vaciar», que se pulsa
   una vez y no merece una copia por fila. Nada de atajos para lo que retira ni para lo
   irreversible, y «todo» y «nada» no necesitan lista porque contestan por lo que el sujeto
   declare.
7. **El atajo de la esquina alcanza solo a los sujetos de la rejilla.** Una página o un widget
   no declaran la acción que nombra, así que aplicárselo no diría nada y borraría lo que sí
   dicen.
8. **El alta es una pantalla, no un asistente.** Los tres pasos existían por el alto: componer
   un rol eran veintiocho filas apiladas y había que partirlas. La matriz cabe entera, y con
   ella el formulario vuelve a ser lo que es en la edición —identidad arriba, catálogo debajo—,
   así que las dos pantallas se leen igual y no hay dos maneras de componer lo mismo.
9. **En la ficha, la lectura en etiquetas sustituye a la rejilla deshabilitada.** Una rejilla
   que no se toca obliga a dibujar treinta casillas para responder tres cosas, y las tres que
   importan se leen igual de tenues que las que callan.

### Lo que se conserva del código actual del paquete

No porque lo dijera ningún plan, sino porque está escrito, probado y no depende de la forma de
la rejilla: el pie con las tres cifras en chips, el badge de prohibiciones —que en la matriz
cabe en la celda del sujeto, junto al nombre—, el icono por sujeto desde la clave `icons`, la
**barra resumen con guardar y cancelar dentro** —que es de la edición y no del asistente, así
que sobrevive a retirarlo—, `requiresAStance()`, el widget `RoleStats`, y en la ficha las cards
de identidad, cobertura, prohibiciones y titulares con su acción de retirar.

### Lo que se retira, y qué se lleva por delante

**El asistente de alta.** Sus tres pasos —identidad, habilidades, resumen— eran la respuesta al
alto de la lista vertical. Con la matriz, el alta es la misma pantalla que la edición.

- `requiresAStance()` **no se pierde**: hoy se le pasa al campo dentro del paso de habilidades,
  pero es una `->rule()` y vale igual en una pantalla sola. Sigue impidiendo crear un rol que no
  dice nada de nada, mientras que editar sí permite vaciarlo, que es como se retira lo que
  tiene.
- Las explicaciones de cada paso pasan a las descripciones de las dos secciones.
- **Lo que sí desaparece es el paso de resumen**, el que leía la elección de vuelta sujeto por
  sujeto antes de escribir. Es lo único del asistente con valor propio, y se acepta perderlo: la
  matriz enseña de una vez lo que la lista obligaba a recorrer, y la ficha del rol da esa misma
  lectura en cuanto está creado. Queda escrito aquí para que no se lea como un olvido.

---

## FASE 1 · El campo

### Commands

Ninguno. `make:filament-form-field` genera en `app/`, y esto vive en un paquete. Los archivos
se crean a mano.

Docs de referencia: https://filamentphp.com/docs/5.x/forms/custom-fields

### Modify · `src/Filament/Forms/AbilityGrid.php`

Namespace: `ElPandaPe\FilamentBouncer\Filament\Forms\AbilityGrid`, extiende
`Filament\Forms\Components\Field`.

**Se conserva sin tocar** (no dependen de la forma): `catalog()`, `requiresAStance()`,
`notes()`, `submitsFromSummary()`, `doesSubmitFromSummary()`, `getSummaryCancelUrl()`,
`getStances()`, `getNeutral()`, `getEmptyLabel()`, `isEmptyCatalog()`, `getNotes()`,
`getBroader()`, `savedStances()`, `neutralState()`, `recordOrNull()`.

**Se reescribe** `getSections()`. Firma y forma exacta de retorno:

```php
/**
 * @return array<string, array{
 *   label: string,
 *   grid: bool,
 *   rows: list<array{
 *     key: string,
 *     label: string,
 *     class: string|null,
 *     policy: string|null,
 *     icon: string|null,
 *     action: string|null,
 *     cells: array<string, bool>,
 *   }>,
 * }>
 */
public function getSections(): array
```

- Clave del array: `CatalogTab::value`.
- `grid`: `$tab->isGrid()`.
- `cells`: `array_fill_keys(array_keys($subject->cells()), true)` — qué acciones declara ese
  sujeto. La vista lo usa para distinguir casilla de hueco.
- `action`: `null` en la pestaña de rejilla; `array_key_first($subject->cells())` en las
  demás, porque una puerta tiene una sola.
- `policy`: `Gate::getPolicyFor($subject->entityType)` reducido con `class_basename()`, o
  `null` cuando el sujeto no tiene modelo detrás.
- `class`: `$subject->entityType`. Se conserva la clave, aunque la matriz dibuje la Policy: el
  dato ya se calcula y una vista que quiera nombrar el modelo no debería volver a derivarlo.

**Se añaden** tres métodos públicos. La vista los llama como `$getX()`.

```php
/**
 * Las columnas de la rejilla, en el orden en que se leen.
 *
 * @return array{
 *   manage: array{action: string, label: string},
 *   groups: list<array{scope: string, label: string, actions: list<array{action: string, label: string}>}>,
 * }
 */
public function getColumnGroups(): array
```

> **El método se llama `getColumnGroups()`, no `getColumns()`.** El segundo choca con
> `Filament\Schemas\Components\Component::getColumns(?string $breakpoint = null)`: es un fatal
> al cargar la clase y se lleva por delante el panel entero, no solo el campo. La misma trampa
> existe con `name()`, que ya declara `Filament\Infolists\Components\Entry`. Antes de bautizar
> un método en una clase que extiende de Filament, mirar la base.

- Las columnas salen **solo de los sujetos que van en rejilla**. Sin ese filtro, la acción
  única de una página abre una columna que todas las filas responderían con un hueco.
- `manage` es `Ability::MANAGE_ACTION` con su etiqueta de `Labels::action()`.
- Los grupos salen de `$this->catalog->actions` (ya ordenados por ámbito y luego por nombre),
  agrupados por `AbilityScope`, con la etiqueta del ámbito de `Labels`.

```php
/**
 * El atajo que ofrece la fila de un sujeto.
 *
 * Solo el de lectura. «Todo» y «nada» no necesitan lista porque contestan por lo que el
 * sujeto declare, y un atajo para lo que retira o para lo irreversible es un atajo que
 * nadie debería tener a mano.
 *
 * @return list<array{key: string, label: string, actions: list<string>}>
 */
public function getPresets(): array
```

Devuelve `[]` si ninguna acción de la rejilla es de ámbito `AbilityScope::Read`.

```php
/**
 * A qué sujetos alcanza un atajo pulsado desde la esquina de la tabla.
 *
 * @return list<string>
 */
public function getGriddedSubjects(): array
```

```php
public function getClearLabel(): string;   // 'filament-bouncer::roles.grid.clear'
public function getNoteLegend(): string;   // 'filament-bouncer::roles.grid.note_legend'
public function getSubjectLabel(): string; // 'filament-bouncer::roles.grid.subject'
```

**Se retira**: `getOpenByDefault()`, `getCollapseLabel()` y la constante `OPEN_UP_TO`. El
plegado por sección era la respuesta al alto de una lista vertical; una tabla de una fila por
sujeto no lo necesita. Los tests `a catalogue small enough to read opens itself` y
`a catalogue too long to open at once arrives folded` se borran.

**Se retira también el control de tres segmentos** (`fb-seg`, sus tres botones y su CSS). Siete
columnas por tres segmentos son veintiún controles por fila: la píldora sólo cabía porque cada
acción tenía una fila entera para ella. Con ella se va el test `each stance is its own button,
and its word is its accessible name`, y lo que ese test protegía —que cada postura se anuncie
con su palabra— pasa al `aria-label` de la casilla, con un test propio que lo fije.

---

## FASE 2 · La vista y la hoja

### New · `resources/views/forms/ability-matrix.blade.php`

Sustituye a `ability-grid.blade.php`, que se borra. Envuelve con:

```blade
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
```

Estructura, de fuera adentro:

1. Si `$isEmptyCatalog()`, un `<p class="fb-empty">{{ $getEmptyLabel() }}</p>` y nada más.
2. `<div x-data="{ … }">` con el estado. **El estado va en un parcial aparte**
   (`ability-alpine.blade.php`), no repetido: duplicarlo es cómo dos copias acaban
   comportándose distinto. Verbos: `at`, `set`, `cycle`, `says`, `inherits`, `apply`,
   `clear`, `grantedIn`, `tally`.
   - **Ojo con Blade y Alpine**: nunca componer una expresión de Alpine concatenando con el
     punto de PHP alrededor de un `@js(...)`. El punto sale tal cual al atributo y el
     navegador lee el resto como JavaScript roto, una vez por celda. Componer la cadena en
     PHP antes, en una variable, y meterla entera con un solo `@js(...)`. Ningún test de
     servidor lo detecta; solo `assertNoJavaScriptErrors()`.
3. Barra de pestañas, `role="tablist"`, con `hidden` si `count($sections) < 2`. Cada pestaña
   lleva su recuento (`x-text="tally(keys)"`) resaltado en cuanto el grupo concede algo: el
   formulario escribe los cuatro grupos de un clic, así que sin el resalte un rol que alcanza
   una página peligrosa se lee inofensivo desde la pestaña abierta.
4. Un `role="tabpanel"` por sección, con `x-show` y `x-cloak`.
5. Si `$section['grid']`, la tabla; si no, la lista de puertas.

`apply` y `clear` en Alpine:

```js
apply(subject, actions, offered) {
    for (const action of offered) {
        this.set(subject, action, actions.includes(action) ? 'granted' : @js($getNeutral()))
    }
},
clear(subject, offered) {
    this.apply(subject, [], offered)
},
```

### New · `resources/views/forms/ability-matrix-section.blade.php`

- `<th class="fb-corner" rowspan="2">`: la palabra del sujeto y, si no está deshabilitado, los
  atajos globales — uno por preset más «Vaciar» —, cada uno recorriendo
  `$getGriddedSubjects()`.
- Cabecera en dos filas: una de grupos de ámbito (`colspan` = nº de acciones) y otra de
  acciones, con la etiqueta arriba y el identificador en `<code>` debajo.
- `<th class="fb-subject">` por fila: icono, nombre, `<code>` con la Policy, y
  los atajos de fila **a la derecha del nombre y en la misma línea**, no debajo.
- Una celda por columna, incluida la de `manage`.

### New · `resources/views/forms/ability-cell.blade.php`

- Si la acción no está en `$row['cells']`: `<td>` con un punto y un `<span class="fb-sr">` que
  diga que no está declarada. **Hueco y casilla vacía no se parecen**: la casilla es un rol
  que se abstiene, el punto es una acción que su Policy no declara.
- Si está: un `<button type="button">` con `data-subject` y `data-action` (los tests de
  navegador los usan como selector), `x-on:click="cycle(...)"`, `x-bind:class` con
  `fb-box-{postura}` más `fb-box-broader` cuando herede, y `title`/`aria-label` compuestos en
  PHP.
- Si la celda tiene nota (`$getNotes()[$key][$action]`), un `<span class="fb-narrowed">` dentro
  del botón y el texto de la nota añadido al `title` y al `aria-label`.

### Modify · `resources/css/filament-bouncer.css`

Tres piezas que se sostienen juntas — quitar cualquiera rompe el efecto de una forma distinta:

```css
.fb-scroll { overflow-x: auto; overscroll-behavior-x: contain; }
.fb-table  { border-collapse: separate; border-spacing: 0; }
.fb-subject, .fb-corner { position: sticky; inset-inline-start: 0; z-index: 2; }
```

- Sin el `overflow-x` en un antecesor, `position: sticky` no tiene contra qué pegarse.
- Con `border-collapse: collapse` los bordes los asigna la tabla y la celda pegada los pierde
  justo al desplazarse. De ahí `separate` y bordes por celda.
- `.fb-box { position: relative }` para que el punto de la nota se ancle.
- Los atajos de fila: `opacity: 0`, a `1` con `:hover` de la fila y `:focus-within`; y
  `@media (hover: none) { opacity: 1 }`, porque en táctil no hay dónde apuntar.
- **Todo contra los tokens de color de Filament** (`--gray-*`, `--success-*`, `--danger-*`,
  `--info-*`, `--warning-*`), nunca utilidades de Tailwind: el panel sirve su hoja compilada
  fuera de Vite y una clase que solo usara este archivo no existiría en ella. Los dos temas se
  declaran mirando `:is(.dark)`, que es lo que Filament estampa para la elección explícita y
  para la del sistema.

---

## FASE 3 · La ficha del rol

### Modify · `src/Filament/Resources/Roles/Pages/ViewRole.php`

- **Se retira** la sección con la rejilla deshabilitada: la sustituye `AbilityTags`.
- **Se conservan** identidad, barra de cobertura, prohibiciones y titulares, con su acción de
  retirar y la salvaguarda del último titular del privilegiado.
- **Se añaden** dos entradas.

### New · `src/Filament/Filament/Infolists/AbilityTags.php`

Namespace: `ElPandaPe\FilamentBouncer\Filament\Infolists\AbilityTags`, extiende
`Filament\Infolists\Components\Entry`.
Docs: https://filamentphp.com/docs/5.x/infolists/custom-entries

Dice lo que el rol concede y prohíbe **y nada más**: una fila por sujeto con las acciones que
tienen postura, verdes las concedidas y rojas las prohibidas. Tres bloques:

1. **Sujetos**, con su clase o Policy debajo del nombre.
2. **Puertas** (páginas, widgets, personalizadas) en su propio bloque: contestan una acción que
   ningún otro sujeto declara, y compartir listado las pintaba enteras de guiones —diciendo
   que la habilidad no existe sobre una que existía y estaba concedida—.
3. **Acotadas**, con **el registro nombrado, no contado**: «sobre 1 registro» no dice cuál, y
   con eso no se puede ni revisar la regla ni retirarla. El nombre se le pide a
   `Filament\Facades\Filament::getModelResource($class)` y, si lo hay,
   `$resource::getRecordTitle($record)` — que devuelve `string|Htmlable|null`. Si el registro
   ya no existe se dice: la regla sigue apuntando a la nada y ninguna otra pantalla lo delata.

Métodos públicos: `getRows()`, `getDoors()`, `getNarrowed()`, `getSilentGroups()`,
`getSilent()`, `spellsSilent()`, `getSilentLabel()`, `getSilentMoreLabel()`,
`getEmptyLabel()`, `getStanceLabels()`, `getStances()`, `gridRows()`.

**El pie que nombra lo que el rol calla se pliega.** Enumerarlo crece con lo que el panel
declara y no con lo que el rol hace —y más cuanto menos dice el rol: con treinta modelos, uno
de solo lectura acabaría con sesenta nombres—. Por encima de **seis** pasa a «No dice nada
sobre otros N sujetos», con la lista repartida por grupo dentro de un `<details>`. Nada de
Alpine: una ficha que no se toca no necesita estado.

Las filas se alinean en **rejilla de dos columnas** —sujeto a ancho fijo (14rem), etiquetas
contra el borde derecho—, no en una fila que envuelve: con `space-between`, en cuanto las
etiquetas no caben saltan enteras abajo y arrancan pegadas a la izquierda, y cada sujeto
empieza en un sitio distinto. Bajo 56rem se apila.

### New · `src/Filament/Filament/Infolists/OrphanChips.php`

Lo que el rol perderá en el próximo sync, en la columna estrecha junto a los metadatos: un
aviso no necesita el ancho del contenido, necesita estar donde se ve.

- **Cuenta solo las condenadas** (`Declaration::of($row)->isDoomed()`). `Declaration` tiene
  tres estados; enseñarlos los tres pone bajo la misma alarma las reglas acotadas, que están
  sanas, que `--prune` no toca y que el bloque de acotadas ya nombra una por una.
- **Dice que no hay nada que perder cuando no lo hay**: un espacio en blanco se lee como que
  nadie ha mirado.
- Cada regla es un chip con **el identificador crudo y nada más**: su etiqueta legible saldría
  de humanizar el nombre —nadie tradujo una acción que dejó de existir— y ese título que nadie
  escribió se lee como si alguien lo hubiera escrito.
- Agrupa por el sujeto al que apunta, buscando su etiqueta en el catálogo aunque la acción ya
  no esté en él, y con un grupo propio para las que no apuntan a ninguno.

### New · `src/Filament/Filament/Infolists/Concerns/ReadsStances.php` y `ReadsDoomedRules.php`

Lo que se lee del almacén se lee **una vez**. Dos entradas que consulten el mismo rol por su
cuenta son dos sitios donde una postura puede salir distinta, y ninguna podría desmentir a la
otra.

### Nota de implementación

El pivote se une a mano y no por la relación: el modelo de rol es el que la aplicación haya
configurado y nada le promete al analizador que lleve los traits de Bouncer. Las dos tablas
tienen columna `entity_id` y significan cosas distintas —una es el rol, la otra el registro
acotado—, así que **van prefijadas**. Y el closure de un `where` agrupado sobre
`Models::ability()->newQuery()` recibe `Illuminate\Database\Eloquent\Builder`, no el de Query:
copiar el type hint de un código que parte de `DB::table()` da un `TypeError` que el análisis
estático no ve.

---

## FASE 4 · Vocabulario

`lang/en/roles.php` y `lang/es/roles.php`, bajo la clave `grid`:

| Clave | en | es |
|---|---|---|
| `subject` | `Subject` | `Sujeto` |
| `clear` | `Clear` | `Vaciar` |
| `preset_read` | `Read only` | `Solo lectura` |
| `note_legend` | (leyenda del punto) | (leyenda del punto) |
| `hint` | (las tres posturas y el Mayús) | ídem |

Y bajo `record`, las de la ficha: `tags_heading`, `tags_empty`, `narrowed_heading`,
`narrowed_note`, `owned`, `record_gone`, `silent_spelled`, `silent_counted`, `silent_more`,
`orphans_heading`, `orphans_headline_none`, `orphans_headline_some`, `orphans_note_none`,
`orphans_note_some`.

**Ninguna cadena en el código.** Se retiran las claves que dejen de leerse en alguna pantalla:
una traducción que nadie lee es una que nadie mantiene.

---

## FASE 5 · Tests

Docs: https://filamentphp.com/docs/5.x/testing/overview

### `tests/Filament/AbilityGridTest.php` — reescribir 30

**Se borran** (hablan de una forma que deja de existir): `a subject is laid out as rows, and
every action it declares gets one` · `the catalogue is drawn as folded sections with a preset
each` · `a catalogue small enough to read opens itself` · `a catalogue too long to open at once
arrives folded` · `the presets arrive as a closed menu` · `the note of a denial is painted in
red, on its row` · `the note of anything but a denial keeps its amber`.

**Se reescriben apuntando a la tabla**: `the grant covering a whole model comes first, under a
key of its own` · `a door is not laid out as a grid` · `a door is drawn as a line, not as a
fold` · `a section carries the name its tab is called by` · `a denial marks its row and a grant
does not` · `each stance is its own button, and its word is its accessible name` · `a row
reached by a broader rule draws the answer, not the dash` · `a row nothing reaches draws no
hollow tick` · `what a row says beyond its stance is read off the record`.

**Se conservan intactos** (no miran la forma): `reading only is not everything` · `the state
the grid fills is the shape the store reads` · `the grid arrives holding what the role was
granted` · `a panel that declares nothing says so instead of drawing an empty grid` · `what it
all adds up to stays in sight` · `the summary of a grid nobody flagged carries no buttons` ·
`the badge of denials is in the markup for alpine to count` · `the footer wraps its three
figures in chips` · `a role holding nothing has no row reached by anything broader` · `the
weight mark the approved design retired is gone from the rows`.

**Se añaden**:

- `the columns come only from the subjects the grid draws` — con una habilidad personalizada
  configurada, `use` no abre columna. **Romper a propósito** quitando el filtro: debe fallar.
- `a cell holding a narrowed rule is marked, and one without it is not`
- `the legend of that mark stays off a role holding no such rule`
- `a shortcut grants what it names and silences the rest of the row`
- `the corner shortcut aims at the gridded subjects and no others`

### `tests/Filament/EditRoleTest.php` — 15

Se reescriben los tres que leen el marcado de una fila (`a row says when …`). Los doce
restantes no miran la forma. **`a role the editor holds refuses to open` y `the way back in
refuses to open, whoever is asking` no se tocan**: son las dos cerraduras y siguen exactamente
igual.

### `tests/Filament/CreateRoleTest.php` — 21

**Se borran los siete del asistente**: `it is composed one question at a time, ending on what is
about to be written` · `the screen says up front that nothing is saved until the end` · `the
identity step explains the two names and hints each field` · `the wizard is marked for the
stylesheet that seats its footer` · `the abilities step heads itself and carries the grid` ·
`the last step reads back the choice, subject by subject` · `a subject nobody said anything
about still gets its line`.

**Se conservan los catorce restantes**, que prueban el alta y no la forma de recorrerla — entre
ellos los dos de la refusal (`a role saying nothing about anything is refused`, `one stance is
enough to get past the refusal`), los dos del nombre reservado y el de que quien no tiene nada
reparte igual. Solo hay que quitarles el paso por los pasos del asistente donde lo tengan.

Con los tests se van `getSteps()`, `getWizardComponent()`, `reviewComponent()`,
`resources/views/roles/review.blade.php`, las claves `wizard.*` de los dos idiomas y el CSS del
pie del asistente.

### `tests/Filament/ViewRoleTest.php` — 18

Se borran los que leen la rejilla deshabilitada (`the record is read in the shape it is written
in, filled and out of reach`, `a reading page offers no save inside the summary bar`). Se
añaden, sobre las dos entradas nuevas:

- `the sheet says only what the role configured`
- `a door is read apart from the subjects`
- `a narrowed rule names the record it reaches`
- `a narrowed rule whose record is gone says so`
- `what the role says nothing about is counted once it stops being worth naming`
- `only the rules the next sync would take away are counted`
- `a role with nothing to lose says so`

### Navegador

El paquete no tiene suite de navegador. **Lo que ningún test de servidor ve**: una expresión de
Alpine rota por concatenación de PHP, y una hoja que no se aplica. Antes de publicar, montar la
pantalla en El Amauta contra la copia local del paquete y comprobar a mano: las tres posturas
al clic, el Mayús al revés, la columna pegada al desplazar, el atajo de fila al apuntar, y la
consola sin errores.

### Umbrales

Los mismos de siempre: cobertura de líneas y de tipos al 100 %, sin profanidad, PHPStan al
nivel `max` sin baseline y sin `@phpstan-ignore`, Rector sin cambios pendientes, y Pint al
final. Ninguno se relaja para dar esto por terminado.

---

## FASE 6 · Publicación

1. `CHANGELOG.md` con el formato de la casa, versión **6.0.0**. Es un mayor: la forma de la
   pantalla cambia entera, `getSections()` cambia de forma de retorno y desaparecen tres
   métodos públicos del campo. Los nombres bajo los que se guardan las habilidades **no
   cambian**, así que ninguna fila de nadie se mueve — decirlo en el changelog.
2. `README.md`: la sección de la pantalla de roles.
3. Tag `v6.0.0`, push, release, Packagist.

---

## FASE Z · El Amauta devuelve el banco de trabajo

**Va después de publicar y de subir la dependencia.** Antes no: el panel se quedaría sin
pantalla de roles entre medias.

### Z1 · Subir la dependencia

`vendor/bin/sail composer require elpandape/filament-bouncer:^6.0` y
`vendor/bin/sail artisan filament:assets`.

### Z2 · Borrar el recurso propio

```
app/Filament/Resources/Security/Roles/           (RoleResource, Pages/, Schemas/, Tables/, Concerns/)
app/Filament/Forms/AbilityMatrix.php
app/Filament/Infolists/                          (Entry, AbilityTags, OrphanChips, Concerns/)
resources/views/filament/forms/                  (las cinco vistas de la matriz)
resources/views/filament/infolists/              (las cuatro vistas de la ficha)
resources/css/filament/ability-matrix.css
resources/css/filament/role-entries.css
tests/Feature/Filament/Resources/Security/Roles/ (los cinco archivos)
```

Y de `AdminPanelServiceProvider`, las dos líneas `Css::make(...)`.

### Z3 · Borrar lo temporal

Se sembraron para ver las pestañas de páginas, widgets y personalizadas con algo dentro:

```
app/Filament/Pages/Bitacora.php
resources/views/filament/pages/bitacora.blade.php
app/Filament/Widgets/Pulso.php
tests/Feature/Filament/Panel/PulsoTest.php
```

Y la clave `custom` de `config/filament-bouncer.php` vuelve a quedar vacía.

**Después: `vendor/bin/sail artisan filament-bouncer:reconcile --prune`.** Sin eso quedan en la
tienda cuatro habilidades que ya no declara nadie, y las concesiones que las apuntaban.

### Z4 · Decidir sobre el seeder

`database/seeders/RoleShowcaseSeeder.php` siembra siete roles para mirar las pantallas con algo
dentro. No es del banco de trabajo y sigue sirviendo para las pantallas del paquete.
**Recomendación: se queda**, con su docblock ajustado a que las pantallas ahora son las del
paquete.

### Z5 · Verificar

- `vendor/bin/sail composer test:all` — las cinco puertas.
- `vendor/bin/sail bun run build && vendor/bin/sail composer test:browser`. **Obligatorio**:
  `tests/Browser/FilamentPanelTest.php` visita la pantalla de roles y sus tests están fuera de
  `test:all`, así que ninguna de las cinco puertas mira ahí. Su test de la matriz apunta a
  `RoleResource::getUrl(...)` del recurso que se borra: hay que repuntarlo al del paquete.
- Comprobar a mano que `/admin/security/roles-crud` da 404 y que `/admin/security/roles` sirve
  la pantalla nueva.

### Z6 · AGENTS.md

- **§6.11** (la matriz de habilidades) y **§6.12** (las entradas de la ficha) se borran enteras:
  describen código que deja de existir aquí. Lo que cuentan —el porqué de cada decisión— viaja
  con el código al paquete y tiene que estar en su documentación antes de borrarlo de aquí.
- **§1**: retirar la mención al segundo recurso sobre `Role` y ajustar el recuento de tests.
- **§5**: quitar `Filament/Forms/`, `Filament/Infolists/`, `views/filament/` y `css/filament/`
  del árbol.
- **§9**: **se conservan** las filas de la trampa del `DeleteBulkAction`, la del punto de PHP
  dentro de Alpine, la de los assets del panel y la de la primera columna que se despega. No
  describen este recurso: describen a Filament, y valen para el siguiente componente propio.

---

## Verificación del plan

Pasado por `checklist.md`:

- Namespaces completos en cada clase nombrada ✓
- URLs de documentación puestas y no sustituidas por «buscar X» ✓
- Sin comandos de andamiaje inventados: en un paquete no aplican, y se dice por qué ✓
- Autorización: **no cambia nada**. Las Policies, el guardián de arranque, las dos negativas de
  `canEdit`/`canDelete` y la salvaguarda del último titular del privilegiado se conservan tal
  cual. Este plan toca la forma de la pantalla, no quién entra ✓
- Tests nombrados uno a uno, con los que se borran, los que se reescriben y los que no se tocan ✓
- Columnas del formulario: la rejilla va en una sección a `columnSpanFull()`, sin anidar
  columnas ✓
