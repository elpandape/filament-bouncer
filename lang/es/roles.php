<?php

declare(strict_types=1);

return [

    'form' => [
        'role' => 'Rol',
        'name' => 'Nombre',
        'title' => 'Título',
        'abilities' => 'Habilidades',
        'description' => 'Solo se muestran las habilidades que tienes tú, porque son las únicas que puedes ceder — o retirar.',
        'empty' => 'No tienes ninguna habilidad propia, así que aquí no hay nada que ceder.',
        'inherited' => 'La tiene por una regla más amplia, no concedida aquí.',
        'overruled' => 'Concedida aquí, pero una regla más amplia la prohíbe.',
        'restricted_owned' => 'También tiene aquí una regla para solo lo suyo, que la rejilla no toca.',
        'restricted_records' => 'También tiene aquí una regla sobre un registro, que la rejilla no toca.|También tiene aquí reglas sobre :count registros, que la rejilla no toca.',
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

];
