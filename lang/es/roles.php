<?php

declare(strict_types=1);

return [

    'form' => [
        'role' => 'Rol',
        'name' => 'Nombre',
        'title' => 'Título',
        'empty' => 'No tienes ninguna habilidad propia, así que aquí no hay nada que ceder.',
        'inherited' => 'La tiene por una regla más amplia, no concedida aquí.',
        'overruled' => 'Concedida aquí, pero una regla más amplia la prohíbe.',
        'restricted_owned' => 'También tiene aquí una regla para solo lo suyo, que la rejilla no toca.',
        'restricted_records' => 'También tiene aquí una regla sobre un registro, que la rejilla no toca.|También tiene aquí reglas sobre :count registros, que la rejilla no toca.',
        'manage' => 'Todo sobre esto',
        'collapse' => 'Mostrar u ocultar las acciones de este sujeto',
        'forbidden_count' => '{1} 1 prohibida|[2,*] :count prohibidas',
        'model_count' => ':granted de :total',
        'requires_stance' => 'Concede o prohíbe al menos una habilidad antes de continuar.',
        'reserved' => 'Ese nombre es el del rol que lo tiene todo, que escribe la reconciliación y no esta pantalla.',
        'name_placeholder' => 'p. ej. soporte',
        'name_help' => 'Minúsculas y guiones. Es el <code>name</code> del rol en Bouncer.',
        'title_placeholder' => 'p. ej. Atención a usuarios',
        'title_help' => 'Cómo se muestra en listados y fichas.',
        'protected_notice' => '<b>:name</b> es el rol protegido: solo lo entrega quien ya lo tiene y a su último titular no se le puede quitar. Ningún rol nuevo puede llamarse así.',
        'save' => 'Guardar cambios',
        'cancel' => 'Cancelar',
    ],

    'wizard' => [
        'subtitle' => 'Tres pasos: identidad, permisos y revisión. Nada se guarda hasta el final.',
        'identity' => 'El rol',
        'identity_hint' => 'Cómo se llama, y cómo se le nombra.',
        'identity_heading' => 'Identidad del rol',
        'identity_note' => 'El nombre es el identificador interno; el título es lo que ven las personas.',
        'abilities' => 'Habilidades',
        'abilities_heading' => 'Permisos',
        'abilities_note' => 'El catálogo deriva de las Policies. Para cada acción: concédela (✓), abstente (–) o prohíbela (✗). Una prohibición gana a cualquier concesión que llegue por otro rol.',
        'abilities_hint' => 'Lo que podrá hacer quien lo tenga.',
        'review' => 'Revisión',
        'review_heading' => 'Revisión',
        'review_note' => 'Lo que se va a escribir, sujeto a sujeto.',
        'review_hint' => 'Lo que está a punto de escribirse.',
    ],

    'review' => [
        'silent' => 'Sin definir: el rol se abstiene',
        'total' => ':granted concedidas · :forbidden prohibidas · :neutral sin definir',
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

    'list' => [
        'subtitle' => 'La postura de seguridad del panel de un vistazo: quién puede qué, y qué está prohibido.',
    ],

    'table' => [
        'name' => 'Rol',
        'coverage' => 'Alcance',
        'reaches_all' => 'Todo, por el comodín',
        'holders' => 'Cuentas',
        'updated' => 'Cambiado',
        'empty' => 'Todavía no hay ningún rol compuesto.',
        'search' => 'Buscar por nombre o título',
        'legend' => 'cobertura sobre las :total habilidades del catálogo',
        'locked' => 'Este rol no se trabaja desde aquí: es la vía de vuelta, o uno que tú tienes.',
    ],

    'edit' => [
        'holders' => '{0} 0 usuarios con este rol|{1} 1 usuario con este rol|[2,*] :count usuarios con este rol',
        'updated' => 'actualizado :when',
    ],

    'record' => [
        'identity' => 'Identidad',
        'name' => 'Nombre',
        'title' => 'Título',
        'holders' => 'Usuarios con el rol',
        'updated' => 'Actualizado',
        'created' => 'Creado',
        'forbidden_heading' => 'Prohibiciones',
        'forbidden_empty' => 'Este rol no prohíbe nada.',
        'forbidden_note' => 'Una prohibición gana a cualquier concesión que llegue por otro rol.',
        'holders_heading' => 'Usuarios con este rol',
        'holders_empty' => 'Nadie tiene este rol.',
        'retract' => 'Quitar rol',
        'last_holder' => 'La vía de vuelta no se le quita nunca a su último titular.',
    ],

    'coverage' => [
        'catalog' => 'Cobertura sobre las :total habilidades del catálogo',
        'granted' => '{1} concedida|concedidas',
        'forbidden' => '{1} prohibida|prohibidas',
        'neutral' => 'sin definir',
        'of' => 'de :total',
    ],

    'stats' => [
        'roles' => 'Roles',
        'abilities' => 'Habilidades declaradas',
        'forbidden' => 'Prohibiciones vigentes',
        'unassigned' => 'Cuentas sin ningún rol',
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
