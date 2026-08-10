<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Policies;

use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Bouncer;

/**
 * What a generated policy is built on.
 *
 * It carries no actions of its own, and that is the point: the methods a policy declares
 * are exactly the abilities the catalogue offers for that model, so the file is the
 * declaration. A base class quietly supplying twelve of them would put restoring and
 * force deleting in front of an administrator for a model that has neither.
 */
abstract class AbilityPolicy
{
    public function __construct(private readonly Bouncer $bouncer) {}

    /**
     * Asks Bouncer's clipboard rather than the Gate.
     *
     * Going through the Gate would resolve this very policy and ask it the same
     * question, and the question would never end.
     */
    protected function allows(Model $authority, string $action, Model|string $subject): bool
    {
        return $this->bouncer->getClipboard()->check($authority, $action, $subject);
    }
}
