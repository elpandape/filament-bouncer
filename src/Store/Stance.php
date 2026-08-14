<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

/**
 * What a role says about an ability.
 *
 * The middle case is not a quieter way of saying no: neutral leaves the answer to whatever else
 * the person holds, while forbidding overrules all of it — including a grant from another role and
 * one made straight to the user.
 */
enum Stance: string
{
    case Granted = 'granted';

    case Neutral = 'neutral';

    case Forbidden = 'forbidden';
}
