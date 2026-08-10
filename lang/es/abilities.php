<?php

declare(strict_types=1);

return [

    'title' => 'Habilidades',

    'ability' => 'Habilidad',

    'direct' => 'concedida en este rol',

    'broader' => 'Con contorno: el rol responde que sí sin una regla propia que nombre la habilidad — la tiene por una más amplia.',

    'nobody' => 'ninguna de sus reglas la alcanza',

    'empty' => 'Todavía no hay nada que informar: o no posees ninguna habilidad, o no se ha creado ningún rol.',

    'retitle' => 'Renombrar',

    'retitle_note' => 'El título lo lee la gente y nadie más. El nombre que el código le pregunta al Gate, y el modelo sobre el que pregunta, los declara el código y no se cambian aquí.',

    'title_field' => 'Título',

    'retitled' => 'Renombrada.',

    'unreconciled' => 'Esta habilidad todavía no tiene fila. Ejecuta antes filament-bouncer:reconcile.',

    'declared' => 'El nombre que el código le pregunta al Gate, y el modelo sobre el que pregunta, los declara el código que pregunta —un método de Policy, una página, un widget o la clave «custom» de la configuración— y los escribe filament-bouncer:reconcile. Ninguno de los dos se reescribe aquí.',

    'narrow' => 'Acotar una habilidad',

    'narrow_note' => 'La regla llana —«puede cambiar artículos»— la declara el código que pregunta por ella y la escribe filament-bouncer:reconcile, así que aquí no se compone. Lo que el código no tiene forma de decir es hasta dónde llega la regla: eso es lo que hace esta pantalla. Una fila acotada a un registro, o a lo que su titular posee, es una de las que la conciliación nunca reclama, así que --check no falla por ella y --prune no se la lleva.',

    'narrow_required' => 'Una habilidad que no acota nada es la llana, que declara el código y escribe filament-bouncer:reconcile. Acótala a lo que su titular posee, a un registro, o a las dos cosas.',

    'duplicate' => 'Esa fila ya existe. Ábrela en vez de escribir una segunda: dos filas que dicen lo mismo se reparten y se quitan por separado, y la pantalla solo te enseñaría una de ellas.',

    'action_field' => 'Acción',

    'only_owned_note' => 'La regla vale solo para los registros que su titular posee.',

    'record_field' => 'Un registro',

    'record_note' => 'La clave del único registro para el que vale la regla. Déjalo vacío para alcanzarlos todos.',

    'compose_note' => 'Se escribe solo según eliges, y puedes cambiarlo. El título lo lee la gente y nadie más.',

    'owned_suffix' => 'solo lo suyo',

    'record_suffix' => 'registro :id',

    'reach' => 'Alcance',

    'reach_all' => 'todos',

    'narrowed_legend' => 'Esta regla llega menos lejos que la de la rejilla, así que allí no tiene casilla. Repartirla aquí escribe exactamente esta fila y deja la llana en paz.',

    'name_field' => 'Nombre',

    'entity_field' => 'Modelo',

    'no_entity' => '— ninguno —',

    'holders' => 'Roles',

    'declared_column' => 'Declarada',

    'declared_yes' => 'por el código',

    'declared_no' => 'por nada',

    'declared_apart' => 'fuera del catálogo',

    'only_owned' => 'Solo lo que su titular posee',

    'holders_section' => 'Quién la tiene',

    'holders_note' => 'Las mismas filas que escribe la pantalla de roles, vistas desde el otro extremo. Una casilla de aquí y la de allá son la misma fila de la misma tabla.',

    'broader_short' => 'por una regla más amplia',

    'nobody_short' => 'nadie',

    'withheld' => 'El código ya no declara esta habilidad, así que aquí no hay nada que repartir.',

];
