<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * What the screens need of an authority, which is what Bouncer's own `HasRoles` trait
 * already gives them. It is written down because the trait carries no interface, so
 * without one every call to it reads as a call on a bare model.
 */
interface HoldsRoles
{
    /** @return MorphToMany<\Illuminate\Database\Eloquent\Model, \Illuminate\Database\Eloquent\Model, \Illuminate\Database\Eloquent\Relations\Pivot> */
    public function roles(): MorphToMany;

    /** @param  \Illuminate\Database\Eloquent\Model|string|array<int, mixed>  $roles */
    public function assign($roles): static;

    /** @param  \Illuminate\Database\Eloquent\Model|string|array<int, mixed>  $roles */
    public function retract($roles): static;
}
