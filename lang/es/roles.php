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
        'manage' => 'Todo sobre esto',
        'undeclared' => 'La Policy de este sujeto no declara esta acción.',
        'collapse' => 'Mostrar u ocultar las acciones de este sujeto',
        'forbidden_count' => '{1} 1 prohibida|[2,*] :count prohibidas',
        'model_count' => ':granted de :total',
        'reserved' => 'Ese nombre es el del rol que lo tiene todo, que escribe la reconciliación y no esta pantalla.',
        'scope' => 'Peso',
    ],

    'wizard' => [
        'identity' => 'El rol',
        'identity_hint' => 'Cómo se llama, y cómo se le nombra.',
        'abilities' => 'Habilidades',
        'abilities_hint' => 'Lo que podrá hacer quien lo tenga.',
        'review' => 'Revisión',
        'review_hint' => 'Lo que está a punto de escribirse.',
        'reading' => ':name se va a crear concediendo :granted de las :total habilidades que el panel declara, y prohibiendo :forbidden.',
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

    'tabs' => [
        'subjects' => 'Recursos y modelos',
        'pages' => 'Páginas',
        'widgets' => 'Widgets',
        'custom' => 'Personalizadas',
    ],

    'resource' => [
        'label' => 'Rol',
        'plural' => 'Roles',
    ],

    'table' => [
        'abilities' => 'Habilidades',
        'name' => 'Rol',
        'coverage' => 'Alcance',
        'reading' => ':granted de :total',
        'reaches_all' => 'Todo, por el comodín',
        'holders' => 'Cuentas',
        'updated' => 'Cambiado',
        'empty' => 'Todavía no hay ningún rol compuesto.',
    ],

    'stats' => [
        'roles' => 'Roles',
        'roles_note' => 'Compuestos en esta pantalla.',
        'abilities' => 'Habilidades declaradas',
        'abilities_note' => 'Sobre lo que el panel sabe preguntar.',
        'forbidden' => 'Prohibiciones vigentes',
        'forbidden_note' => 'Una prohibición vence a cualquier concesión que llegue a la misma habilidad.',
        'unassigned' => 'Cuentas sin ningún rol',
        'unassigned_note' => 'Llegan al panel y no pueden hacer nada en él.',
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
