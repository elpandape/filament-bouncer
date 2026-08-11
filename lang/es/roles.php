<?php

declare(strict_types=1);

return [

    'form' => [
        'role' => 'Rol',
        'name' => 'Nombre',
        'title' => 'Título',
        'abilities' => 'Habilidades',
        'description' => 'Todo lo que el panel declara. Quien pueda trabajar esta pantalla reparte cualquiera de ellas, también a sí mismo.',
        'empty' => 'No tienes ninguna habilidad propia, así que aquí no hay nada que ceder.',
        'inherited' => 'La tiene por una regla más amplia, no concedida aquí.',
        'overruled' => 'Concedida aquí, pero una regla más amplia la prohíbe.',
        'restricted_owned' => 'También tiene aquí una regla para solo lo suyo, que la rejilla no toca.',
        'restricted_records' => 'También tiene aquí una regla sobre un registro, que la rejilla no toca.|También tiene aquí reglas sobre :count registros, que la rejilla no toca.',
        'manage' => 'Todo',
        'subject' => 'Sujeto',
        'undeclared' => 'La Policy de este sujeto no declara esta acción.',
    ],

    'tabs' => [
        'subjects' => 'Recursos y modelos',
        'pages' => 'Páginas',
        'widgets' => 'Widgets',
        'custom' => 'Personalizadas',
    ],

    'table' => [
        'abilities' => 'Habilidades',
    ],

    'relation' => [
        'title' => 'Roles',
        'assign' => 'Asignar un rol',
        'assign_submit' => 'Asignar',
        'retract' => 'Quitar',
        'role' => 'Rol',
        'empty' => 'Esta cuenta no tiene ningún rol.',
    ],

    'field' => [
        'label' => 'Roles',
        'note' => 'Lo que esta cuenta podrá hacer. Un rol que no se le puede entregar se muestra, pero no se puede marcar.',
    ],

];
