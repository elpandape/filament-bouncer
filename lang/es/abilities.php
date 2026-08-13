<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'Habilidad',
        'plural' => 'Habilidades',
    ],

    'form' => [
        'rule' => 'Regla',
        'rule_note' => 'Lo que el código le pide al Gate, y cómo se lee eso en pantalla.',
        'reach' => 'Alcance',
        'reach_note' => 'Sobre qué decide la regla. Las tres respuestas juntas son lo que la separa de otra con el mismo nombre.',
        'restrictions' => 'Restricciones',
        'restrictions_note' => 'Lo que Bouncer guarda en la columna «options».',
        'tenant' => 'Tenant',
        'metadata' => 'Metadatos',

        'name' => 'Nombre',
        'name_from_list' => 'Las acciones que el panel declara hoy: los métodos de sus Policies, más el comodín.',
        'name_by_hand' => 'Un nombre que el catálogo todavía no declara. Bouncer no avisa de una errata: contesta que no, para siempre.',
        'take_name' => 'Escribirlo a mano',
        'pick_name' => 'Elegir de la lista',
        'any_action' => 'Cualquier acción (:wildcard)',

        'model' => 'Modelo',
        'model_note' => 'Los modelos que el panel pone en pantalla, más el comodín.',
        'model_none' => 'Ninguno: la regla no habla de ningún modelo',
        'any_model' => 'Cualquier modelo',

        'record' => 'Registro',
        'record_note' => 'Acota la regla a un registro concreto de ese modelo. En blanco, alcanza a todos.',
        'record_missing' => 'No existe ningún registro con ese id',

        'owned' => 'Solo lo propio',
        'owned_note' => 'Acota la regla a lo que su titular posee, según la clave «ownership» de la configuración.',

        'scope' => 'Tenant',
        'scope_note' => 'En blanco es global. Con tenant, solo esta pantalla la ve.',
        'scope_global' => 'Global',

        'options' => 'Opciones',
        'options_note' => 'Bouncer guarda aquí su árbol de restricciones, bajo la clave «constraints». Es de un solo nivel: un valor anidado se aplanaría al guardar.',
        'options_key' => 'Clave',
        'options_value' => 'Valor',
        'options_empty' => 'Sin restricciones',

        'created' => 'Creada',
        'updated' => 'Modificada',

        'twin' => 'Ya hay una habilidad idéntica: mismo nombre, mismo modelo, mismo registro, mismo «solo lo propio» y mismo tenant. Dos filas iguales se reparten y se retiran por separado, así que quien retirara una creería haber quitado la regla.',
    ],

    'table' => [
        'name' => 'Nombre',
        'title' => 'Título',
        'title_empty' => 'Sin título',
        'reach' => 'Alcance',
        'reach_record' => 'Registro #:id',
        'reach_any' => 'Cualquier modelo',
        'model_none' => 'Ningún modelo',
        'owned' => 'Solo lo propio',
        'narrow' => 'Acotar',
        'narrow_note' => 'Componer otra regla igual que esta, acotada a un registro',
        'empty' => 'El catálogo todavía no ha escrito ninguna habilidad',
        'empty_note' => 'Las escribe filament-bouncer:reconcile a partir de lo que declaran las Policies del panel.',
    ],

    'record' => [
        'name_empty' => 'Sin título',
        'model_none' => 'Ninguno',
        'record_all' => 'Todos',
    ],

    'probe' => [
        'label' => 'Probar',
        'heading' => 'Qué contesta el Gate',
        'note' => 'Sobre quién quieres preguntar. No se escribe nada: es la respuesta de Bouncer en este momento.',
        'close' => 'Cerrar',
        'holder' => 'Quién',
        'roles' => 'Roles',
        'accounts' => 'Cuentas',
        'record' => 'Sobre el registro',
        'record_note' => 'En blanco pregunta por el modelo entero. Una regla acotada a lo propio solo contesta que sí sobre un registro concreto.',
        'unaskable' => 'No se puede preguntar por esta regla: nombra un modelo que ya no se carga, así que el Gate contestaría que no por el motivo equivocado. Arréglalo antes de probarla.',
        'choose' => 'Elige un rol o una cuenta.',
        'yes' => 'Sí. El Gate contesta que puede.',
        'no' => 'No. El Gate contesta que no puede.',
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

    'health' => [
        'twin' => [
            'label' => 'Duplicada',
            'question' => '¿Hay otra fila igual?',
            'note' => 'Otra fila dice exactamente lo mismo. Se reparten y se retiran por separado: quien retire una creerá haber quitado la regla.',
            'yes' => 'Sí: la #:id dice exactamente lo mismo.',
            'no' => 'No: ninguna otra fila dice lo mismo.',
        ],
        'ghost-model' => [
            'label' => 'Modelo fantasma',
            'question' => '¿El modelo se carga?',
            'note' => 'El modelo del que habla ya no se puede cargar. Bouncer contesta que no, para siempre, sin avisar de que la clase no existe.',
            'yes' => 'Sí: :class.',
            'no' => 'No: «:class» ya no se puede cargar.',
            'none' => 'No habla de ningún modelo.',
            'any' => 'Habla de cualquier modelo.',
        ],
        'ghost-record' => [
            'label' => 'Registro fantasma',
            'question' => '¿El registro que acota existe?',
            'note' => 'Acota a un registro que ya se borró. Si ese id se reutiliza, la regla despierta apuntando a otra cosa.',
            'yes' => 'Sí: el registro #:id existe.',
            'no' => 'No: el registro #:id ya no está.',
            'none' => 'No acota a ningún registro.',
            'unknown' => 'No se puede saber: el modelo no se carga.',
        ],
        'invisible' => [
            'label' => 'Invisible',
            'question' => '¿La ve todo el sistema?',
            'note' => 'Tiene tenant: no la ve la rejilla de un rol y Bouncer no la responde. Esta pantalla es la única que la muestra.',
            'yes' => 'Sí: es global, así que la ve todo el sistema.',
            'no' => 'No: tiene el tenant :scope, y solo esta pantalla la muestra.',
        ],
        'heading' => 'Salud',
        'note' => 'Cuatro cosas que ninguna otra pantalla comprueba.',
        'column' => 'Salud',
        'clean' => 'Sin problemas',
        'widget' => [
            'heading' => 'Salud de las habilidades',
            'sound' => ':total habilidades, y ninguna con nada que revisar.',
            'ailing' => ':total habilidades, :ailing con algo que revisar.',
            'nothing' => 'Nada que revisar',
            'look' => 'Ver cuáles',
        ],
    ],

];
