<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'Habilidad',
        'plural' => 'Habilidades',
    ],

    'narrow' => 'Acotar una habilidad',

    'saved' => 'Se ha escrito lo que cada rol dice de esta regla.',

    'form' => [
        'rule' => 'Definición',
        'name' => 'Nombre',
        'entity' => 'Modelo',
        'no_entity' => '— ninguno —',
        'reach' => 'Alcance',
        'title' => 'Título',
        'title_note' => 'Lo lee la gente y nadie más.',
        'created' => 'Creada',
        'declared_note' => 'El nombre que el código le da al Gate, y el modelo que le pasa con él, los declara el código que pregunta —un método de Policy, una página, un widget o la clave «custom» de la configuración— y los escribe filament-bouncer:reconcile. Ninguno de los dos se reescribe aquí.',
        'holders' => 'Impacto actual',
        'holders_note' => 'Quién tiene esta habilidad hoy. Las mismas filas que escribe la pantalla de roles, vistas desde el otro extremo.',
        'holders_empty' => 'Todavía no hay ningún rol compuesto.',
        'withheld' => 'El código ya no declara esta habilidad, así que aquí no hay nada que repartir. El próximo filament-bouncer:reconcile --prune se lleva la fila, y con ella cada concesión que la apuntaba.',
        'direct_heading' => 'Usuarios directos',
        'direct_granted' => 'Concedida directa',
        'direct_forbidden' => 'Prohibida directa',
        'direct_note' => 'Concesión directa: no llega por ningún rol. Quitarla solo afecta a esa cuenta. Si fuera una prohibición, ganaría a cualquier concesión que llegara por un rol.',
    ],

    'phrase' => [
        'can' => 'Puede',
        'action' => 'acción…',
        'subject' => 'modelo…',
        'reach' => 'alcance…',
        'note' => 'La frase se recompone en vivo con lo que elijas en cada paso.',
    ],

    'chips' => [
        'all' => 'Todas',
        'narrowed' => 'Acotadas',
        'wildcard' => 'Comodín',
        'forbidden' => 'Prohibiciones en uso',
    ],

    'reach' => [
        'all' => 'Todos',
        'owned' => 'Solo lo que su titular posee',
        'record' => 'Un registro',
        'record_reading' => 'Registro :id',
    ],

    'declared' => [
        'label' => 'Declarada',
        'declared' => 'por el código',
        'drifted' => 'por nada',
        'apart' => 'fuera del catálogo',
        'declared_note' => 'El código declara esta habilidad, así que la reconciliación la conserva.',
        'drifted_note' => 'Ya no la declara nadie. El próximo filament-bouncer:reconcile --prune se la lleva, y con ella cada concesión que la apuntaba.',
        'apart_note' => 'Esta regla nunca fue de la reconciliación, así que --check no falla por ella y --prune no se la lleva.',
    ],

    'wizard' => [
        'subtitle' => 'Compón la frase: una acción, un modelo y un alcance.',
        'ability' => 'La habilidad',
        'ability_hint' => 'Qué se puede hacer, y sobre qué.',
        'actions_note' => 'Las acciones del catálogo salen de las Policies; no se escriben a mano.',
        'actions_empty' => 'Elige primero el modelo.',
        'reach' => 'Alcance',
        'reach_hint' => 'Hasta dónde llega la regla.',
        'review' => 'Revisión',
        'review_hint' => 'Lo que está a punto de escribirse.',
        'subject' => 'Modelo',
        'action' => 'Acción',
        'reach_field' => 'La regla vale para',
        'record' => 'Registro',
        'record_note' => 'La clave del único registro para el que vale la regla.',
        'title' => 'Título',
        'reading' => 'Se va a escribir: :rule, con alcance :reach.',
        'nothing' => 'todavía no has elegido nada',
    ],

    'refusals' => [
        'narrow' => 'Una regla que no acota nada es la llana, que declara el código y escribe filament-bouncer:reconcile. Acótala a lo que su titular posee, o a un único registro.',
        'duplicate' => 'Esa fila ya existe. Ábrela en vez de escribir una segunda: dos filas que dicen lo mismo se reparten y se quitan por separado, y esta pantalla solo te enseñaría una de ellas.',
        'unknown' => 'El catálogo no declara ese par, y Bouncer nunca se queja de un nombre que nadie declaró: responde que no, para siempre, a todo el mundo.',
        'record_needs_model' => 'Una regla sobre un único registro necesita un modelo en el que buscarlo, y esta habilidad no es sobre ninguno.',
    ],

    'table' => [
        'name' => 'Habilidad',
        'entity' => 'Modelo',
        'reach' => 'Alcance',
        'holders' => 'La tienen',
        'holder' => ':role — :stance',
        'group_count' => '{1} :count habilidad|[2,*] :count habilidades',
        'empty' => 'Todavía no se ha reconciliado nada. Ejecuta filament-bouncer:reconcile.',
    ],

];
