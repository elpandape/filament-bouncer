<?php

declare(strict_types=1);

return [

    'form' => [
        'abilities_note' => 'Todo lo que el panel declara. Quien puede trabajar esta pantalla reparte cualquiera de ellas, incluso a sí mismo.',
        'name' => 'Nombre',
        'title' => 'Título',
        'empty' => 'No tienes ninguna habilidad propia, así que aquí no hay nada que ceder.',
        'inherited' => 'La tiene por una regla más amplia, no concedida aquí.',
        'overruled' => 'Concedida aquí, pero una regla más amplia la prohíbe.',
        'restricted_owned' => 'También tiene aquí una regla para solo lo suyo, que la rejilla no toca.',
        'restricted_records' => 'También tiene aquí una regla sobre un registro, que la rejilla no toca.|También tiene aquí reglas sobre :count registros, que la rejilla no toca.',
        'manage' => 'Administrar',
        'forbidden_count' => '{1} 1 prohibida|[2,*] :count prohibidas',
        'model_count' => ':granted de :total',
        'requires_stance' => 'Concede o prohíbe al menos una habilidad antes de continuar.',
        'reserved' => 'Ese nombre es el del rol que lo tiene todo, que escribe la reconciliación y no esta pantalla.',
        'name_placeholder' => 'p. ej. soporte',
        'name_help' => 'Minúsculas y guiones. Es el nombre del rol en Bouncer, el que se escribe en el código.',
        'protected_notice' => '<b>:name</b> es el rol protegido: solo lo entrega quien ya lo tiene y a su último titular no se le puede quitar. Ningún rol nuevo puede llamarse así.',
        'save' => 'Guardar cambios',
        'cancel' => 'Cancelar',
    ],

    'summary' => [
        'granted' => '{1} 1 concedida|[2,*] :count concedidas',
        'forbidden' => '{1} 1 prohibida|[2,*] :count prohibidas',
        'neutral' => '{1} 1 sin definir|[2,*] :count sin definir',
    ],

    'grid' => [
        'subject' => 'Sujeto',
        'clear' => 'Vaciar',
        'undeclared' => 'no declarada',
        'preset_read' => 'Solo lectura',
        'note_legend' => 'El punto marca una regla acotada a lo propio o a un registro: esta pantalla no la escribe ni la borra, así que su casilla dice menos de lo que el rol puede.',
        'hint' => 'Un clic recorre las tres posturas; con la tecla Mayús, al revés. Una prohibición gana a cualquier concesión que llegue por otro rol.',
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
        'scope' => 'Tenant',
        'title' => 'Título',
        'created' => 'Creado',
        'name' => 'Rol',
        'reaches_all' => 'Todo, por el comodín',
        'updated' => 'Cambiado',
        'empty' => 'Todavía no hay ningún rol compuesto.',
        'search' => 'Buscar por nombre o título',
        'locked' => 'Este rol no se trabaja desde aquí: es la vía de vuelta, o uno que tú tienes.',
    ],

    'record' => [
        'identity_note' => 'El nombre por el que pregunta el código, y el que se lee en pantalla.',
        'title_empty' => 'Sin título',
        'metadata' => 'Metadatos',
        'scope' => 'Tenant',
        'scope_global' => 'Global',
        'abilities_heading' => 'Habilidades',
        'abilities_note' => 'Lo que este rol concede y lo que prohíbe, y nada más.',
        'orphans_heading' => 'Habilidades huérfanas',
        'narrowed_heading' => 'Acotadas',
        'narrowed_note' => 'Se componen a mano en la pantalla de habilidades. La rejilla de este rol no las escribe ni las borra.',
        'owned' => 'solo lo que le pertenece',
        'record_gone' => 'ya no existe',
        'silent_spelled' => 'No dice nada sobre :names.',
        'silent_counted' => 'No dice nada sobre otros :count sujetos.',
        'silent_more' => 'Ver cuáles',
        'and' => 'y',
        'tags_empty' => 'Este rol no concede ni prohíbe nada.',
        'orphans_loose' => 'Sin sujeto',
        'orphans_none' => 'Nada por perder',
        'orphans_some' => 'Se perderán',
        'orphans_note_none' => 'Ninguna regla de este rol apunta a una habilidad que el código haya dejado de declarar.',
        'orphans_note_some' => 'El próximo sync con --prune se las lleva, y con ellas lo que concedían.',
        'identity' => 'Identidad',
        'name' => 'Nombre',
        'title' => 'Título',
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

    'relation' => [
        'title' => 'Roles',
        'assign' => 'Asignar un rol',
        'assign_submit' => 'Asignar',
        'retract' => 'Quitar',
        'empty' => 'Esta cuenta no tiene ningún rol.',
    ],

    'field' => [
        'label' => 'Roles',
        'note' => 'Lo que esta cuenta podrá hacer. Un rol que no se le puede entregar se muestra, pero no se puede marcar.',
    ],

];
