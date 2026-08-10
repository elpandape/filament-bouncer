<?php

declare(strict_types=1);

return [

    /*
     * Cómo se llama el recurso de roles dentro del panel. Son decisiones del consumidor,
     * no del paquete: el grupo puede llamarse de otro modo, el orden depende de qué más
     * tenga ese panel, y el icono de la familia que ese proyecto ya use.
     */
    'navigation' => [
        'icon' => null,
        'group' => null,
        'sort' => null,
        'slug' => 'security/roles',
    ],

];
