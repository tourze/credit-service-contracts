<?php

namespace Tourze\CreditServiceContracts\Tests;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\CreditServiceContracts\CreditTransactionInterface;
use Tourze\CreditServiceContracts\Enum\CreditTransactionStatusEnum;
use Tourze\CreditServiceContracts\Enum\CreditTransactionTypeEnum;
use Tourze\UserIDBundle\Contracts\AccountInterface;

class CreditTransactionInterfaceTest extends TestCase
{
    private CreditTransactionInterface $transaction;
    private UserInterface $mockUser;
    private AccountInterface $mockAccount;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 创建模拟的用户和账户对象
        $this->mockUser = $this->createMock(UserInterface::class);
        $this->mockAccount = $this->createMock(AccountInterface::class);
        
        // 创建一个模拟的积分交易实现
        $this->transaction = $this->createMock(CreditTransactionInterface::class);
        
        // 设置通用预期行为
        $this->transaction->method('getId')->willReturn('trans-123');
        $this->transaction->method('getUser')->willReturn($this->mockUser);
        $this->transaction->method('getAccount')->willReturn($this->mockAccount);
        $this->transaction->method('getCreditTypeId')->willReturn('type-123');
        $this->transaction->method('getType')->willReturn(CreditTransactionTypeEnum::INCOME);
        $this->transaction->method('getAmount')->willReturn(100);
        $this->transaction->method('getBeforeBalance')->willReturn(500);
        $this->transaction->method('getAfterBalance')->willReturn(600);
        $this->transaction->method('getBusinessCode')->willReturn('TASK_COMPLETE');
        $this->transaction->method('getBusinessId')->willReturn('task-456');
        $this->transaction->method('getRemark')->willReturn('完成每日任务奖励');
        $this->transaction->method('getStatus')->willReturn(CreditTransactionStatusEnum::COMPLETED);
        $this->transaction->method('getOperatorId')->willReturn('op-789');
        $this->transaction->method('getBatchNo')->willReturn('batch-001');
        $this->transaction->method('getCreateTime')->willReturn(new DateTimeImmutable('2023-01-01 12:00:00'));
        $this->transaction->method('getCompleteTime')->willReturn(new DateTimeImmutable('2023-01-01 12:01:00'));
        $this->transaction->method('getExpiryTime')->willReturn(new DateTimeImmutable('2024-01-01 00:00:00'));
        $this->transaction->method('getIpAddress')->willReturn('192.168.1.1');
        $this->transaction->method('getSource')->willReturn('APP');
        $this->transaction->method('getDevice')->willReturn('iPhone 13');
        $this->transaction->method('getExtraData')->willReturn(['promotion_id' => 'promo-001']);
    }

    /**
     * 测试getId方法返回正确的交易ID
     */
    public function testGetId_returnsCorrectTransactionId()
    {
        $this->assertSame('trans-123', $this->transaction->getId());
    }

    /**
     * 测试getUser方法返回正确的用户对象
     */
    public function testGetUser_returnsCorrectUserObject()
    {
        $this->assertSame($this->mockUser, $this->transaction->getUser());
    }

    /**
     * 测试getAccount方法返回正确的账户对象
     */
    public function testGetAccount_returnsCorrectAccountObject()
    {
        $this->assertSame($this->mockAccount, $this->transaction->getAccount());
    }

    /**
     * 测试getCreditTypeId方法返回正确的积分类型ID
     */
    public function testGetCreditTypeId_returnsCorrectTypeId()
    {
        $this->assertSame('type-123', $this->transaction->getCreditTypeId());
    }

    /**
     * 测试getType方法返回正确的交易类型
     */
    public function testGetType_returnsCorrectTransactionType()
    {
        $this->assertSame(CreditTransactionTypeEnum::INCOME, $this->transaction->getType());
        $this->assertSame(1, $this->transaction->getType()->value);
        $this->assertSame('收入', $this->transaction->getType()->getLabel());
    }

    /**
     * 测试getAmount方法返回正确的交易金额
     */
    public function testGetAmount_returnsCorrectAmount()
    {
        $this->assertSame(100, $this->transaction->getAmount());
    }

    /**
     * 测试getBeforeBalance方法返回正确的交易前余额
     */
    public function testGetBeforeBalance_returnsCorrectBeforeBalance()
    {
        $this->assertSame(500, $this->transaction->getBeforeBalance());
    }

    /**
     * 测试getAfterBalance方法返回正确的交易后余额
     */
    public function testGetAfterBalance_returnsCorrectAfterBalance()
    {
        $this->assertSame(600, $this->transaction->getAfterBalance());
        
        // 验证交易后余额是否等于交易前余额加上交易金额（对于收入类型）
        $this->assertEquals(
            $this->transaction->getBeforeBalance() + $this->transaction->getAmount(),
            $this->transaction->getAfterBalance()
        );
    }

    /**
     * 测试getBusinessCode方法返回正确的业务码
     */
    public function testGetBusinessCode_returnsCorrectBusinessCode()
    {
        $this->assertSame('TASK_COMPLETE', $this->transaction->getBusinessCode());
    }

    /**
     * 测试getBusinessId方法返回正确的业务ID
     */
    public function testGetBusinessId_returnsCorrectBusinessId()
    {
        $this->assertSame('task-456', $this->transaction->getBusinessId());
    }

    /**
     * 测试getRemark方法返回正确的备注
     */
    public function testGetRemark_returnsCorrectRemark()
    {
        $this->assertSame('完成每日任务奖励', $this->transaction->getRemark());
    }

    /**
     * 测试getStatus方法返回正确的交易状态
     */
    public function testGetStatus_returnsCorrectStatus()
    {
        $this->assertSame(CreditTransactionStatusEnum::COMPLETED, $this->transaction->getStatus());
        $this->assertSame(1, $this->transaction->getStatus()->value);
        $this->assertSame('已完成', $this->transaction->getStatus()->getLabel());
    }

    /**
     * 测试getOperatorId方法返回正确的操作者ID
     */
    public function testGetOperatorId_returnsCorrectOperatorId()
    {
        $this->assertSame('op-789', $this->transaction->getOperatorId());
    }

    /**
     * 测试getBatchNo方法返回正确的批次号
     */
    public function testGetBatchNo_returnsCorrectBatchNo()
    {
        $this->assertSame('batch-001', $this->transaction->getBatchNo());
    }

    /**
     * 测试getCreateTime方法返回正确的创建时间
     */
    public function testGetCreateTime_returnsCorrectCreateTime()
    {
        $createTime = $this->transaction->getCreateTime();
        $this->assertInstanceOf(DateTimeInterface::class, $createTime);
        $this->assertEquals('2023-01-01 12:00:00', $createTime->format('Y-m-d H:i:s'));
    }

    /**
     * 测试getCompleteTime方法返回正确的完成时间
     */
    public function testGetCompleteTime_returnsCorrectCompleteTime()
    {
        $completeTime = $this->transaction->getCompleteTime();
        $this->assertInstanceOf(DateTimeInterface::class, $completeTime);
        $this->assertEquals('2023-01-01 12:01:00', $completeTime->format('Y-m-d H:i:s'));
    }

    /**
     * 测试getExpiryTime方法返回正确的过期时间
     */
    public function testGetExpiryTime_returnsCorrectExpiryTime()
    {
        $expiryTime = $this->transaction->getExpiryTime();
        $this->assertInstanceOf(DateTimeInterface::class, $expiryTime);
        $this->assertEquals('2024-01-01 00:00:00', $expiryTime->format('Y-m-d H:i:s'));
    }

    /**
     * 测试getIpAddress方法返回正确的IP地址
     */
    public function testGetIpAddress_returnsCorrectIpAddress()
    {
        $this->assertSame('192.168.1.1', $this->transaction->getIpAddress());
    }

    /**
     * 测试getSource方法返回正确的来源
     */
    public function testGetSource_returnsCorrectSource()
    {
        $this->assertSame('APP', $this->transaction->getSource());
    }

    /**
     * 测试getDevice方法返回正确的设备信息
     */
    public function testGetDevice_returnsCorrectDevice()
    {
        $this->assertSame('iPhone 13', $this->transaction->getDevice());
    }

    /**
     * 测试getExtraData方法返回正确的额外数据
     */
    public function testGetExtraData_returnsCorrectExtraData()
    {
        $expectedExtraData = ['promotion_id' => 'promo-001'];
        $this->assertSame($expectedExtraData, $this->transaction->getExtraData());
    }

    /**
     * 测试可选字段为null的情况
     */
    public function testOptionalFields_canBeNull()
    {
        $transactionWithNullFields = $this->createMock(CreditTransactionInterface::class);
        $transactionWithNullFields->method('getBusinessId')->willReturn(null);
        $transactionWithNullFields->method('getRemark')->willReturn(null);
        $transactionWithNullFields->method('getOperatorId')->willReturn(null);
        $transactionWithNullFields->method('getBatchNo')->willReturn(null);
        $transactionWithNullFields->method('getCompleteTime')->willReturn(null);
        $transactionWithNullFields->method('getExpiryTime')->willReturn(null);
        $transactionWithNullFields->method('getIpAddress')->willReturn(null);
        $transactionWithNullFields->method('getSource')->willReturn(null);
        $transactionWithNullFields->method('getDevice')->willReturn(null);
        
        $this->assertNull($transactionWithNullFields->getBusinessId());
        $this->assertNull($transactionWithNullFields->getRemark());
        $this->assertNull($transactionWithNullFields->getOperatorId());
        $this->assertNull($transactionWithNullFields->getBatchNo());
        $this->assertNull($transactionWithNullFields->getCompleteTime());
        $this->assertNull($transactionWithNullFields->getExpiryTime());
        $this->assertNull($transactionWithNullFields->getIpAddress());
        $this->assertNull($transactionWithNullFields->getSource());
        $this->assertNull($transactionWithNullFields->getDevice());
    }

    /**
     * 测试支出类型交易
     */
    public function testExpenseTransaction_hasCorrectBalanceCalculation()
    {
        $expenseTransaction = $this->createMock(CreditTransactionInterface::class);
        $expenseTransaction->method('getType')->willReturn(CreditTransactionTypeEnum::EXPENSE);
        $expenseTransaction->method('getAmount')->willReturn(50);
        $expenseTransaction->method('getBeforeBalance')->willReturn(200);
        $expenseTransaction->method('getAfterBalance')->willReturn(150);
        
        $this->assertSame(CreditTransactionTypeEnum::EXPENSE, $expenseTransaction->getType());
        $this->assertSame(50, $expenseTransaction->getAmount());
        $this->assertSame(200, $expenseTransaction->getBeforeBalance());
        $this->assertSame(150, $expenseTransaction->getAfterBalance());
        
        // 验证交易后余额是否等于交易前余额减去交易金额（对于支出类型）
        $this->assertEquals(
            $expenseTransaction->getBeforeBalance() - $expenseTransaction->getAmount(),
            $expenseTransaction->getAfterBalance()
        );
    }

    /**
     * 测试冻结类型交易
     */
    public function testFrozenTransaction_hasCorrectType()
    {
        $frozenTransaction = $this->createMock(CreditTransactionInterface::class);
        $frozenTransaction->method('getType')->willReturn(CreditTransactionTypeEnum::FROZEN);
        
        $this->assertSame(CreditTransactionTypeEnum::FROZEN, $frozenTransaction->getType());
        $this->assertSame(3, $frozenTransaction->getType()->value);
        $this->assertSame('冻结', $frozenTransaction->getType()->getLabel());
    }

    /**
     * 测试不同状态的交易
     */
    public function testDifferentStatusTransactions()
    {
        $pendingTransaction = $this->createMock(CreditTransactionInterface::class);
        $pendingTransaction->method('getStatus')->willReturn(CreditTransactionStatusEnum::PENDING);
        $pendingTransaction->method('getCompleteTime')->willReturn(null);
        
        $failedTransaction = $this->createMock(CreditTransactionInterface::class);
        $failedTransaction->method('getStatus')->willReturn(CreditTransactionStatusEnum::FAILED);
        
        $cancelledTransaction = $this->createMock(CreditTransactionInterface::class);
        $cancelledTransaction->method('getStatus')->willReturn(CreditTransactionStatusEnum::CANCELLED);
        
        $this->assertSame(CreditTransactionStatusEnum::PENDING, $pendingTransaction->getStatus());
        $this->assertSame(0, $pendingTransaction->getStatus()->value);
        $this->assertSame('待处理', $pendingTransaction->getStatus()->getLabel());
        $this->assertNull($pendingTransaction->getCompleteTime());
        
        $this->assertSame(CreditTransactionStatusEnum::FAILED, $failedTransaction->getStatus());
        $this->assertSame(2, $failedTransaction->getStatus()->value);
        $this->assertSame('失败', $failedTransaction->getStatus()->getLabel());
        
        $this->assertSame(CreditTransactionStatusEnum::CANCELLED, $cancelledTransaction->getStatus());
        $this->assertSame(3, $cancelledTransaction->getStatus()->value);
        $this->assertSame('已取消', $cancelledTransaction->getStatus()->getLabel());
    }
} 