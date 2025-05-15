<?php

namespace Tourze\CreditServiceContracts\Tests\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\CreditServiceContracts\CreditAccountInterface;
use Tourze\CreditServiceContracts\CreditTransactionInterface;
use Tourze\CreditServiceContracts\Enum\CreditTransactionStatusEnum;
use Tourze\CreditServiceContracts\Enum\CreditTransactionTypeEnum;
use Tourze\CreditServiceContracts\Exception\CreditServiceException;
use Tourze\CreditServiceContracts\Service\CreditAccountServiceInterface;
use Tourze\CreditServiceContracts\Service\CreditOperationServiceInterface;
use Tourze\UserIDBundle\Contracts\AccountInterface;

class CreditOperationServiceInterfaceTest extends TestCase
{
    private CreditOperationServiceInterface $operationService;
    private UserInterface $mockUser;
    private AccountInterface $mockAccount;
    private CreditAccountInterface $mockCreditAccount;
    private CreditAccountServiceInterface $mockAccountService;
    private CreditTransactionInterface $mockTransaction;

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
        
        // 创建模拟的账户服务
        $this->mockAccountService = $this->createMock(CreditAccountServiceInterface::class);
        $this->mockAccountService->method('getAccountById')->willReturn($this->mockCreditAccount);
        
        // 创建模拟的交易记录
        $this->mockTransaction = $this->createMock(CreditTransactionInterface::class);
        $this->mockTransaction->method('getId')->willReturn('trans-123');
        $this->mockTransaction->method('getAccount')->willReturn($this->mockAccount);
        $this->mockTransaction->method('getAmount')->willReturn(100);
        $this->mockTransaction->method('getStatus')->willReturn(CreditTransactionStatusEnum::COMPLETED);
        
        // 创建模拟的积分操作服务
        $this->operationService = $this->createMock(CreditOperationServiceInterface::class);
    }

    /**
     * 测试增加积分
     */
    public function testAddCredits_addsCorrectAmount()
    {
        $incomeTransaction = $this->createMock(CreditTransactionInterface::class);
        $incomeTransaction->method('getId')->willReturn('trans-income-123');
        $incomeTransaction->method('getAccount')->willReturn($this->mockAccount);
        $incomeTransaction->method('getAmount')->willReturn(100);
        $incomeTransaction->method('getType')->willReturn(CreditTransactionTypeEnum::INCOME);
        $incomeTransaction->method('getStatus')->willReturn(CreditTransactionStatusEnum::COMPLETED);
        $incomeTransaction->method('getBusinessCode')->willReturn('TASK_COMPLETE');
        $incomeTransaction->method('getBusinessId')->willReturn('task-123');
        $incomeTransaction->method('getRemark')->willReturn('完成任务奖励');
        
        $this->operationService
            ->method('addCredits')
            ->willReturnCallback(function ($account, $amount, $businessCode, $businessId, $remark, $extraData) use ($incomeTransaction) {
                if ($account === $this->mockAccount && $amount === 100 && $businessCode === 'TASK_COMPLETE') {
                    return $incomeTransaction;
                }
                throw new CreditServiceException('增加积分失败');
            });
        
        // 测试正确增加积分
        $transaction = $this->operationService->addCredits(
            $this->mockAccount,
            100,
            'TASK_COMPLETE',
            'task-123',
            '完成任务奖励'
        );
        
        $this->assertSame($incomeTransaction, $transaction);
        $this->assertSame('trans-income-123', $transaction->getId());
        $this->assertSame($this->mockAccount, $transaction->getAccount());
        $this->assertSame(100, $transaction->getAmount());
        $this->assertSame(CreditTransactionTypeEnum::INCOME, $transaction->getType());
        $this->assertSame(CreditTransactionStatusEnum::COMPLETED, $transaction->getStatus());
        $this->assertSame('TASK_COMPLETE', $transaction->getBusinessCode());
        $this->assertSame('task-123', $transaction->getBusinessId());
        $this->assertSame('完成任务奖励', $transaction->getRemark());
    }

    /**
     * 测试扣减积分
     */
    public function testDeductCredits_deductsCorrectAmount()
    {
        $expenseTransaction = $this->createMock(CreditTransactionInterface::class);
        $expenseTransaction->method('getId')->willReturn('trans-expense-123');
        $expenseTransaction->method('getAccount')->willReturn($this->mockAccount);
        $expenseTransaction->method('getAmount')->willReturn(50);
        $expenseTransaction->method('getType')->willReturn(CreditTransactionTypeEnum::EXPENSE);
        $expenseTransaction->method('getStatus')->willReturn(CreditTransactionStatusEnum::COMPLETED);
        $expenseTransaction->method('getBusinessCode')->willReturn('GOODS_PURCHASE');
        $expenseTransaction->method('getBusinessId')->willReturn('order-123');
        $expenseTransaction->method('getRemark')->willReturn('商品购买');
        
        $this->operationService
            ->method('deductCredits')
            ->willReturnCallback(function ($account, $amount, $businessCode, $businessId, $remark, $extraData) use ($expenseTransaction) {
                if ($account === $this->mockAccount && $amount === 50 && $businessCode === 'GOODS_PURCHASE') {
                    return $expenseTransaction;
                }
                throw new CreditServiceException('扣减积分失败');
            });
        
        // 测试正确扣减积分
        $transaction = $this->operationService->deductCredits(
            $this->mockAccount,
            50,
            'GOODS_PURCHASE',
            'order-123',
            '商品购买'
        );
        
        $this->assertSame($expenseTransaction, $transaction);
        $this->assertSame('trans-expense-123', $transaction->getId());
        $this->assertSame($this->mockAccount, $transaction->getAccount());
        $this->assertSame(50, $transaction->getAmount());
        $this->assertSame(CreditTransactionTypeEnum::EXPENSE, $transaction->getType());
        $this->assertSame(CreditTransactionStatusEnum::COMPLETED, $transaction->getStatus());
        $this->assertSame('GOODS_PURCHASE', $transaction->getBusinessCode());
        $this->assertSame('order-123', $transaction->getBusinessId());
        $this->assertSame('商品购买', $transaction->getRemark());
    }

    /**
     * 测试冻结积分
     */
    public function testFreezeCredits_freezesCorrectAmount()
    {
        $frozenTransaction = $this->createMock(CreditTransactionInterface::class);
        $frozenTransaction->method('getId')->willReturn('trans-frozen-123');
        $frozenTransaction->method('getAccount')->willReturn($this->mockAccount);
        $frozenTransaction->method('getAmount')->willReturn(200);
        $frozenTransaction->method('getType')->willReturn(CreditTransactionTypeEnum::FROZEN);
        $frozenTransaction->method('getStatus')->willReturn(CreditTransactionStatusEnum::COMPLETED);
        $frozenTransaction->method('getBusinessCode')->willReturn('ORDER_PENDING');
        $frozenTransaction->method('getBusinessId')->willReturn('order-456');
        $frozenTransaction->method('getRemark')->willReturn('订单预留');
        
        $this->operationService
            ->method('freezeCredits')
            ->willReturnCallback(function ($account, $amount, $businessCode, $businessId, $remark, $extraData) use ($frozenTransaction) {
                if ($account === $this->mockAccount && $amount === 200 && $businessCode === 'ORDER_PENDING') {
                    return $frozenTransaction;
                }
                throw new CreditServiceException('冻结积分失败');
            });
        
        // 测试正确冻结积分
        $transaction = $this->operationService->freezeCredits(
            $this->mockAccount,
            200,
            'ORDER_PENDING',
            'order-456',
            '订单预留'
        );
        
        $this->assertSame($frozenTransaction, $transaction);
        $this->assertSame('trans-frozen-123', $transaction->getId());
        $this->assertSame($this->mockAccount, $transaction->getAccount());
        $this->assertSame(200, $transaction->getAmount());
        $this->assertSame(CreditTransactionTypeEnum::FROZEN, $transaction->getType());
        $this->assertSame(CreditTransactionStatusEnum::COMPLETED, $transaction->getStatus());
        $this->assertSame('ORDER_PENDING', $transaction->getBusinessCode());
        $this->assertSame('order-456', $transaction->getBusinessId());
        $this->assertSame('订单预留', $transaction->getRemark());
    }

    /**
     * 测试解冻积分
     */
    public function testUnfreezeCredits_unfreezesCorrectAmount()
    {
        $unfrozenTransaction = $this->createMock(CreditTransactionInterface::class);
        $unfrozenTransaction->method('getId')->willReturn('trans-unfrozen-123');
        $unfrozenTransaction->method('getAccount')->willReturn($this->mockAccount);
        $unfrozenTransaction->method('getAmount')->willReturn(150);
        $unfrozenTransaction->method('getType')->willReturn(CreditTransactionTypeEnum::UNFROZEN);
        $unfrozenTransaction->method('getStatus')->willReturn(CreditTransactionStatusEnum::COMPLETED);
        $unfrozenTransaction->method('getBusinessCode')->willReturn('ORDER_CANCEL');
        $unfrozenTransaction->method('getBusinessId')->willReturn('order-456');
        $unfrozenTransaction->method('getRemark')->willReturn('订单取消解冻');
        
        $this->operationService
            ->method('unfreezeCredits')
            ->willReturnCallback(function ($account, $amount, $businessCode, $businessId, $remark, $extraData) use ($unfrozenTransaction) {
                if ($account === $this->mockAccount && $amount === 150 && $businessCode === 'ORDER_CANCEL') {
                    return $unfrozenTransaction;
                }
                throw new CreditServiceException('解冻积分失败');
            });
        
        // 测试正确解冻积分
        $transaction = $this->operationService->unfreezeCredits(
            $this->mockAccount,
            150,
            'ORDER_CANCEL',
            'order-456',
            '订单取消解冻'
        );
        
        $this->assertSame($unfrozenTransaction, $transaction);
        $this->assertSame('trans-unfrozen-123', $transaction->getId());
        $this->assertSame($this->mockAccount, $transaction->getAccount());
        $this->assertSame(150, $transaction->getAmount());
        $this->assertSame(CreditTransactionTypeEnum::UNFROZEN, $transaction->getType());
        $this->assertSame(CreditTransactionStatusEnum::COMPLETED, $transaction->getStatus());
        $this->assertSame('ORDER_CANCEL', $transaction->getBusinessCode());
        $this->assertSame('order-456', $transaction->getBusinessId());
        $this->assertSame('订单取消解冻', $transaction->getRemark());
    }

    /**
     * 测试积分过期
     */
    public function testExpireCredits_expiresCorrectAmount()
    {
        $expiredTransaction = $this->createMock(CreditTransactionInterface::class);
        $expiredTransaction->method('getId')->willReturn('trans-expired-123');
        $expiredTransaction->method('getAccount')->willReturn($this->mockAccount);
        $expiredTransaction->method('getAmount')->willReturn(100);
        $expiredTransaction->method('getType')->willReturn(CreditTransactionTypeEnum::EXPIRED);
        $expiredTransaction->method('getStatus')->willReturn(CreditTransactionStatusEnum::COMPLETED);
        $expiredTransaction->method('getBusinessCode')->willReturn('CREDITS_EXPIRED');
        $expiredTransaction->method('getRemark')->willReturn('积分过期');
        
        $this->operationService
            ->method('expireCredits')
            ->willReturnCallback(function ($account, $amount, $businessCode, $businessId, $remark, $extraData) use ($expiredTransaction) {
                if ($account === $this->mockAccount && $amount === 100 && $businessCode === 'CREDITS_EXPIRED') {
                    return $expiredTransaction;
                }
                throw new CreditServiceException('积分过期处理失败');
            });
        
        // 测试正确处理积分过期
        $transaction = $this->operationService->expireCredits(
            $this->mockAccount,
            100,
            'CREDITS_EXPIRED',
            null,
            '积分过期'
        );
        
        $this->assertSame($expiredTransaction, $transaction);
        $this->assertSame('trans-expired-123', $transaction->getId());
        $this->assertSame($this->mockAccount, $transaction->getAccount());
        $this->assertSame(100, $transaction->getAmount());
        $this->assertSame(CreditTransactionTypeEnum::EXPIRED, $transaction->getType());
        $this->assertSame(CreditTransactionStatusEnum::COMPLETED, $transaction->getStatus());
        $this->assertSame('CREDITS_EXPIRED', $transaction->getBusinessCode());
        $this->assertSame('积分过期', $transaction->getRemark());
    }

    /**
     * 测试批量增加积分
     */
    public function testBatchAddCredits_addsToMultipleAccounts()
    {
        $secondAccount = $this->getMockBuilder(AccountInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getId'])
            ->getMockForAbstractClass();
        $secondAccount->method('getId')->willReturn('account-id-2');
        $secondAccount->method('getUser')->willReturn($this->mockUser);
        $secondAccount->method('getIdentities')->willReturn([]);
        
        $transaction1 = $this->createMock(CreditTransactionInterface::class);
        $transaction1->method('getId')->willReturn('trans-1');
        $transaction1->method('getAccount')->willReturn($this->mockAccount);
        $transaction1->method('getAmount')->willReturn(50);
        
        $transaction2 = $this->createMock(CreditTransactionInterface::class);
        $transaction2->method('getId')->willReturn('trans-2');
        $transaction2->method('getAccount')->willReturn($secondAccount);
        $transaction2->method('getAmount')->willReturn(50);
        
        $transactions = [$transaction1, $transaction2];
        $accounts = [$this->mockAccount, $secondAccount];
        
        $this->operationService
            ->method('batchAddCredits')
            ->willReturnCallback(function ($batchAccounts, $amount, $businessCode, $businessId, $remark, $extraData) use ($transactions, $accounts) {
                if ($batchAccounts === $accounts && $amount === 50 && $businessCode === 'SYSTEM_REWARD') {
                    return $transactions;
                }
                return [];
            });
        
        // 测试批量增加积分
        $results = $this->operationService->batchAddCredits(
            $accounts,
            50,
            'SYSTEM_REWARD',
            null,
            '系统奖励'
        );
        
        $this->assertCount(2, $results);
        $this->assertContains($transaction1, $results);
        $this->assertContains($transaction2, $results);
        $this->assertSame('trans-1', $results[0]->getId());
        $this->assertSame('trans-2', $results[1]->getId());
        $this->assertSame(50, $results[0]->getAmount());
        $this->assertSame(50, $results[1]->getAmount());
    }

    /**
     * 测试批量扣减积分
     */
    public function testBatchDeductCredits_deductsFromMultipleAccounts()
    {
        $secondAccount = $this->getMockBuilder(AccountInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getId'])
            ->getMockForAbstractClass();
        $secondAccount->method('getId')->willReturn('account-id-2');
        $secondAccount->method('getUser')->willReturn($this->mockUser);
        $secondAccount->method('getIdentities')->willReturn([]);
        
        $transaction1 = $this->createMock(CreditTransactionInterface::class);
        $transaction1->method('getId')->willReturn('trans-deduct-1');
        $transaction1->method('getAccount')->willReturn($this->mockAccount);
        $transaction1->method('getAmount')->willReturn(30);
        $transaction1->method('getType')->willReturn(CreditTransactionTypeEnum::EXPENSE);
        
        $transaction2 = $this->createMock(CreditTransactionInterface::class);
        $transaction2->method('getId')->willReturn('trans-deduct-2');
        $transaction2->method('getAccount')->willReturn($secondAccount);
        $transaction2->method('getAmount')->willReturn(30);
        $transaction2->method('getType')->willReturn(CreditTransactionTypeEnum::EXPENSE);
        
        $transactions = [$transaction1, $transaction2];
        $accounts = [$this->mockAccount, $secondAccount];
        
        $this->operationService
            ->method('batchDeductCredits')
            ->willReturnCallback(function ($batchAccounts, $amount, $businessCode, $businessId, $remark, $extraData) use ($transactions, $accounts) {
                if ($batchAccounts === $accounts && $amount === 30 && $businessCode === 'SYSTEM_FEE') {
                    return $transactions;
                }
                return [];
            });
        
        // 测试批量扣减积分
        $results = $this->operationService->batchDeductCredits(
            $accounts,
            30,
            'SYSTEM_FEE',
            null,
            '系统服务费'
        );
        
        $this->assertCount(2, $results);
        $this->assertContains($transaction1, $results);
        $this->assertContains($transaction2, $results);
        $this->assertSame('trans-deduct-1', $results[0]->getId());
        $this->assertSame('trans-deduct-2', $results[1]->getId());
        $this->assertSame(30, $results[0]->getAmount());
        $this->assertSame(30, $results[1]->getAmount());
        $this->assertSame(CreditTransactionTypeEnum::EXPENSE, $results[0]->getType());
        $this->assertSame(CreditTransactionTypeEnum::EXPENSE, $results[1]->getType());
    }

    /**
     * 测试检查账户余额是否足够
     */
    public function testHasEnoughCredits_checksBalanceCorrectly()
    {
        $this->operationService
            ->method('hasEnoughCredits')
            ->willReturnCallback(function ($user, $creditTypeId, $amount) {
                if ($user === $this->mockUser && $creditTypeId === 'type-1') {
                    return $amount <= 800; // 可用余额为800
                }
                return false;
            });
        
        // 测试余额足够的情况
        $this->assertTrue($this->operationService->hasEnoughCredits($this->mockUser, 'type-1', 500));
        $this->assertTrue($this->operationService->hasEnoughCredits($this->mockUser, 'type-1', 800));
        
        // 测试余额不足的情况
        $this->assertFalse($this->operationService->hasEnoughCredits($this->mockUser, 'type-1', 801));
        $this->assertFalse($this->operationService->hasEnoughCredits($this->mockUser, 'type-1', 1000));
        
        // 测试积分类型不存在的情况
        $this->assertFalse($this->operationService->hasEnoughCredits($this->mockUser, 'non-existent-type', 100));
    }

    /**
     * 测试负数金额的处理
     */
    public function testNegativeAmount_throwsException()
    {
        $this->operationService
            ->method('addCredits')
            ->willReturnCallback(function ($account, $amount, $businessCode, $businessId, $remark, $extraData) {
                if ($amount < 0) {
                    throw new CreditServiceException('金额不能为负数');
                }
                return $this->mockTransaction;
            });
        
        $this->expectException(CreditServiceException::class);
        $this->expectExceptionMessage('金额不能为负数');
        
        $this->operationService->addCredits(
            $this->mockAccount,
            -100,
            'NEGATIVE_TEST',
            null,
            '负数测试'
        );
    }

    /**
     * 测试零金额的处理
     */
    public function testZeroAmount_throwsException()
    {
        $this->operationService
            ->method('deductCredits')
            ->willReturnCallback(function ($account, $amount, $businessCode, $businessId, $remark, $extraData) {
                if ($amount <= 0) {
                    throw new CreditServiceException('金额必须大于零');
                }
                return $this->mockTransaction;
            });
        
        $this->expectException(CreditServiceException::class);
        $this->expectExceptionMessage('金额必须大于零');
        
        $this->operationService->deductCredits(
            $this->mockAccount,
            0,
            'ZERO_TEST',
            null,
            '零金额测试'
        );
    }
} 