# UI nueva (propuesta C) — se demuele la capa de pantalla y se construye de cero

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Borrar entera la capa de UI del paquete —recursos, formularios, vistas, CSS y sus tests— y escribir en su lugar la propuesta C desde cero, sin arrastrar ni una decisión de la anterior.

**Architecture:** Se corta por la línea que separa la pantalla del lazo cerrado. El lazo —catálogo, tienda, políticas, comandos y el guardián del panel— **no se toca**: es lo que distingue a este paquete y lo que la UI nueva consume. Todo lo que dibuja se tira y se vuelve a escribir: secciones plegables por sujeto con un segmentado de tres estados por acción, tablas con cobertura, asistentes por pasos y fichas nuevas.

**Tech Stack:** PHP 8.5 · Laravel 13 · Filament 5.7 · silber/bouncer 1.0.4 · Alpine.js (el que trae Filament) · Pest 4 · PHPStan max · Rector · Pint.

**SemVer: esto es `5.0.0`.** Desaparecen clases públicas (`AbilityGrid`, `AbilityHolders`, los schemas y las tablas), así que es un mayor sin discusión. Se trabaja en una rama, no en `main`.

---

## Global Constraints

Valen para **todas** las tareas y no se repiten en cada una.

- **Commits en inglés**, formato `type(scope): short description`, ≤50 caracteres, imperativo, sin punto final. El scope nombra dónde vive el cambio y no se inventa. Cuerpo con bullets de qué y por qué, sin nombres de archivo ni comillas invertidas.
- **Prohibido el trailer `Co-Authored-By`** en cualquier commit de este repositorio.
- **`PLAN-UI-C.md` se queda sin rastrear.** No se commitea y **no se añade al `.gitignore`**.
- **`composer test:all` son seis puertas**, todas obligatorias: Pint, cobertura de líneas al 100 %, cobertura de tipos al 100 %, profanidad, PHPStan al nivel máximo y Rector en seco.
- **Toda cadena que lea una persona sale de `__()`**, declarada en `lang/en` **y** `lang/es` en el mismo commit. Ningún test comprueba la paridad de idiomas. Los mensajes de excepción van en inglés y sin traducir: los lee quien mira una traza.
- **El CSS no usa utilidades de Tailwind** —el build de la aplicación consumidora no compilaría una clase que solo usa este archivo— y todo sale de los tokens que el panel ya define: `--gray-*`, `--info-*`, `--success-*`, `--danger-*`, `--warning-*`, `--primary-*`, `--radius-*`.
- **El tema oscuro se conmuta solo con `:is(.dark)`**, nunca con `prefers-color-scheme`: Filament estampa `dark` en la raíz tanto para una elección explícita como para una del sistema, y una media query oscurecería la pantalla dentro de una página que alguien dejó clara a propósito.
- **La paleta del mockup se traduce a tokens.** Ningún hex del HTML de la propuesta entra en este CSS.
- **No se toca ni un nombre de habilidad.** Son filas en la base de datos de quien usa el paquete: cambiar cómo se escribe una tiraría en silencio cada concesión que apuntaba a ella. Esta es la única parte de la API pública que un mayor **tampoco** puede romper.

---

## Lo que la demolición NO se lleva

Siete señas, del README y de la memoria del proyecto. Ninguna vive en la capa que se borra, y por eso la demolición es segura. Una tarea que parezca exigir tocarlas está mal planteada: se para y se pregunta.

1. **El catálogo se deriva de lo que declara la Policy**, jamás de una lista de acciones inventada.
2. **Las habilidades se guardan por modelo**, no como cadenas.
3. **Quien puede editar roles reparte todo lo que el panel declara**, incluido el comodín e incluido a sí mismo.
4. **El panel no arranca** con una página o un widget que no decida quién entra.
5. **`reconcile --check` pone en rojo** un build cuya tienda haya divergido del catálogo.
6. **Hay vía de vuelta**: rol privilegiado restaurado en cada reconcile, más `filament-bouncer:assign`.
7. **Una denegación es un estado, no una ausencia**, y gana a cualquier concesión que llegue por otro lado.

---

## La línea de corte

### Se borra (≈ 2.000 líneas)

```
src/Filament/Resources/            todo: Roles/ y Abilities/, con sus Pages, Schemas y Tables
src/Filament/Forms/                AbilityGrid, AbilityHolders, RolesField
src/Filament/RelationManagers/     RolesRelationManager
src/Filament/Concerns/FillsRoleAbilities.php
src/Filament/Concerns/SavesRoleAbilities.php
src/Filament/Concerns/FillsAbilityHolders.php
resources/views/forms/             los tres blades
resources/css/filament-bouncer.css
tests/Filament/                    los seis archivos de test de pantalla
tests/RolesFieldTest.php
```

### Se queda intacto — es el lazo, no la pantalla

```
src/Catalog/      src/Store/      src/Policies/     src/Console/
src/Support/      src/Contracts/  src/Exceptions/
src/Filament/PanelGuard.php                  seña nº 4, el guardián
src/Filament/ComponentAccess.php             lo que el guardián consulta
src/Filament/Concerns/AuthorizesPage.php     API pública: la usan las aplicaciones
src/Filament/Concerns/AuthorizesWidget.php   ídem
config/           stubs/
lang/en/actions.php  lang/es/actions.php     vocabulario del dominio
lang/en/scopes.php   lang/es/scopes.php      ídem
lang/en/stances.php  lang/es/stances.php     ídem
lang/en/console.php  lang/es/console.php     ídem
tests/  (todo lo que no sea de pantalla: catálogo, tienda, comandos, guardián, ownership…)
```

### Se ajusta, no se borra

- `src/Filament/FilamentBouncerPlugin.php` — `FilamentBouncerPlugin::make()` está en el README y en el `AdminPanelServiceProvider` de El Amauta. Se le cambian los recursos que registra; el nombre de clase y el método no.
- `src/FilamentBouncerServiceProvider.php` — sigue registrando traducciones, vistas, el CSS, las dos Policies de vendor y los dos `ownedVia`. Solo cambia lo que apunte a clases borradas.
- `lang/{en,es}/roles.php` y `lang/{en,es}/abilities.php` — se vacían de claves de pantalla y se rellenan con las de la UI nueva. Las de dominio que otros tests asertan literalmente (`declared_yes`, `declared_no`, `declared_apart`, `broader_short`, `owned_suffix`, `record_suffix`) se conservan.

### Dos nombres de clase que se reconstruyen con el mismo nombre

`RolesField` y `RolesRelationManager` los consume el `UserResource` de El Amauta. Se borran y se reescriben de cero, **pero conservando nombre y namespace**, para que la aplicación no tenga que cambiar una línea. Es lo único que la demolición conserva de la capa vieja, y se conserva el nombre, no el código.

---

## Las 66 garantías que la UI nueva tiene que volver a demostrar

Los tests que se borran encierran garantías que no están escritas en ningún otro sitio. Esta es la lista de aceptación de la reconstrucción: **una pantalla no está terminada hasta que las suyas vuelven a estar probadas**. Las marcadas 🔒 son de seguridad — si alguna se pierde, el paquete deja de cerrar el panel y la pérdida no se ve en ninguna captura.

### Rejilla de roles — alta (10)
Ofrece las habilidades que quien rellena tiene · ofrece **todo lo que el panel declara, se tenga o no** 🔒 · crear concede exactamente lo marcado · descarta una celda que el panel no declara 🔒 · exige nombre · rechaza un nombre ya tomado · quien no tiene nada reparte igualmente 🔒 · un sujeto que no responde a una acción deja ese hueco vacío · páginas, widgets y habilidades de configuración se ofrecen como listas en pestañas propias · un panel que no declara nada lo dice en vez de dibujar una rejilla vacía.

### Rejilla de roles — edición (17)
Llega con lo que el rol tiene · guardar concede lo marcado y retira lo desmarcado · **una concesión que el editor no ve sobrevive a un guardado que no la menciona** 🔒 · descarta una celda no declarada 🔒 · **rechaza abrir un rol que el editor tiene** 🔒 · **rechaza abrir el rol que lo tiene todo** 🔒 · un rol corriente se abre · una celda puede prohibir, y la prohibición es lo que el rol lleva 🔒 · la pantalla de un rol que nadie puede borrar no ofrece cómo · dice cuándo el rol llega a la habilidad por una regla ajena · una celda alcanzada por una regla más amplia dibuja la respuesta, no el guion · una celda que nada alcanza dibuja el guion · dice cuándo una prohibición ajena vence la concesión hecha aquí · dice cuándo el rol tiene reglas que la rejilla no puede escribir · cada postura se lee como palabra accesible · una concesión sobre el modelo entero se escribe como comodín · no crece una fila que ningún sujeto podría llenar.

### Ficha del rol (3)
Muestra lo mismo, lleno y fuera de alcance · muestra una prohibición como prohibición 🔒 · la de un rol que nadie puede editar no ofrece entrada.

### Listado de roles (6)
Lista los roles · **la fila del rol que lo tiene todo no ofrece editar ni borrar** 🔒 · las acciones llevan a las páginas y no abren el formulario en modal · toma de configuración cómo se presenta · **la pantalla está gobernada por una habilidad como todo lo demás** 🔒 · la propia pantalla es una fila de la rejilla que dibuja.

### Habilidades (19)
Acotar una regla exige concesión propia 🔒 · compone una regla solo para lo propio · compone una regla para un único registro · **una regla que no acota nada se rechaza** · una segunda fila que dice lo mismo se rechaza · **el nombre sale del catálogo y no de la columna donde se eligió** 🔒 · un par que el catálogo no resuelve se rechaza y no se guarda 🔒 · una regla acotada se reparte como ella misma y deja en paz a la llana · se puede retirar, y lo dice al hacerlo · el listado distingue una fila acotada de una que nadie declara, y dice quién la tiene · la pantalla exige concesión propia 🔒 · el listado muestra sobre qué puede decidir quien lee · el título es el único campo que escribe, y el nombre no 🔒 · una postura puesta desde aquí es la misma fila que escribe la pantalla de roles · una fila que el código ya no declara no lleva celdas · toma su sitio en el panel de la configuración · el listado dice quién la tiene, cómo, y cuáles ya no declara el código · un formulario sin registro no le pide nada al catálogo · un rol borrado con la pantalla abierta se pasa por alto y no resucita.

### Roles de una cuenta — pestaña y campo (11)
Lista los roles de la cuenta · asignar escribe y se lee dentro de la misma petición 🔒 · retirar también escribe · **el rol privilegiado no se ofrece a quien no lo tiene** 🔒 · sí lo ofrece quien lo tiene 🔒 · **al último titular no se lo quita nadie** 🔒 · el campo ofrece todos los roles · ofrece el privilegiado deshabilitado a quien no lo tiene 🔒 · **una petición que nombra un rol que la pantalla no ofrecería se rechaza** 🔒 · quien tiene el privilegiado lo pasa · nada marcado no escribe nada.

---

## Decisiones donde la propuesta C choca con el paquete

El mockup se diseñó **a propósito sin mirar el paquete**. Siete choques; el mockup manda en lo visual, el paquete en el dominio.

- **D1 — Fuera el botón de eliminar una habilidad.** Una fila desaparece cuando la reconciliación deja de declararla, y `--prune` dice cuántas se llevó. El botón ofrecería llevarse cada concesión que apuntaba a ella en un clic. En su lugar, el estado de reconciliación. *(Fase 3.)*
- **D2 — Fuera la autoría y la actividad reciente.** No hay `updated_by` ni auditoría. *(Decidido por el usuario.)*
- **D3 — El scope de cada acción sigue viéndose.** Las bandas tintadas impedían que «ver un listado» y «borrar para siempre» parecieran la misma decisión. Pasa a marca por fila. *(Fase 1.)*
- **D4 — El estado de reconciliación se queda en el listado de habilidades**, además de la agrupación por modelo que trae C. *(Fase 3.)*
- **D5 — Sin acciones en lote en la tabla de roles.** `RolePolicy` no declara `deleteAny` a propósito: Filament autoriza el lote una sola vez y se saltaría las dos salvaguardas. C ya no las lleva; queda escrito para que nadie las añada.
- **D6 — La ficha del rol se lee en la misma forma en que se cambia.** C proponía chips; gana el principio: la ficha renderiza las secciones nuevas deshabilitadas. La barra de cobertura sí se añade. *(Fase 2.)*
- **D7 — «Tipo: concesión / prohibición» es falso sobre una habilidad.** La fila es la cosa; conceder o prohibir vive en el pivote `permissions.forbidden`, **por titular**, y la misma fila puede estar concedida a un rol y prohibida a otro. Se convierte en marca por titular dentro de «Impacto actual». *(Fase 3.)*

---

## Task 1: Cortar la capa de pantalla

**Files:**
- Delete: los quince caminos de «Se borra», arriba.
- Modify: `src/Filament/FilamentBouncerPlugin.php`, `src/FilamentBouncerServiceProvider.php`.

**Interfaces:**
- Produces: un paquete sin UI cuya suite pasa entera. Es el suelo desde el que construyen todas las tareas siguientes.

Un commit que deja el paquete sin pantallas. Es deliberado: mezclar la demolición con la primera pieza nueva hace ilegible el diff de las dos.

- [ ] **Step 1: Rama propia**

```bash
cd /home/b760m/code/elpandape/filament-bouncer
git switch -c ui-c
```

- [ ] **Step 2: Borrar la capa**

```bash
git rm -r src/Filament/Resources src/Filament/Forms src/Filament/RelationManagers \
         src/Filament/Concerns/FillsRoleAbilities.php \
         src/Filament/Concerns/SavesRoleAbilities.php \
         src/Filament/Concerns/FillsAbilityHolders.php \
         resources/views resources/css \
         tests/Filament tests/RolesFieldTest.php
```

- [ ] **Step 3: Dejar el plugin y el provider sin referencias muertas**

En `FilamentBouncerPlugin::register()`, vaciar el array de recursos:

```php
public function register(Panel $panel): void
{
    $panel->resources([]);
}
```

En `FilamentBouncerServiceProvider::boot()`, quitar `loadViewsFrom()` y el bloque de `FilamentAsset::register()` con su comentario. **No se tocan** `loadTranslationsFrom()`, las dos `Gate::policy()` ni los dos `Bouncer::ownedVia()`: no son pantalla.

- [ ] **Step 4: Comprobar que lo que queda pasa entero**

Run: `composer test`
Expected: PASS. Si algún test que no era de pantalla monta un recurso borrado, se anota aquí y se decide: casi siempre es que probaba la UI desde fuera y se va con ella.

- [ ] **Step 5: Las seis puertas sobre el paquete desnudo**

Run: `composer test:all`
Expected: las seis en verde. La cobertura al 100 % tiene que salir sola: se borró código y sus tests a la vez.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "refactor(filament): cut the screen layer out"
```

Cuerpo del commit: que se retira la capa que dibuja para reescribirla de cero, que el lazo —catálogo, tienda, políticas, comandos y guardián— queda intacto, y que las pantallas vuelven en los commits siguientes.

---

## Task 2: `RoleCoverage`, el reparto de estados de un rol

**Files:**
- Create: `src/Store/RoleCoverage.php`
- Test: `tests/RoleCoverageTest.php`

**Interfaces:**
- Consumes: `RoleAbilities::toFormState(Model): array<string, array<string, string>>`, `RoleAbilities::holds(Model, Ability): bool`, `Catalog::$subjects`, `Subject::cells(): array<string, Ability>`. Los cuatro sobreviven a la demolición: son tienda y catálogo.
- Produces: `RoleCoverage::for(Model $role, Catalog $catalog): self` con las propiedades públicas `granted`, `forbidden`, `neutral`, `total` (`int`) y `reachesAll` (`bool`). Las consumen la barra resumen de la rejilla, la columna de cobertura de la tabla, el widget de stat-cards y la ficha del rol; ninguna vuelve a contar.

- [ ] **Step 1: Escribir el test que falla**

```php
<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Store\RoleCoverage;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

function coveredRole(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name]);

    return $role;
}

test('a role holding nothing is all neutral', function (): void {
    signIn();

    $coverage = RoleCoverage::for(coveredRole(), app(CatalogRegistry::class)->current());

    expect($coverage->granted)->toBe(0)
        ->and($coverage->forbidden)->toBe(0)
        ->and($coverage->neutral)->toBe($coverage->total)
        ->and($coverage->total)->toBeGreaterThan(0)
        ->and($coverage->reachesAll)->toBeFalse();
});

test('it counts a grant and a denial apart', function (): void {
    signIn();

    $role = coveredRole();
    grant($role, [['viewAny', Post::class]]);
    Bouncer::forbid($role)->to('delete', Post::class);
    Bouncer::refresh();

    $coverage = RoleCoverage::for($role, app(CatalogRegistry::class)->current());

    expect($coverage->granted)->toBe(1)
        ->and($coverage->forbidden)->toBe(1)
        ->and($coverage->neutral)->toBe($coverage->total - 2);
});

test('the wildcard reaches everything without a rule of its own', function (): void {
    signIn();

    $role = coveredRole();
    Bouncer::allow($role)->everything();
    Bouncer::refresh();

    $coverage = RoleCoverage::for($role, app(CatalogRegistry::class)->current());

    expect($coverage->reachesAll)->toBeTrue()
        ->and($coverage->granted)->toBe(0);
});
```

- [ ] **Step 2: Correr y verificar que falla**

Run: `vendor/bin/pest tests/RoleCoverageTest.php`
Expected: FAIL con `Class "ElPandaPe\FilamentBouncer\Store\RoleCoverage" not found`.

- [ ] **Step 3: Escribir la implementación**

```php
<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use Illuminate\Database\Eloquent\Model;

/**
 * How a role stands against the whole catalogue, counted once.
 *
 * Four screens ask the same question — the grid's own summary, the table, the record page
 * and the header widget — and each counting it again would walk the catalogue four times
 * per role. The wildcard is carried apart rather than folded into the grants: a role
 * holding it has no rule of its own for any cell, so a bar drawn from `granted` alone
 * would report that it can do nothing at all.
 */
final readonly class RoleCoverage
{
    public function __construct(
        public int $granted,
        public int $forbidden,
        public int $neutral,
        public int $total,
        public bool $reachesAll,
    ) {}

    public static function for(Model $role, Catalog $catalog): self
    {
        $abilities = app(RoleAbilities::class);
        $state = $abilities->toFormState($role);

        $granted = 0;
        $forbidden = 0;
        $total = 0;
        $reached = 0;

        foreach ($catalog->subjects as $key => $subject) {
            foreach ($subject->cells() as $action => $ability) {
                $total++;

                $stance = Stance::tryFrom((string) ($state[$key][$action] ?? '')) ?? Stance::Neutral;

                if ($stance === Stance::Granted) {
                    $granted++;

                    continue;
                }

                if ($stance === Stance::Forbidden) {
                    $forbidden++;

                    continue;
                }

                if ($abilities->holds($role, $ability)) {
                    $reached++;
                }
            }
        }

        $neutral = $total - $granted - $forbidden;

        return new self(
            granted: $granted,
            forbidden: $forbidden,
            neutral: $neutral,
            total: $total,
            reachesAll: $reached > 0 && $reached === $neutral,
        );
    }
}
```

- [ ] **Step 4: Correr y verificar que pasa**

Run: `vendor/bin/pest tests/RoleCoverageTest.php`
Expected: PASS, los tres.

- [ ] **Step 5: Estilo y análisis**

Run: `composer test:lint && composer test:static`
Expected: sin hallazgos. Si PHPStan se queja del genérico de `Models::role()`, anotar con `/** @var Model $role */`, como hace el resto del paquete. Nunca `ignoreErrors`.

- [ ] **Step 6: Commit**

```bash
git add src/Store/RoleCoverage.php tests/RoleCoverageTest.php
git commit -m "feat(store): count how a role stands against the catalogue"
```

---

## Task 3: El vocabulario de la rejilla nueva, en los dos idiomas

**Files:**
- Modify: `lang/en/roles.php`, `lang/es/roles.php`
- Create: `tests/LanguageParityTest.php`

**Interfaces:**
- Produces: `roles.form.{collapse,model_count,scope,empty,inherited,overruled,restricted_owned,restricted_records,undeclared}`, `roles.tabs.*`, `roles.presets.{label,read,all,none}`, `roles.summary.{granted,forbidden,neutral}`. Las Tareas 4 a 6 las consumen y no inventan ninguna más.

Se escriben **todas de una vez y en los dos archivos**. Y se añade el test de paridad que hoy no existe: es lo que impide que la segunda tanda de claves se quede sin español.

- [ ] **Step 1: Escribir el test que falla**

```php
<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Tests\TestCase;

pest()->extend(TestCase::class);

test('every string ships in both languages', function (string $file): void {
    $en = require __DIR__.'/../lang/en/'.$file.'.php';
    $es = require __DIR__.'/../lang/es/'.$file.'.php';

    $flatten = function (array $items, string $prefix = '') use (&$flatten): array {
        $keys = [];

        foreach ($items as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            $keys = [...$keys, ...(is_array($value) ? $flatten($value, $path) : [$path])];
        }

        return $keys;
    };

    expect($flatten($es))->toEqualCanonicalizing($flatten($en));
})->with(['roles', 'abilities', 'actions', 'scopes', 'stances', 'console']);
```

- [ ] **Step 2: Correr y verificar que falla**

Run: `vendor/bin/pest tests/LanguageParityTest.php`
Expected: FAIL en `roles` — las claves nuevas aún no están en ninguno de los dos, y las viejas de la rejilla borrada siguen.

- [ ] **Step 3: Reescribir `lang/en/roles.php`**

Se conserva `tabs.*` (el catálogo sigue teniendo pestañas) y las cinco notas de celda (`inherited`, `overruled`, `restricted_owned`, `restricted_records`, `undeclared`, `empty`), que describen el dominio y las vuelve a necesitar la rejilla nueva. Se borran `form.subject` y `form.manage`, cabeceras de columna que ya no existen. Se añaden:

```php
'form' => [
    'collapse' => 'Show or hide the actions of this subject',
    'model_count' => ':granted of :total',
    'scope' => 'Weight',
],

'presets' => [
    'label' => 'Set every action',
    'read' => 'Reading only',
    'all' => 'Everything',
    'none' => 'Nothing',
],

'summary' => [
    'granted' => '{1} 1 granted|[2,*] :count granted',
    'forbidden' => '{1} 1 forbidden|[2,*] :count forbidden',
    'neutral' => '{1} 1 not granted|[2,*] :count not granted',
],
```

- [ ] **Step 4: Las mismas claves en `lang/es/roles.php`**

```php
'form' => [
    'collapse' => 'Mostrar u ocultar las acciones de este sujeto',
    'model_count' => ':granted de :total',
    'scope' => 'Peso',
],

'presets' => [
    'label' => 'Poner todas las acciones en',
    'read' => 'Solo lectura',
    'all' => 'Todo',
    'none' => 'Nada',
],

'summary' => [
    'granted' => '{1} 1 concedida|[2,*] :count concedidas',
    'forbidden' => '{1} 1 prohibida|[2,*] :count prohibidas',
    'neutral' => '{1} 1 sin definir|[2,*] :count sin definir',
],
```

- [ ] **Step 5: Correr y verificar que pasa**

Run: `vendor/bin/pest tests/LanguageParityTest.php`
Expected: PASS los seis casos del dataset.

- [ ] **Step 6: Commit**

```bash
git add lang tests/LanguageParityTest.php
git commit -m "feat(lang): name what the new grid says, in both tongues"
```

---

## Task 4: El campo de la rejilla, escrito de cero

**Files:**
- Create: `src/Filament/Forms/AbilityGrid.php`
- Create: `tests/Filament/AbilityGridTest.php`

**Interfaces:**
- Consumes: `CatalogRegistry::current()`, `Catalog::tabs()`, `Subject::cells()`, `Labels::action()`, `Labels::scope()`, `Labels::stances()`, `RoleAbilities::{toFormState,holds,restrictions}`, `Stance`.
- Produces: `AbilityGrid::make(string $name)` con `->catalog(Catalog)`, y los getters `getSections()`, `getPresets()`, `getStances()`, `getNeutral()`. `getSections()` devuelve
  `array<string, array{label: string, doors: bool, subjects: array<string, array{label: string, policy: string|null, rows: array<int, array{action: string, label: string, scope: string, note: string|null, broader: bool}>}>}>`.
  La ruta de estado es `array<string, array<string, string>>` — sujeto, acción, valor de `Stance` — y **eso es contrato con la tienda**, no una elección de pantalla: es lo que `RoleAbilities::toFormState()` devuelve y lo que el guardado escribirá.

El campo es uno solo para toda la rejilla, no un campo por celda: con un campo por celda, un catálogo de treinta recursos monta ciento ochenta componentes de Livewire y cada clic es un viaje al servidor.

- [ ] **Step 1: Escribir el test que falla**

```php
<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Forms\AbilityGrid;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;

pest()->extend(TestCase::class);

function freshGrid(): AbilityGrid
{
    return AbilityGrid::make('abilities')->catalog(app(CatalogRegistry::class)->current());
}

test('a subject is described as rows, each carrying its weight', function (): void {
    signIn();

    $sections = freshGrid()->getSections();
    $rows = collect($sections['subjects']['subjects'][Subject::keyFor(Post::class)]['rows'] ?? []);

    expect($rows)->not->toBeEmpty()
        ->and($rows->firstWhere('action', 'viewAny')['scope'] ?? null)->toBe('read')
        ->and($rows->firstWhere('action', 'delete')['scope'] ?? null)->toBe('withdraw');
});

test('reading only is not everything', function (): void {
    signIn();

    $presets = freshGrid()->getPresets();

    expect($presets['read'])->toContain('viewAny')
        ->and($presets['read'])->not->toContain('delete');
});

test('a door is not laid out as a grid', function (): void {
    signIn();

    expect(freshGrid()->getSections()['pages']['doors'] ?? null)->toBeTrue()
        ->and(freshGrid()->getSections()['subjects']['doors'] ?? null)->toBeFalse();
});
```

- [ ] **Step 2: Correr y verificar que falla**

Run: `vendor/bin/pest tests/Filament/AbilityGridTest.php`
Expected: FAIL con la clase no encontrada.

- [ ] **Step 3: Escribir el campo**

`AbilityGrid extends Filament\Forms\Components\Field`, `protected string $view = 'filament-bouncer::forms.ability-grid';`, propiedad privada `Catalog $catalog` con su setter `catalog()`, y una closure `notes()` como la que tenía el paquete —las notas dependen del registro y solo la página lo tiene—. `getSections()` recorre `$this->catalog->tabs()`, marca `doors` con `! $tab->isGrid()`, y por cada sujeto arma `rows` con `action`, `label` (de `Labels::action()`), `scope` (de `$this->catalog->actions[$action] ?? AbilityScope::Write`), `note` y `broader`. `getPresets()` devuelve `['read' => …]` con las acciones cuyo scope es `AbilityScope::Read`. `getStances()` devuelve `Labels::stances()`; `getNeutral()`, `Stance::Neutral->value`.

`broader` y `note` se calculan como los calculaba el paquete —esa lógica es correcta y su porqué está escrito en los tests que la fijaban—: una celda en neutro cuya habilidad el rol **sí** tiene es `broader`, y sin eso un rol con el comodín se dibujaría incapaz de todo.

- [ ] **Step 4: Correr y verificar que pasa**

Run: `vendor/bin/pest tests/Filament/AbilityGridTest.php`
Expected: PASS los tres. La vista aún no existe: estos tests no la renderizan, solo piden los getters.

- [ ] **Step 5: Commit**

```bash
git add src/Filament/Forms/AbilityGrid.php tests/Filament/AbilityGridTest.php
git commit -m "feat(forms): describe the catalogue as sections of rows"
```

---

## Task 5: La celda: tres botones, no un ciclo

**Files:**
- Create: `resources/views/forms/ability-cell.blade.php`
- Create: `resources/css/filament-bouncer.css` (nuevo, desde cero: tokens + segmentado)
- Modify: `src/FilamentBouncerServiceProvider.php` (reponer `loadViewsFrom` y el registro del CSS)
- Test: `tests/Filament/AbilityGridTest.php`

**Interfaces:**
- Consumes: `$subject`, `$action`, `$note`, `$broader`, `$disabled`, `$stances`, que le pasa el include de la Tarea 6.
- Produces: `.fb-seg` con tres `.fb-seg-btn`. Escribe `state[subject][action] = <stance>` llamando a `set()`, que declara la Tarea 6.

El paquete anterior usaba **un** botón que ciclaba, y su comentario decía por qué: tres nunca cabían en una columna. En una fila caben, y elegir directamente quita la trampa del ciclo — llegar a «prohibida» obligaba a pasar por «concedida», una regla que existía en pantalla un instante y nunca a propósito.

- [ ] **Step 1: Escribir el test que falla**

```php
test('each stance is its own button, and its word is its accessible name', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    expect(view('filament-bouncer::forms.ability-cell', [
        'subject' => Subject::keyFor(Post::class),
        'action' => 'viewAny',
        'note' => null,
        'broader' => false,
        'disabled' => false,
        'stances' => app(ElPandaPe\FilamentBouncer\Support\Labels::class)->stances(),
    ])->render())
        ->toContain('class="fb-seg"')
        ->toContain('aria-pressed')
        ->toContain(__('filament-bouncer::stances.granted'))
        ->toContain(__('filament-bouncer::stances.neutral'))
        ->toContain(__('filament-bouncer::stances.forbidden'));
});
```

- [ ] **Step 2: Correr y verificar que falla**

Run: `vendor/bin/pest tests/Filament/AbilityGridTest.php --filter="its own button"`
Expected: FAIL — la vista no existe.

- [ ] **Step 3: Escribir la vista**

```blade
{{--
    One action, three words.

    A row has the width three buttons need, and choosing directly removes the trap a cycle
    carries: reaching "forbidden" through "granted" puts a rule on screen that nobody
    meant, however briefly.

    The neutral segment of a cell reached by a broader rule draws an outlined tick rather
    than a dash. The dash is true of the row and false of the reader's question: a role
    holding the wildcard has no rule of its own for any cell here, and a screen full of
    dashes would say it can do nothing at all.
--}}
<div class="fb-seg" role="group" @if (filled($note)) title="{{ $note }}" @endif>
    @foreach (['granted', 'neutral', 'forbidden'] as $stance)
        <button
            type="button"
            @disabled($disabled)
            x-on:click="set(@js($subject), @js($action), @js($stance))"
            x-bind:aria-pressed="at(@js($subject), @js($action)) === @js($stance) ? 'true' : 'false'"
            x-bind:class="'fb-seg-btn fb-seg-' + @js($stance) + (at(@js($subject), @js($action)) === @js($stance) ? ' fb-seg-on' : '')"
            class="fb-seg-btn fb-seg-{{ $stance }}"
            aria-label="{{ $stances[$stance] ?? $stance }}"
        >
            @if ($stance === 'granted' || ($stance === 'neutral' && $broader))
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
            @elseif ($stance === 'neutral')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14" /></svg>
            @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
            @endif
        </button>
    @endforeach
</div>
```

- [ ] **Step 4: Escribir el CSS de cero**

Archivo nuevo `resources/css/filament-bouncer.css`, empezando por la cabecera que explica por qué no hay utilidades de Tailwind y por qué el oscuro va con `:is(.dark)`, los tokens `--fb-*` en `.fb` y su bloque oscuro, y a continuación el segmentado (`.fb-seg`, `.fb-seg-btn`, `.fb-seg-on` y las tres variantes). Los tokens salen de los del panel: `--gray-*` para superficies y texto, `--success-600`/`--danger-600` para las dos posturas duras, `--info-600`/`--warning-600`/`--danger-600` para los pesos de scope.

- [ ] **Step 5: Reponer el registro de vistas y del CSS**

En `FilamentBouncerServiceProvider::boot()`, devolver `loadViewsFrom(__DIR__.'/../resources/views', 'filament-bouncer')` y el `FilamentAsset::register([Css::make('filament-bouncer', …)], 'elpandape/filament-bouncer')`, con su comentario de por qué se registra con Filament y no se enlaza desde la vista.

- [ ] **Step 6: Correr y verificar que pasa**

Run: `vendor/bin/pest tests/Filament/AbilityGridTest.php --filter="its own button"`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add resources src/FilamentBouncerServiceProvider.php tests/Filament/AbilityGridTest.php
git commit -m "feat(forms): let a stance be chosen, not cycled to"
```

---

## Task 6: Las secciones, los presets, la barra resumen y el peso (D3)

**Files:**
- Create: `resources/views/forms/ability-grid.blade.php`
- Modify: `resources/css/filament-bouncer.css`
- Test: `tests/Filament/AbilityGridTest.php`

**Interfaces:**
- Consumes: `getSections()`, `getPresets()`, `getStances()`, `getNeutral()` de la Tarea 4; el include de la Tarea 5.
- Produces: el `x-data` con `set(subject, action, stance)`, `at(subject, action)`, `preset(subject, actions, stance)` y `tally()`. `tally()` devuelve `{granted, forbidden, neutral}` contados sobre el estado; ninguna otra pieza cuenta por su cuenta.

- [ ] **Step 1: Escribir el test que falla**

Necesita una página que monte el campo. Como la pantalla de roles todavía no existe, el test monta el campo dentro de un componente de prueba en `tests/Fixtures/Filament/Pages/GridHost.php` — un `Filament\Pages\Page` con `AuthorizesPage` (el guardián lo exige) y un schema de un solo `AbilityGrid`.

```php
test('the grid folds by subject, totals live, and shows the weight of an action', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class], ['delete', Post::class]]);

    livewire(GridHost::class)
        ->assertSeeHtml('class="fb-summary"')
        ->assertSeeHtml('fb-preset')
        ->assertSeeHtml('fb-weight-read')
        ->assertSeeHtml('fb-weight-withdraw')
        ->assertSee(__('filament-bouncer::roles.presets.read'));
});
```

- [ ] **Step 2: Correr y verificar que falla**

Run: `vendor/bin/pest tests/Filament/AbilityGridTest.php --filter="folds by subject"`
Expected: FAIL — no existe la vista de la rejilla.

- [ ] **Step 3: Escribir la vista**

`x-data` con `state: $wire.$entangle('{{ $getStatePath() }}')`, `open: {}`, `disabled`, y los cuatro métodos. Por cada sección con `doors` a `false`: una `<section class="fb-subject">` por sujeto, con cabecera plegable (`x-on:click`, `x-bind:aria-expanded`, etiqueta de `roles.form.collapse`), contador `roles.form.model_count`, menú de presets (`fb-preset`, tres entradas de `roles.presets.*`) y una fila por `row` con `data-action`, la marca `<span class="fb-weight fb-weight-{{ $row['scope'] }}">` (D3), la etiqueta, el nombre técnico y el include de la celda. Las secciones con `doors` a `true` se dibujan como lista: una habilidad por sujeto, que es la forma correcta para una puerta. Al pie, `<div class="fb-summary">` con los tres números por `x-text` sobre `tally()`.

- [ ] **Step 4: Añadir su CSS**

`.fb-subject`, `.fb-subject-head`, `.fb-subject-count`, `.fb-action-row`, `.fb-weight` con sus cuatro variantes de scope, `.fb-preset`, `.fb-summary` con `position: sticky; inset-block-end: 0`, y la lista de puertas.

- [ ] **Step 5: Correr y verificar que pasa**

Run: `vendor/bin/pest tests/Filament/AbilityGridTest.php`
Expected: PASS entero.

- [ ] **Step 6: Medir el primer render antes de dar la fase por buena**

Tres botones por acción triplican los nodos frente a una matriz. Con un catálogo de treinta recursos por seis acciones son quinientos cuarenta botones. Añadir al fixture del panel de prueba diez recursos más y comprobar que la página sigue montando en un tiempo razonable; si no, el plegado por defecto pasa a cerrado y se anota aquí la decisión con su medida.

- [ ] **Step 7: Las seis puertas**

Run: `composer test:all`
Expected: las seis en verde.

- [ ] **Step 8: Commit**

```bash
git add resources tests
git commit -m "feat(forms): fold the catalogue and total it live"
```

---

## Self-review

- **Cobertura del alcance:** D3 queda en la Tarea 6. D2 y D6, en la Fase 2. D1, D4 y D7, en la Fase 3. D5 no exige trabajo. De las 66 garantías, esta fase vuelve a probar las de la rejilla que son del campo y de su vista; las que dependen de una pantalla (guardar, refusar, listar) vuelven con su pantalla en las fases siguientes, y **cada fase se cierra comprobando su bloque de la lista**.
- **Consistencia de tipos:** `getSections()` devuelve `rows` con `action`, `label`, `scope`, `note`, `broader`, que son exactamente las claves que leen las Tareas 5 y 6. `set`/`at`/`preset`/`tally` se declaran en la Tarea 6 y se llaman con esa firma en la 5.
- **Riesgo asumido y vigilado:** entre la Tarea 1 y el final de la Fase 2 el paquete no tiene pantallas. Vive en la rama `ui-c` y no se publica hasta que la lista de garantías esté entera.

---

## Las fases siguientes, cada una con su plan

Se escriben cuando esta esté en verde: cada una consume lo que la anterior deja.

- **Fase 2 — roles.** `RoleResource` y sus cuatro páginas de cero, con `Wizard` en el alta, columna de cobertura y widget de stat-cards en el listado, ficha respetando D6, y las dos salvaguardas del recurso repuestas y probadas. Cierra las garantías de alta, edición, ficha y listado (36).
- **Fase 3 — habilidades.** `AbilityResource` de cero: agrupación por modelo conservando la columna `declared` (D4), compositor como asistente con frase viva, ficha con marca por titular (D7) y sin botón de eliminar (D1). Cierra las 19 de habilidades.
- **Fase 4 — roles de una cuenta.** `RolesField` y `RolesRelationManager` de cero, con el mismo nombre y namespace. Cierra las 11 restantes, entre ellas las tres de la vía de vuelta.
- **Fase 5 — cierre.** README, CHANGELOG, `5.0.0`, y la nota de migración para quien consumiera las clases borradas.
