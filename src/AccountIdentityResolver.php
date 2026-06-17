<?php

declare(strict_types=1);

namespace Drupal\croct;

use Croct\Plug\IdentityResolver;
use Drupal\Core\Session\AccountInterface as Account;

/**
 * Resolves the user identity from the Drupal current user.
 */
final class AccountIdentityResolver implements IdentityResolver
{
    private Account $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    public function getUserId(): ?string
    {
        return $this->account->isAnonymous() ? null : (string) $this->account->id();
    }
}
