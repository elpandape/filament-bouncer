<?php

declare(strict_types=1);

return [

    'created' => 'Creada :count habilidad.|Creadas :count habilidades.',
    'deleted' => 'Eliminada :count habilidad, y con ella cada concesión que la señalaba.|Eliminadas :count habilidades, y con ellas cada concesión que las señalaba.',
    'kept' => 'Se deja en pie :count habilidad que el catálogo ya no declara. Pasa --prune para borrarla.|Se dejan en pie :count habilidades que el catálogo ya no declara. Pasa --prune para borrarlas.',
    'matches' => 'La tienda coincide con el catálogo.',
    'missing' => 'Faltan en la tienda',
    'extra' => 'Guardadas, pero ya no declaradas',
    'open' => 'Abiertas a cualquiera, porque su modelo no tiene política',
    'privileged' => 'El rol privilegiado [:name] no existe, o ha perdido el comodín.',
    'policies' => [
        'written' => 'escrita',
        'kept' => 'ya estaba',
        'none' => 'Todos los recursos del panel tienen ya su política.',
        'next' => 'Ejecuta filament-bouncer:reconcile para que existan las habilidades que declaran estas políticas.',
    ],

];
