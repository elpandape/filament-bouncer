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

    'declared' => 'Las habilidades las declara el código que pregunta por ellas —un método de Policy, una página, un widget o la clave «custom» de la configuración— y las escribe filament-bouncer:reconcile. Una creada aquí sería una fila que el catálogo no declara: --check fallaría por ella y --prune la borraría.',

    'name_field' => 'Nombre',

    'entity_field' => 'Modelo',

    'no_entity' => '— ninguno —',

    'holders' => 'Roles',

    'declared_column' => 'Declarada',

    'declared_yes' => 'por el código',

    'declared_no' => 'por nada',

    'only_owned' => 'Solo lo que su titular posee',

    'holders_section' => 'Quién la tiene',

    'holders_note' => 'Las mismas filas que escribe la pantalla de roles, vistas desde el otro extremo. Una casilla de aquí y la de allá son la misma fila de la misma tabla.',

    'broader_short' => 'por una regla más amplia',

    'nobody_short' => 'nadie',

    'withheld' => 'El código ya no declara esta habilidad, así que aquí no hay nada que repartir.',

];
