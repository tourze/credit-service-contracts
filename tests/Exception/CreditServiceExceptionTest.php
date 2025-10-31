<?php

declare(strict_types=1);

namespace Tourze\CreditServiceContracts\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\CreditServiceContracts\Exception\CreditServiceException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(CreditServiceException::class)]
final class CreditServiceExceptionTest extends AbstractExceptionTestCase
{
    /**
     * 测试异常基本构造函数
     */
    public function testConstructorSetsMessageCodeAndContext(): void
    {
        $message = '测试异常消息';
        $code = CreditServiceException::ERROR_GENERAL;
        $context = ['key' => 'value'];

        $exception = new CreditServiceException($message, $code, $context);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($context, $exception->getContext());
    }

    /**
     * 测试创建余额不足异常
     */
    public function testInsufficientBalanceCreatesCorrectException(): void
    {
        $required = 1000;
        $available = 500;
        $context = ['user_id' => 'user-123'];

        $exception = CreditServiceException::insufficientBalance($required, $available, $context);

        $this->assertStringContainsString('积分余额不足', $exception->getMessage());
        $this->assertStringContainsString((string) $required, $exception->getMessage());
        $this->assertStringContainsString((string) $available, $exception->getMessage());
        $this->assertSame(CreditServiceException::ERROR_INSUFFICIENT_BALANCE, $exception->getCode());

        $exceptionContext = $exception->getContext();
        $this->assertSame($required, $exceptionContext['required']);
        $this->assertSame($available, $exceptionContext['available']);
        $this->assertSame('user-123', $exceptionContext['user_id']);
    }

    /**
     * 测试创建账户不存在异常
     */
    public function testAccountNotFoundCreatesCorrectException(): void
    {
        $identifier = 'account-123';
        $context = ['user_id' => 'user-456'];

        $exception = CreditServiceException::accountNotFound($identifier, $context);

        $this->assertStringContainsString('积分账户不存在', $exception->getMessage());
        $this->assertStringContainsString($identifier, $exception->getMessage());
        $this->assertSame(CreditServiceException::ERROR_ACCOUNT_NOT_FOUND, $exception->getCode());

        $exceptionContext = $exception->getContext();
        $this->assertSame($identifier, $exceptionContext['identifier']);
        $this->assertSame('user-456', $exceptionContext['user_id']);
    }

    /**
     * 测试创建交易不存在异常
     */
    public function testTransactionNotFoundCreatesCorrectException(): void
    {
        $transactionId = 'trans-123';
        $context = ['credit_type' => 'POINTS'];

        $exception = CreditServiceException::transactionNotFound($transactionId, $context);

        $this->assertStringContainsString('交易记录不存在', $exception->getMessage());
        $this->assertStringContainsString($transactionId, $exception->getMessage());
        $this->assertSame(CreditServiceException::ERROR_TRANSACTION_NOT_FOUND, $exception->getCode());

        $exceptionContext = $exception->getContext();
        $this->assertSame($transactionId, $exceptionContext['transaction_id']);
        $this->assertSame('POINTS', $exceptionContext['credit_type']);
    }

    /**
     * 测试创建交易状态错误异常
     */
    public function testInvalidTransactionStatusCreatesCorrectException(): void
    {
        $transactionId = 'trans-456';
        $currentStatus = 'PENDING';
        $expectedStatus = 'COMPLETED';
        $context = ['operation' => 'refund'];

        $exception = CreditServiceException::invalidTransactionStatus(
            $transactionId,
            $currentStatus,
            $expectedStatus,
            $context
        );

        $this->assertStringContainsString('交易状态错误', $exception->getMessage());
        $this->assertStringContainsString($transactionId, $exception->getMessage());
        $this->assertStringContainsString($currentStatus, $exception->getMessage());
        $this->assertStringContainsString($expectedStatus, $exception->getMessage());
        $this->assertSame(CreditServiceException::ERROR_TRANSACTION_STATUS, $exception->getCode());

        $exceptionContext = $exception->getContext();
        $this->assertSame($transactionId, $exceptionContext['transaction_id']);
        $this->assertSame($currentStatus, $exceptionContext['current_status']);
        $this->assertSame($expectedStatus, $exceptionContext['expected_status']);
        $this->assertSame('refund', $exceptionContext['operation']);
    }

    /**
     * 测试创建重复交易异常
     */
    public function testDuplicateTransactionCreatesCorrectException(): void
    {
        $businessCode = 'ORDER_PAYMENT';
        $businessId = 'order-789';
        $context = ['created_at' => '2023-01-01'];

        $exception = CreditServiceException::duplicateTransaction($businessCode, $businessId, $context);

        $this->assertStringContainsString('交易已存在', $exception->getMessage());
        $this->assertStringContainsString($businessCode, $exception->getMessage());
        $this->assertStringContainsString($businessId, $exception->getMessage());
        $this->assertSame(CreditServiceException::ERROR_TRANSACTION_EXISTS, $exception->getCode());

        $exceptionContext = $exception->getContext();
        $this->assertSame($businessCode, $exceptionContext['business_code']);
        $this->assertSame($businessId, $exceptionContext['business_id']);
        $this->assertSame('2023-01-01', $exceptionContext['created_at']);
    }

    /**
     * 测试创建账户已禁用异常
     */
    public function testAccountDisabledCreatesCorrectException(): void
    {
        $accountId = 'account-789';
        $context = ['disabled_at' => '2023-01-02'];

        $exception = CreditServiceException::accountDisabled($accountId, $context);

        $this->assertStringContainsString('积分账户已禁用', $exception->getMessage());
        $this->assertStringContainsString($accountId, $exception->getMessage());
        $this->assertSame(CreditServiceException::ERROR_ACCOUNT_DISABLED, $exception->getCode());

        $exceptionContext = $exception->getContext();
        $this->assertSame($accountId, $exceptionContext['account_id']);
        $this->assertSame('2023-01-02', $exceptionContext['disabled_at']);
    }

    /**
     * 测试创建冻结积分不足异常
     */
    public function testInsufficientFrozenCreatesCorrectException(): void
    {
        $accountId = 'account-012';
        $required = 200;
        $available = 150;
        $context = ['operation' => 'unfreeze'];

        $exception = CreditServiceException::insufficientFrozen($accountId, $required, $available, $context);

        $this->assertStringContainsString('冻结积分不足', $exception->getMessage());
        $this->assertSame(CreditServiceException::ERROR_INSUFFICIENT_FROZEN, $exception->getCode());

        $exceptionContext = $exception->getContext();
        $this->assertSame($accountId, $exceptionContext['account_id']);
        $this->assertSame($required, $exceptionContext['required']);
        $this->assertSame($available, $exceptionContext['available']);
        $this->assertSame('unfreeze', $exceptionContext['operation']);
    }

    /**
     * 测试创建参数错误异常
     */
    public function testInvalidParameterCreatesCorrectException(): void
    {
        $paramName = 'amount';
        $reason = '必须大于0';
        $context = ['value' => -100];

        $exception = CreditServiceException::invalidParameter($paramName, $reason, $context);

        $this->assertStringContainsString('参数错误', $exception->getMessage());
        $this->assertStringContainsString($paramName, $exception->getMessage());
        $this->assertStringContainsString($reason, $exception->getMessage());
        $this->assertSame(CreditServiceException::ERROR_INVALID_PARAMETER, $exception->getCode());

        $exceptionContext = $exception->getContext();
        $this->assertSame($paramName, $exceptionContext['param_name']);
        $this->assertSame($reason, $exceptionContext['reason']);
        $this->assertSame(-100, $exceptionContext['value']);
    }

    /**
     * 测试创建版本冲突异常
     */
    public function testVersionConflictCreatesCorrectException(): void
    {
        $resourceId = 'account-345';
        $expectedVersion = 5;
        $actualVersion = 6;
        $context = ['operation' => 'update'];

        $exception = CreditServiceException::versionConflict(
            $resourceId,
            $expectedVersion,
            $actualVersion,
            $context
        );

        $this->assertStringContainsString('版本冲突', $exception->getMessage());
        $this->assertSame(CreditServiceException::ERROR_VERSION_CONFLICT, $exception->getCode());

        $exceptionContext = $exception->getContext();
        $this->assertSame($resourceId, $exceptionContext['resource_id']);
        $this->assertSame($expectedVersion, $exceptionContext['expected_version']);
        $this->assertSame($actualVersion, $exceptionContext['actual_version']);
        $this->assertSame('update', $exceptionContext['operation']);
    }

    /**
     * 测试创建积分已过期异常
     */
    public function testCreditsExpiredCreatesCorrectException(): void
    {
        $accountId = 'account-678';
        $context = ['expired_at' => '2023-01-15'];

        $exception = CreditServiceException::creditsExpired($accountId, $context);

        $this->assertStringContainsString('积分已过期', $exception->getMessage());
        $this->assertSame(CreditServiceException::ERROR_CREDITS_EXPIRED, $exception->getCode());

        $exceptionContext = $exception->getContext();
        $this->assertSame($accountId, $exceptionContext['account_id']);
        $this->assertSame('2023-01-15', $exceptionContext['expired_at']);
    }

    /**
     * 测试异常层次结构
     */
    public function testExceptionHierarchyInheritsFromBaseException(): void
    {
        $exception = new CreditServiceException();

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /**
     * 测试嵌套异常
     */
    public function testNestedExceptionPreservesPreviousException(): void
    {
        $previousException = new \RuntimeException('内部错误', 123);
        $exception = new CreditServiceException('服务异常', CreditServiceException::ERROR_SYSTEM, [], $previousException);

        $this->assertSame($previousException, $exception->getPrevious());
        $this->assertSame('内部错误', $exception->getPrevious()->getMessage());
        $this->assertSame(123, $exception->getPrevious()->getCode());
    }
}
