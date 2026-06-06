<?php

declare(strict_types=1);

namespace Drupal\croct;

use Croct\Plug\IdentityResolver;
use Drupal\Core\Session\AccountInterface;

/**
 * Resolves the user identity from the Drupal current user.
 */
final class AccountIdentityResolver implements IdentityResolver
{
    private AccountInterface $account;

    public function __construct(AccountInterface $account)
    {
        $this->account = $account;
    }

    public function getUserId(): ?string
    {
        return $this->account->isAnonymous() ? null : (string) $this->account->id();
    }
}
