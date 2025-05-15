<?php

namespace Tourze\CreditServiceContracts\Tests\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\CreditServiceContracts\CreditAccountInterface;
use Tourze\CreditServiceContracts\Exception\CreditServiceException;
use Tourze\CreditServiceContracts\Service\CreditAccountServiceInterface;
use Tourze\UserIDBundle\Contracts\AccountInterface;

class CreditAccountServiceInterfaceTest extends TestCase
{
    private CreditAccountServiceInterface $accountService;
    private UserInterface $mockUser;
    private AccountInterface $mockAccount;
    private CreditAccountInterface $mockCreditAccount;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 创建模拟的用户和账户对象
        $this->mockUser = $this->createMock(UserInterface::class);
        $this->mockAccount = $this->getMockBuilder(AccountInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getId'])
            ->getMockForAbstractClass();
        $this->mockAccount->method('getId')->willReturn('account-id-1');
        $this->mockAccount->method('getUser')->willReturn($this->mockUser);
        $this->mockAccount->method('getIdentities')->willReturn([]);
        
        // 创建模拟的积分账户
        $this->mockCreditAccount = $this->createMock(CreditAccountInterface::class);
        $this->mockCreditAccount->method('getId')->willReturn('credit-account-1');
        $this->mockCreditAccount->method('getUser')->willReturn($this->mockUser);
        $this->mockCreditAccount->method('getAccount')->willReturn($this->mockAccount);
        $this->mockCreditAccount->method('getCreditTypeId')->willReturn('type-1');
        $this->mockCreditAccount->method('getBalance')->willReturn(1000);
        $this->mockCreditAccount->method('getFrozenAmount')->willReturn(200);
        $this->mockCreditAccount->method('getAvailableBalance')->willReturn(800);
        
        // 创建模拟的积分账户服务
        $this->accountService = $this->createMock(CreditAccountServiceInterface::class);
    }

    /**
     * 测试获取用户积分账户
     */
    public function testGetAccount_returnsCorrectAccount()
    {
        $this->accountService
            ->method('getAccount')
            ->willReturnCallback(function ($user, $creditTypeId) {
                if ($user === $this->mockUser && $creditTypeId === 'type-1') {
                    return $this->mockCreditAccount;
                }
                throw new CreditServiceException('账户不存在');
            });
        
        // 测试正确获取账户
        $account = $this->accountService->getAccount($this->mockUser, 'type-1');
        $this->assertSame($this->mockCreditAccount, $account);
        $this->assertSame('credit-account-1', $account->getId());
        $this->assertSame($this->mockUser, $account->getUser());
        $this->assertSame('type-1', $account->getCreditTypeId());
        
        // 测试账户不存在时抛出异常
        $this->expectException(CreditServiceException::class);
        $this->accountService->getAccount($this->mockUser, 'non-existent-type');
    }

    /**
     * 测试获取或创建用户积分账户
     */
    public function testGetOrCreateAccount_returnsOrCreatesAccount()
    {
        $newCreditAccount = $this->createMock(CreditAccountInterface::class);
        $newCreditAccount->method('getId')->willReturn('credit-account-2');
        $newCreditAccount->method('getUser')->willReturn($this->mockUser);
        $newCreditAccount->method('getCreditTypeId')->willReturn('type-2');
        
        $this->accountService
            ->method('getOrCreateAccount')
            ->willReturnCallback(function ($user, $creditTypeId) use ($newCreditAccount) {
                if ($user === $this->mockUser) {
                    return match ($creditTypeId) {
                        'type-1' => $this->mockCreditAccount,
                        'type-2' => $newCreditAccount,
                        default => throw new CreditServiceException('创建账户失败'),
                    };
                }
                throw new CreditServiceException('创建账户失败');
            });
        
        // 测试获取已存在的账户
        $account = $this->accountService->getOrCreateAccount($this->mockUser, 'type-1');
        $this->assertSame($this->mockCreditAccount, $account);
        $this->assertSame('credit-account-1', $account->getId());
        
        // 测试创建新账户
        $newAccount = $this->accountService->getOrCreateAccount($this->mockUser, 'type-2');
        $this->assertSame($newCreditAccount, $newAccount);
        $this->assertSame('credit-account-2', $newAccount->getId());
        
        // 测试创建失败时抛出异常
        $this->expectException(CreditServiceException::class);
        $this->accountService->getOrCreateAccount($this->mockUser, 'invalid-type');
    }

    /**
     * 测试获取用户所有积分账户
     */
    public function testGetUserAccounts_returnsAllUserAccounts()
    {
        $secondCreditAccount = $this->createMock(CreditAccountInterface::class);
        $secondCreditAccount->method('getId')->willReturn('credit-account-2');
        $secondCreditAccount->method('getUser')->willReturn($this->mockUser);
        $secondCreditAccount->method('getCreditTypeId')->willReturn('type-2');
        
        $userAccounts = [$this->mockCreditAccount, $secondCreditAccount];
        
        $this->accountService
            ->method('getUserAccounts')
            ->willReturnCallback(function ($user) use ($userAccounts) {
                return $user === $this->mockUser ? $userAccounts : [];
            });
        
        // 测试获取用户所有账户
        $accounts = $this->accountService->getUserAccounts($this->mockUser);
        $this->assertCount(2, $accounts);
        $this->assertContains($this->mockCreditAccount, $accounts);
        $this->assertContains($secondCreditAccount, $accounts);
        
        // 测试用户不存在账户时返回空数组
        $anotherUser = $this->createMock(UserInterface::class);
        $this->assertEmpty($this->accountService->getUserAccounts($anotherUser));
    }

    /**
     * 测试根据ID获取账户
     */
    public function testGetAccountById_returnsCorrectAccount()
    {
        $this->accountService
            ->method('getAccountById')
            ->willReturnCallback(function ($account) {
                if ($account === $this->mockAccount) {
                    return $this->mockCreditAccount;
                }
                throw new CreditServiceException('账户不存在');
            });
        
        // 测试正确获取账户
        $account = $this->accountService->getAccountById($this->mockAccount);
        $this->assertSame($this->mockCreditAccount, $account);
        
        // 测试账户不存在时抛出异常
        $anotherAccount = $this->createMock(AccountInterface::class);
        $this->expectException(CreditServiceException::class);
        $this->accountService->getAccountById($anotherAccount);
    }

    /**
     * 测试使用悲观锁获取账户
     */
    public function testGetAccountWithLock_returnsLockedAccount()
    {
        $this->accountService
            ->method('getAccountWithLock')
            ->willReturnCallback(function ($user, $creditTypeId) {
                if ($user === $this->mockUser && $creditTypeId === 'type-1') {
                    return $this->mockCreditAccount;
                }
                throw new CreditServiceException('获取账户锁失败');
            });
        
        // 测试正确获取带锁的账户
        $account = $this->accountService->getAccountWithLock($this->mockUser, 'type-1');
        $this->assertSame($this->mockCreditAccount, $account);
        
        // 测试获取锁失败时抛出异常
        $this->expectException(CreditServiceException::class);
        $this->accountService->getAccountWithLock($this->mockUser, 'invalid-type');
    }

    /**
     * 测试释放账户锁
     */
    public function testReleaseLock_returnsCorrectStatus()
    {
        $this->accountService
            ->method('releaseLock')
            ->willReturnCallback(function ($accountId) {
                return $accountId === 'credit-account-1';
            });
        
        // 测试成功释放锁
        $this->assertTrue($this->accountService->releaseLock('credit-account-1'));
        
        // 测试释放不存在的锁
        $this->assertFalse($this->accountService->releaseLock('non-existent-account'));
    }

    /**
     * 测试更新账户信息
     */
    public function testUpdateAccount_returnsCorrectStatus()
    {
        $this->accountService
            ->method('updateAccount')
            ->willReturnCallback(function ($accountId, $data) {
                if ($accountId === 'credit-account-1') {
                    return true;
                }
                throw new CreditServiceException('账户不存在');
            });
        
        // 测试成功更新账户
        $updateData = ['remark' => '已更新备注'];
        $this->assertTrue($this->accountService->updateAccount('credit-account-1', $updateData));
        
        // 测试更新不存在的账户时抛出异常
        $this->expectException(CreditServiceException::class);
        $this->accountService->updateAccount('non-existent-account', $updateData);
    }

    /**
     * 测试使用乐观锁更新账户
     */
    public function testUpdateAccountWithVersion_handlesVersionConflict()
    {
        $this->accountService
            ->method('updateAccountWithVersion')
            ->willReturnCallback(function ($accountId, $data, $version) {
                if ($accountId === 'credit-account-1') {
                    if ($version === 1) {
                        return true;
                    }
                    throw new CreditServiceException('版本冲突');
                }
                throw new CreditServiceException('账户不存在');
            });
        
        // 测试正确版本号更新成功
        $updateData = ['balance' => 1500];
        $this->assertTrue($this->accountService->updateAccountWithVersion('credit-account-1', $updateData, 1));
        
        // 测试版本冲突时抛出异常
        $this->expectException(CreditServiceException::class);
        $this->accountService->updateAccountWithVersion('credit-account-1', $updateData, 2);
    }

    /**
     * 测试冻结积分
     */
    public function testFreezeCredits_freezesCorrectAmount()
    {
        $this->accountService
            ->method('freezeCredits')
            ->willReturnCallback(function ($accountId, $amount, $reason, $metadata) {
                if ($accountId === 'credit-account-1') {
                    if ($amount <= 800) { // 可用余额为800
                        return true;
                    }
                    throw new CreditServiceException('余额不足');
                }
                throw new CreditServiceException('账户不存在');
            });
        
        // 测试成功冻结积分
        $this->assertTrue($this->accountService->freezeCredits(
            'credit-account-1',
            500,
            '订单预留',
            ['order_id' => 'order-123']
        ));
        
        // 测试冻结金额超过可用余额时抛出异常
        $this->expectException(CreditServiceException::class);
        $this->accountService->freezeCredits(
            'credit-account-1',
            1000,
            '订单预留',
            ['order_id' => 'order-123']
        );
    }

    /**
     * 测试解冻积分
     */
    public function testUnfreezeCredits_unfreezesCorrectAmount()
    {
        $this->accountService
            ->method('unfreezeCredits')
            ->willReturnCallback(function ($accountId, $amount, $reason, $metadata) {
                if ($accountId === 'credit-account-1') {
                    if ($amount <= 200) { // 冻结余额为200
                        return true;
                    }
                    throw new CreditServiceException('冻结金额不足');
                }
                throw new CreditServiceException('账户不存在');
            });
        
        // 测试成功解冻积分
        $this->assertTrue($this->accountService->unfreezeCredits(
            'credit-account-1',
            150,
            '订单取消',
            ['order_id' => 'order-123']
        ));
        
        // 测试解冻金额超过已冻结金额时抛出异常
        $this->expectException(CreditServiceException::class);
        $this->accountService->unfreezeCredits(
            'credit-account-1',
            250,
            '订单取消',
            ['order_id' => 'order-123']
        );
    }

    /**
     * 测试校正账户余额
     */
    public function testCorrectBalance_correctsAccountBalance()
    {
        $this->accountService
            ->method('correctBalance')
            ->willReturnCallback(function ($accountId, $calculatedBalance, $reason) {
                if ($accountId === 'credit-account-1') {
                    return true;
                }
                throw new CreditServiceException('账户不存在');
            });
        
        // 测试成功校正余额
        $this->assertTrue($this->accountService->correctBalance(
            'credit-account-1',
            1200,
            '系统审计校正'
        ));
        
        // 测试校正不存在的账户时抛出异常
        $this->expectException(CreditServiceException::class);
        $this->accountService->correctBalance(
            'non-existent-account',
            1000,
            '系统审计校正'
        );
    }

    /**
     * 测试获取即将过期的积分
     */
    public function testGetExpiringCredits_returnsExpiringCredits()
    {
        $expiringCredits = [
            [
                'amount' => 100,
                'expiry_date' => new DateTimeImmutable('+15 days'),
                'transaction_id' => 'trans-123',
            ],
            [
                'amount' => 200,
                'expiry_date' => new DateTimeImmutable('+25 days'),
                'transaction_id' => 'trans-456',
            ],
        ];
        
        $this->accountService
            ->method('getExpiringCredits')
            ->willReturnCallback(function ($user, $creditTypeId, $daysThreshold) use ($expiringCredits) {
                if ($user === $this->mockUser && $creditTypeId === 'type-1') {
                    return $expiringCredits;
                }
                throw new CreditServiceException('账户不存在');
            });
        
        // 测试获取即将过期的积分
        $expiring = $this->accountService->getExpiringCredits($this->mockUser, 'type-1');
        $this->assertSame($expiringCredits, $expiring);
        $this->assertCount(2, $expiring);
        
        // 测试账户不存在时抛出异常
        $this->expectException(CreditServiceException::class);
        $this->accountService->getExpiringCredits($this->mockUser, 'invalid-type');
    }

    /**
     * 测试设置账户状态
     */
    public function testSetAccountStatus_setsCorrectStatus()
    {
        $this->accountService
            ->method('setAccountStatus')
            ->willReturnCallback(function ($accountId, $isActive, $reason) {
                if ($accountId === 'credit-account-1') {
                    return true;
                }
                throw new CreditServiceException('账户不存在');
            });
        
        // 测试成功设置账户状态
        $this->assertTrue($this->accountService->setAccountStatus('credit-account-1', false, '用户请求禁用'));
        
        // 测试设置不存在的账户状态时抛出异常
        $this->expectException(CreditServiceException::class);
        $this->accountService->setAccountStatus('non-existent-account', true, '激活账户');
    }
} 