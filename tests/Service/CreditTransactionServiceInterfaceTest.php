<?php

namespace Tourze\CreditServiceContracts\Tests\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\CreditServiceContracts\CreditTransactionInterface;
use Tourze\CreditServiceContracts\Enum\CreditTransactionStatusEnum;
use Tourze\CreditServiceContracts\Enum\CreditTransactionTypeEnum;
use Tourze\CreditServiceContracts\Exception\CreditServiceException;
use Tourze\CreditServiceContracts\Service\CreditTransactionServiceInterface;
use Tourze\UserIDBundle\Contracts\AccountInterface;

class CreditTransactionServiceInterfaceTest extends TestCase
{
    private CreditTransactionServiceInterface $transactionService;
    private UserInterface $mockUser;
    private AccountInterface $mockAccount;
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
        
        // 创建模拟的交易记录
        $this->mockTransaction = $this->createMock(CreditTransactionInterface::class);
        $this->mockTransaction->method('getId')->willReturn('trans-123');
        $this->mockTransaction->method('getUser')->willReturn($this->mockUser);
        $this->mockTransaction->method('getAccount')->willReturn($this->mockAccount);
        $this->mockTransaction->method('getCreditTypeId')->willReturn('type-1');
        $this->mockTransaction->method('getType')->willReturn(CreditTransactionTypeEnum::INCOME);
        $this->mockTransaction->method('getAmount')->willReturn(100);
        $this->mockTransaction->method('getStatus')->willReturn(CreditTransactionStatusEnum::COMPLETED);
        $this->mockTransaction->method('getBusinessCode')->willReturn('TASK_REWARD');
        $this->mockTransaction->method('getBusinessId')->willReturn('task-123');
        $this->mockTransaction->method('getCreateTime')->willReturn(new DateTimeImmutable('2023-01-01 12:00:00'));
        
        // 创建模拟的交易服务
        $this->transactionService = $this->createMock(CreditTransactionServiceInterface::class);
    }

    /**
     * 测试根据ID获取交易记录
     */
    public function testGetTransactionById_returnsCorrectTransaction()
    {
        $this->transactionService
            ->method('getTransactionById')
            ->willReturnCallback(function ($transactionId) {
                if ($transactionId === 'trans-123') {
                    return $this->mockTransaction;
                }
                throw new CreditServiceException('交易记录不存在');
            });
        
        // 测试正确获取交易记录
        $transaction = $this->transactionService->getTransactionById('trans-123');
        $this->assertSame($this->mockTransaction, $transaction);
        $this->assertSame('trans-123', $transaction->getId());
        $this->assertSame($this->mockUser, $transaction->getUser());
        $this->assertSame($this->mockAccount, $transaction->getAccount());
        
        // 测试不存在的交易ID
        $this->expectException(CreditServiceException::class);
        $this->transactionService->getTransactionById('non-existent-transaction');
    }

    /**
     * 测试获取用户交易记录
     */
    public function testGetUserTransactions_returnsCorrectTransactions()
    {
        $mockTransaction2 = $this->createMock(CreditTransactionInterface::class);
        $mockTransaction2->method('getId')->willReturn('trans-456');
        $mockTransaction2->method('getUser')->willReturn($this->mockUser);
        
        $transactionList = [$this->mockTransaction, $mockTransaction2];
        $pagination = ['total' => 2, 'page' => 1, 'pageSize' => 20];
        $expected = ['items' => $transactionList, 'pagination' => $pagination];
        
        $this->transactionService
            ->method('getUserTransactions')
            ->willReturnCallback(function ($user, $filters, $page, $pageSize) use ($expected) {
                if ($user === $this->mockUser) {
                    return $expected;
                }
                return ['items' => [], 'pagination' => ['total' => 0, 'page' => 1, 'pageSize' => 20]];
            });
        
        // 测试获取用户交易记录
        $result = $this->transactionService->getUserTransactions($this->mockUser);
        $this->assertSame($expected, $result);
        $this->assertCount(2, $result['items']);
        $this->assertSame($this->mockTransaction, $result['items'][0]);
        $this->assertSame($mockTransaction2, $result['items'][1]);
        
        // 测试应用过滤器
        $filters = ['type' => CreditTransactionTypeEnum::INCOME->value];
        $this->transactionService->getUserTransactions($this->mockUser, $filters);
        
        // 测试分页
        $this->transactionService->getUserTransactions($this->mockUser, [], 2, 10);
    }

    /**
     * 测试获取账户交易记录
     */
    public function testGetAccountTransactions_returnsCorrectTransactions()
    {
        $mockTransaction2 = $this->createMock(CreditTransactionInterface::class);
        $mockTransaction2->method('getId')->willReturn('trans-456');
        $mockTransaction2->method('getAccount')->willReturn($this->mockAccount);
        
        $transactionList = [$this->mockTransaction, $mockTransaction2];
        $pagination = ['total' => 2, 'page' => 1, 'pageSize' => 20];
        $expected = ['items' => $transactionList, 'pagination' => $pagination];
        
        $this->transactionService
            ->method('getAccountTransactions')
            ->willReturnCallback(function ($account, $filters, $page, $pageSize) use ($expected) {
                if ($account === $this->mockAccount) {
                    return $expected;
                }
                return ['items' => [], 'pagination' => ['total' => 0, 'page' => 1, 'pageSize' => 20]];
            });
        
        // 测试获取账户交易记录
        $result = $this->transactionService->getAccountTransactions($this->mockAccount);
        $this->assertSame($expected, $result);
        $this->assertCount(2, $result['items']);
    }

    /**
     * 测试获取账户所有交易记录
     */
    public function testGetAllAccountTransactions_returnsAllTransactions()
    {
        $mockTransaction2 = $this->createMock(CreditTransactionInterface::class);
        $mockTransaction2->method('getId')->willReturn('trans-456');
        
        $transactionList = [$this->mockTransaction, $mockTransaction2];
        
        $this->transactionService
            ->method('getAllAccountTransactions')
            ->willReturnCallback(function ($accountId) use ($transactionList) {
                if ($accountId === 'account-id-1') {
                    return $transactionList;
                }
                return [];
            });
        
        // 测试获取账户所有交易记录
        $transactions = $this->transactionService->getAllAccountTransactions('account-id-1');
        $this->assertSame($transactionList, $transactions);
        $this->assertCount(2, $transactions);
        
        // 测试获取不存在账户的交易记录
        $emptyTransactions = $this->transactionService->getAllAccountTransactions('non-existent-account');
        $this->assertEmpty($emptyTransactions);
    }

    /**
     * 测试根据业务码和业务ID获取交易记录
     */
    public function testGetTransactionsByBusiness_returnsCorrectTransactions()
    {
        $businessTransaction1 = $this->createMock(CreditTransactionInterface::class);
        $businessTransaction1->method('getId')->willReturn('trans-business-1');
        $businessTransaction1->method('getBusinessCode')->willReturn('ORDER_PAYMENT');
        $businessTransaction1->method('getBusinessId')->willReturn('order-123');
        
        $businessTransaction2 = $this->createMock(CreditTransactionInterface::class);
        $businessTransaction2->method('getId')->willReturn('trans-business-2');
        $businessTransaction2->method('getBusinessCode')->willReturn('ORDER_PAYMENT');
        $businessTransaction2->method('getBusinessId')->willReturn('order-123');
        
        $transactionList = [$businessTransaction1, $businessTransaction2];
        
        $this->transactionService
            ->method('getTransactionsByBusiness')
            ->willReturnCallback(function ($businessCode, $businessId) use ($transactionList) {
                if ($businessCode === 'ORDER_PAYMENT' && $businessId === 'order-123') {
                    return $transactionList;
                }
                return [];
            });
        
        // 测试根据业务码和业务ID获取交易记录
        $transactions = $this->transactionService->getTransactionsByBusiness('ORDER_PAYMENT', 'order-123');
        $this->assertSame($transactionList, $transactions);
        $this->assertCount(2, $transactions);
        
        // 测试不存在的业务记录
        $emptyTransactions = $this->transactionService->getTransactionsByBusiness('NON_EXISTENT', 'id-123');
        $this->assertEmpty($emptyTransactions);
    }

    /**
     * 测试查找单个业务交易记录
     */
    public function testFindByBusinessCodeAndId_returnsCorrectTransaction()
    {
        $businessTransaction = $this->createMock(CreditTransactionInterface::class);
        $businessTransaction->method('getId')->willReturn('trans-business-1');
        $businessTransaction->method('getBusinessCode')->willReturn('TASK_REWARD');
        $businessTransaction->method('getBusinessId')->willReturn('task-123');
        
        $this->transactionService
            ->method('findByBusinessCodeAndId')
            ->willReturnCallback(function ($businessCode, $businessId) use ($businessTransaction) {
                if ($businessCode === 'TASK_REWARD' && $businessId === 'task-123') {
                    return $businessTransaction;
                }
                return null;
            });
        
        // 测试找到匹配的交易记录
        $transaction = $this->transactionService->findByBusinessCodeAndId('TASK_REWARD', 'task-123');
        $this->assertSame($businessTransaction, $transaction);
        $this->assertSame('trans-business-1', $transaction->getId());
        
        // 测试不存在的业务记录
        $nonExistentTransaction = $this->transactionService->findByBusinessCodeAndId('NON_EXISTENT', 'id-123');
        $this->assertNull($nonExistentTransaction);
    }

    /**
     * 测试获取特定时间段内的交易记录
     */
    public function testGetUserTransactionsByTimeRange_returnsCorrectTransactions()
    {
        $startTime = new DateTimeImmutable('2023-01-01');
        $endTime = new DateTimeImmutable('2023-01-31');
        
        $mockTransaction2 = $this->createMock(CreditTransactionInterface::class);
        $mockTransaction2->method('getId')->willReturn('trans-456');
        $mockTransaction2->method('getUser')->willReturn($this->mockUser);
        $mockTransaction2->method('getCreateTime')->willReturn(new DateTimeImmutable('2023-01-15'));
        
        $transactionList = [$this->mockTransaction, $mockTransaction2];
        $pagination = ['total' => 2, 'page' => 1, 'pageSize' => 20];
        $expected = ['items' => $transactionList, 'pagination' => $pagination];
        
        $this->transactionService
            ->method('getUserTransactionsByTimeRange')
            ->willReturnCallback(function ($user, $start, $end, $filters, $page, $pageSize) use ($expected, $startTime, $endTime) {
                if ($user === $this->mockUser && $start == $startTime && $end == $endTime) {
                    return $expected;
                }
                return ['items' => [], 'pagination' => ['total' => 0, 'page' => 1, 'pageSize' => 20]];
            });
        
        // 测试获取特定时间段内的交易记录
        $result = $this->transactionService->getUserTransactionsByTimeRange(
            $this->mockUser,
            $startTime,
            $endTime
        );
        $this->assertSame($expected, $result);
        $this->assertCount(2, $result['items']);
    }

    /**
     * 测试更新交易状态
     */
    public function testUpdateTransactionStatus_returnsCorrectResult()
    {
        $this->transactionService
            ->method('updateTransactionStatus')
            ->willReturnCallback(function ($transactionId, $status, $remark) {
                if ($transactionId === 'trans-123') {
                    return true;
                }
                throw new CreditServiceException('交易记录不存在');
            });
        
        // 测试成功更新状态
        $result = $this->transactionService->updateTransactionStatus(
            'trans-123',
            CreditTransactionStatusEnum::COMPLETED,
            '已完成处理'
        );
        $this->assertTrue($result);
        
        // 测试更新不存在的交易
        $this->expectException(CreditServiceException::class);
        $this->transactionService->updateTransactionStatus(
            'non-existent-transaction',
            CreditTransactionStatusEnum::FAILED,
            '处理失败'
        );
    }

    /**
     * 测试批量更新交易状态
     */
    public function testBatchUpdateTransactionStatus_returnsCorrectResults()
    {
        $transactionIds = ['trans-123', 'trans-456', 'non-existent'];
        $expectedResults = [
            'success' => [
                'trans-123' => '状态已更新',
                'trans-456' => '状态已更新',
            ],
            'failed' => [
                'non-existent' => '交易记录不存在',
            ],
        ];
        
        $this->transactionService
            ->method('batchUpdateTransactionStatus')
            ->willReturnCallback(function ($ids, $status, $remark) use ($expectedResults) {
                return $expectedResults;
            });
        
        // 测试批量更新状态
        $results = $this->transactionService->batchUpdateTransactionStatus(
            $transactionIds,
            CreditTransactionStatusEnum::COMPLETED,
            '批量完成'
        );
        $this->assertSame($expectedResults, $results);
        $this->assertCount(2, $results['success']);
        $this->assertCount(1, $results['failed']);
    }

    /**
     * 测试获取交易统计信息
     */
    public function testGetTransactionStatistics_returnsCorrectStatistics()
    {
        $expectedStats = [
            'total_income' => 1000,
            'total_expense' => 500,
            'total_frozen' => 200,
            'total_unfrozen' => 150,
            'total_expired' => 50,
            'transaction_count' => 15,
            'last_transaction_time' => new DateTimeImmutable('2023-01-31'),
        ];
        
        $this->transactionService
            ->method('getTransactionStatistics')
            ->willReturnCallback(function ($user, $creditTypeId, $startTime, $endTime) use ($expectedStats) {
                if ($user === $this->mockUser) {
                    return $expectedStats;
                }
                return [];
            });
        
        // 测试获取交易统计
        $statistics = $this->transactionService->getTransactionStatistics(
            $this->mockUser,
            'type-1'
        );
        $this->assertSame($expectedStats, $statistics);
    }

    /**
     * 测试标记交易完成
     */
    public function testMarkTransactionCompleted_returnsCorrectResult()
    {
        $this->transactionService
            ->method('markTransactionCompleted')
            ->willReturnCallback(function ($transactionId, $remark) {
                if ($transactionId === 'trans-123') {
                    return true;
                }
                throw new CreditServiceException('交易记录不存在或状态错误');
            });
        
        // 测试成功标记交易完成
        $result = $this->transactionService->markTransactionCompleted('trans-123', '系统自动完成');
        $this->assertTrue($result);
        
        // 测试标记不存在的交易
        $this->expectException(CreditServiceException::class);
        $this->transactionService->markTransactionCompleted('non-existent-transaction', '系统自动完成');
    }

    /**
     * 测试记录操作日志
     */
    public function testLogOperation_returnsLogId()
    {
        $logData = [
            'user_id' => 'user-123',
            'action' => 'credit_add',
            'details' => '添加积分100点',
            'ip' => '192.168.1.1',
        ];
        $expectedLogId = 'log-123';
        
        $this->transactionService
            ->method('logOperation')
            ->willReturnCallback(function ($data) use ($expectedLogId) {
                return $expectedLogId;
            });
        
        // 测试记录操作日志
        $logId = $this->transactionService->logOperation($logData);
        $this->assertSame($expectedLogId, $logId);
    }

    /**
     * 测试获取交易详情
     */
    public function testGetTransactionDetails_returnsCorrectDetails()
    {
        $expectedDetails = [
            'transaction' => $this->mockTransaction,
            'operator' => [
                'id' => 'operator-123',
                'name' => '管理员',
            ],
            'business_data' => [
                'task_name' => '每日签到',
                'task_id' => 'task-123',
            ],
            'timeline' => [
                ['time' => '2023-01-01 12:00:00', 'status' => '创建'],
                ['time' => '2023-01-01 12:01:00', 'status' => '完成'],
            ],
        ];
        
        $this->transactionService
            ->method('getTransactionDetails')
            ->willReturnCallback(function ($transactionId) use ($expectedDetails) {
                if ($transactionId === 'trans-123') {
                    return $expectedDetails;
                }
                throw new CreditServiceException('交易记录不存在');
            });
        
        // 测试获取交易详情
        $details = $this->transactionService->getTransactionDetails('trans-123');
        $this->assertSame($expectedDetails, $details);
        
        // 测试获取不存在交易的详情
        $this->expectException(CreditServiceException::class);
        $this->transactionService->getTransactionDetails('non-existent-transaction');
    }

    /**
     * 测试添加交易备注
     */
    public function testAddTransactionRemark_returnsCorrectResult()
    {
        $this->transactionService
            ->method('addTransactionRemark')
            ->willReturnCallback(function ($transactionId, $remark, $user) {
                if ($transactionId === 'trans-123' && $user === $this->mockUser) {
                    return true;
                }
                throw new CreditServiceException('交易记录不存在');
            });
        
        // 测试成功添加备注
        $result = $this->transactionService->addTransactionRemark(
            'trans-123',
            '手动确认完成',
            $this->mockUser
        );
        $this->assertTrue($result);
        
        // 测试为不存在的交易添加备注
        $this->expectException(CreditServiceException::class);
        $this->transactionService->addTransactionRemark(
            'non-existent-transaction',
            '备注',
            $this->mockUser
        );
    }

    /**
     * 测试获取关联交易
     */
    public function testGetRelatedTransactions_returnsCorrectTransactions()
    {
        $relatedTransaction1 = $this->createMock(CreditTransactionInterface::class);
        $relatedTransaction1->method('getId')->willReturn('related-trans-1');
        
        $relatedTransaction2 = $this->createMock(CreditTransactionInterface::class);
        $relatedTransaction2->method('getId')->willReturn('related-trans-2');
        
        $relatedTransactions = [$relatedTransaction1, $relatedTransaction2];
        
        $this->transactionService
            ->method('getRelatedTransactions')
            ->willReturnCallback(function ($transactionId) use ($relatedTransactions) {
                if ($transactionId === 'trans-123') {
                    return $relatedTransactions;
                }
                throw new CreditServiceException('交易记录不存在');
            });
        
        // 测试获取关联交易
        $transactions = $this->transactionService->getRelatedTransactions('trans-123');
        $this->assertSame($relatedTransactions, $transactions);
        $this->assertCount(2, $transactions);
        
        // 测试获取不存在交易的关联交易
        $this->expectException(CreditServiceException::class);
        $this->transactionService->getRelatedTransactions('non-existent-transaction');
    }

    /**
     * 测试验证交易完整性
     */
    public function testVerifyTransactionIntegrity_returnsCorrectResult()
    {
        $this->transactionService
            ->method('verifyTransactionIntegrity')
            ->willReturnCallback(function ($transactionId) {
                if ($transactionId === 'trans-123') {
                    return true;
                } elseif ($transactionId === 'trans-tampered') {
                    return false;
                }
                throw new CreditServiceException('交易记录不存在');
            });
        
        // 测试完整的交易
        $result = $this->transactionService->verifyTransactionIntegrity('trans-123');
        $this->assertTrue($result);
        
        // 测试被篡改的交易
        $result = $this->transactionService->verifyTransactionIntegrity('trans-tampered');
        $this->assertFalse($result);
        
        // 测试不存在的交易
        $this->expectException(CreditServiceException::class);
        $this->transactionService->verifyTransactionIntegrity('non-existent-transaction');
    }
} 