<?php

declare(strict_types=1);

namespace Drupal\croct\Tests;

use Drupal\Core\Session\AccountInterface as Account;
use Drupal\croct\AccountIdentityResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccountIdentityResolver::class)]
#[TestDox('The Drupal account identity resolver')]
final class AccountIdentityResolverTest extends TestCase
{
    #[TestDox('Returns the identifier of the authenticated user.')]
    public function testReturnsUserId(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('isAnonymous')->willReturn(false);
        $account->method('id')->willReturn(42);

        self::assertSame('42', (new AccountIdentityResolver($account))->getUserId());
    }

    #[TestDox('Returns null for an anonymous visitor.')]
    public function testReturnsNullWhenAnonymous(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('isAnonymous')->willReturn(true);

        self::assertNull((new AccountIdentityResolver($account))->getUserId());
    }
}
