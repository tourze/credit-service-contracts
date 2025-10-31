<?php

declare(strict_types=1);

namespace Tourze\CreditServiceContracts\Exception;

/**
 * 积分服务异常基类
 */
class CreditServiceException extends \Exception
{
    /**
     * 错误代码：通用错误
     */
    public const ERROR_GENERAL = 10000;

    /**
     * 错误代码：账户不存在
     */
    public const ERROR_ACCOUNT_NOT_FOUND = 10001;

    /**
     * 错误代码：账户已禁用
     */
    public const ERROR_ACCOUNT_DISABLED = 10002;

    /**
     * 错误代码：余额不足
     */
    public const ERROR_INSUFFICIENT_BALANCE = 10003;

    /**
     * 错误代码：积分类型不存在
     */
    public const ERROR_CREDIT_TYPE_NOT_FOUND = 10004;

    /**
     * 错误代码：积分类型已禁用
     */
    public const ERROR_CREDIT_TYPE_DISABLED = 10005;

    /**
     * 错误代码：交易不存在
     */
    public const ERROR_TRANSACTION_NOT_FOUND = 10009;

    /**
     * 错误代码：交易状态错误
     */
    public const ERROR_TRANSACTION_STATUS = 10010;

    /**
     * 错误代码：冻结积分不足
     */
    public const ERROR_INSUFFICIENT_FROZEN = 10017;

    /**
     * 错误代码：参数错误
     */
    public const ERROR_INVALID_PARAMETER = 10018;

    /**
     * 错误代码：数据库错误
     */
    public const ERROR_DATABASE = 10019;

    /**
     * 错误代码：系统错误
     */
    public const ERROR_SYSTEM = 10020;

    /**
     * 错误代码：业务码与业务ID冲突
     */
    public const ERROR_BUSINESS_CODE_CONFLICT = 10021;

    /**
     * 错误代码：交易已存在
     */
    public const ERROR_TRANSACTION_EXISTS = 10022;

    /**
     * 错误代码：批量操作部分失败
     */
    public const ERROR_BATCH_PARTIAL_FAILURE = 10023;

    /**
     * 错误代码：操作被锁定
     */
    public const ERROR_OPERATION_LOCKED = 10024;

    /**
     * 错误代码：版本冲突
     */
    public const ERROR_VERSION_CONFLICT = 10025;

    /**
     * 错误代码：积分已过期
     */
    public const ERROR_CREDITS_EXPIRED = 10026;

    /**
     * 错误上下文数据
     *
     * @var array<string, mixed>
     */
    protected array $context;

    /**
     * 构造函数
     *
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * 获取错误上下文数据
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * 创建余额不足异常
     *
     * @param array<string, mixed> $context
     */
    public static function insufficientBalance(int $required, int $available, array $context = []): self
    {
        return new self(
            sprintf('积分余额不足，需要%d积分，当前可用%d积分', $required, $available),
            self::ERROR_INSUFFICIENT_BALANCE,
            array_merge(['required' => $required, 'available' => $available], $context)
        );
    }

    /**
     * 创建账户不存在异常
     *
     * @param array<string, mixed> $context
     */
    public static function accountNotFound(string $identifier, array $context = []): self
    {
        return new self(
            sprintf('积分账户不存在: %s', $identifier),
            self::ERROR_ACCOUNT_NOT_FOUND,
            array_merge(['identifier' => $identifier], $context)
        );
    }

    /**
     * 创建交易不存在异常
     *
     * @param array<string, mixed> $context
     */
    public static function transactionNotFound(string $transactionId, array $context = []): self
    {
        return new self(
            sprintf('交易记录不存在: %s', $transactionId),
            self::ERROR_TRANSACTION_NOT_FOUND,
            array_merge(['transaction_id' => $transactionId], $context)
        );
    }

    /**
     * 创建交易状态错误异常
     *
     * @param array<string, mixed> $context
     */
    public static function invalidTransactionStatus(string $transactionId, string $currentStatus, string $expectedStatus, array $context = []): self
    {
        return new self(
            sprintf('交易状态错误: %s，当前状态: %s，期望状态: %s', $transactionId, $currentStatus, $expectedStatus),
            self::ERROR_TRANSACTION_STATUS,
            array_merge([
                'transaction_id' => $transactionId,
                'current_status' => $currentStatus,
                'expected_status' => $expectedStatus,
            ], $context)
        );
    }

    /**
     * 创建重复交易异常
     *
     * @param array<string, mixed> $context
     */
    public static function duplicateTransaction(string $businessCode, string $businessId, array $context = []): self
    {
        return new self(
            sprintf('交易已存在，业务码: %s，业务ID: %s', $businessCode, $businessId),
            self::ERROR_TRANSACTION_EXISTS,
            array_merge(['business_code' => $businessCode, 'business_id' => $businessId], $context)
        );
    }

    /**
     * 创建账户已禁用异常
     *
     * @param array<string, mixed> $context
     */
    public static function accountDisabled(string $accountId, array $context = []): self
    {
        return new self(
            sprintf('积分账户已禁用: %s', $accountId),
            self::ERROR_ACCOUNT_DISABLED,
            array_merge(['account_id' => $accountId], $context)
        );
    }

    /**
     * 创建积分类型不存在异常
     *
     * @param array<string, mixed> $context
     */
    public static function creditTypeNotFound(string $creditTypeId, array $context = []): self
    {
        return new self(
            sprintf('积分类型不存在: %s', $creditTypeId),
            self::ERROR_CREDIT_TYPE_NOT_FOUND,
            array_merge(['credit_type_id' => $creditTypeId], $context)
        );
    }

    /**
     * 创建积分类型已禁用异常
     *
     * @param array<string, mixed> $context
     */
    public static function creditTypeDisabled(string $creditTypeId, array $context = []): self
    {
        return new self(
            sprintf('积分类型已禁用: %s', $creditTypeId),
            self::ERROR_CREDIT_TYPE_DISABLED,
            array_merge(['credit_type_id' => $creditTypeId], $context)
        );
    }

    /**
     * 创建冻结积分不足异常
     *
     * @param array<string, mixed> $context
     */
    public static function insufficientFrozen(string $accountId, int $required, int $available, array $context = []): self
    {
        return new self(
            sprintf('冻结积分不足，账户: %s，需要%d积分，当前冻结%d积分', $accountId, $required, $available),
            self::ERROR_INSUFFICIENT_FROZEN,
            array_merge([
                'account_id' => $accountId,
                'required' => $required,
                'available' => $available,
            ], $context)
        );
    }

    /**
     * 创建参数错误异常
     *
     * @param array<string, mixed> $context
     */
    public static function invalidParameter(string $paramName, string $reason, array $context = []): self
    {
        return new self(
            sprintf('参数错误: %s, 原因: %s', $paramName, $reason),
            self::ERROR_INVALID_PARAMETER,
            array_merge(['param_name' => $paramName, 'reason' => $reason], $context)
        );
    }

    /**
     * 创建数据库错误异常
     *
     * @param array<string, mixed> $context
     */
    public static function databaseError(string $message, array $context = []): self
    {
        return new self(
            sprintf('数据库操作错误: %s', $message),
            self::ERROR_DATABASE,
            $context
        );
    }

    /**
     * 创建操作锁定异常
     *
     * @param array<string, mixed> $context
     */
    public static function operationLocked(string $resourceId, array $context = []): self
    {
        return new self(
            sprintf('操作被锁定，资源ID: %s', $resourceId),
            self::ERROR_OPERATION_LOCKED,
            array_merge(['resource_id' => $resourceId], $context)
        );
    }

    /**
     * 创建版本冲突异常
     *
     * @param array<string, mixed> $context
     */
    public static function versionConflict(string $resourceId, int $expectedVersion, int $actualVersion, array $context = []): self
    {
        return new self(
            sprintf('版本冲突，资源ID: %s，期望版本: %d，实际版本: %d', $resourceId, $expectedVersion, $actualVersion),
            self::ERROR_VERSION_CONFLICT,
            array_merge([
                'resource_id' => $resourceId,
                'expected_version' => $expectedVersion,
                'actual_version' => $actualVersion,
            ], $context)
        );
    }

    /**
     * 创建积分已过期异常
     *
     * @param array<string, mixed> $context
     */
    public static function creditsExpired(string $accountId, array $context = []): self
    {
        return new self(
            sprintf('积分已过期，账户ID: %s', $accountId),
            self::ERROR_CREDITS_EXPIRED,
            array_merge(['account_id' => $accountId], $context)
        );
    }

    /**
     * 创建批量操作部分失败异常
     *
     * @param array<mixed> $failedItems
     * @param array<string, mixed> $context
     */
    public static function batchPartialFailure(array $failedItems, array $context = []): self
    {
        return new self(
            sprintf('批量操作部分失败，失败项数量: %d', count($failedItems)),
            self::ERROR_BATCH_PARTIAL_FAILURE,
            array_merge(['failed_items' => $failedItems], $context)
        );
    }

    /**
     * 创建业务码冲突异常
     *
     * @param array<string, mixed> $context
     */
    public static function businessCodeConflict(string $businessCode, string $businessId, array $context = []): self
    {
        return new self(
            sprintf('业务码与业务ID冲突: %s, %s', $businessCode, $businessId),
            self::ERROR_BUSINESS_CODE_CONFLICT,
            array_merge(['business_code' => $businessCode, 'business_id' => $businessId], $context)
        );
    }

    /**
     * 创建系统错误异常
     *
     * @param array<string, mixed> $context
     */
    public static function systemError(string $message, array $context = []): self
    {
        return new self(
            sprintf('系统错误: %s', $message),
            self::ERROR_SYSTEM,
            $context
        );
    }
}
