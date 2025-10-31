<?php

declare(strict_types=1);

namespace Tourze\CreditServiceContracts\Service;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\CreditServiceContracts\CreditAccountInterface;
use Tourze\CreditServiceContracts\Exception\CreditServiceException;
use Tourze\UserIDBundle\Contracts\AccountInterface;

/**
 * 积分账户服务接口
 */
interface CreditAccountServiceInterface
{
    /**
     * 获取用户特定积分类型的账户
     *
     * @param UserInterface $user         用户
     * @param string        $creditTypeId 积分类型ID
     *
     * @return CreditAccountInterface 积分账户
     *
     * @throws CreditServiceException 账户不存在时抛出异常
     */
    public function getAccount(UserInterface $user, string $creditTypeId): CreditAccountInterface;

    /**
     * 获取用户特定积分类型的账户，如不存在则创建
     *
     * @param UserInterface $user         用户
     * @param string        $creditTypeId 积分类型ID
     *
     * @return CreditAccountInterface 积分账户
     *
     * @throws CreditServiceException 创建失败时抛出异常
     */
    public function getOrCreateAccount(UserInterface $user, string $creditTypeId): CreditAccountInterface;

    /**
     * 获取用户的所有积分账户
     *
     * @param UserInterface $user 用户
     *
     * @return CreditAccountInterface[] 积分账户列表
     */
    public function getUserAccounts(UserInterface $user): array;

    /**
     * 根据账户ID获取账户
     *
     * @param AccountInterface $account 账户
     *
     * @return CreditAccountInterface 积分账户
     *
     * @throws CreditServiceException 账户不存在时抛出异常
     */
    public function getAccountById(AccountInterface $account): CreditAccountInterface;

    /**
     * 使用乐观锁更新账户信息
     *
     * @param string $accountId 账户ID
     * @param array<string, mixed>  $data      更新数据
     * @param int    $version   当前版本号
     *
     * @return bool 更新是否成功
     *
     * @throws CreditServiceException 账户不存在或版本冲突时抛出异常
     */
    public function updateAccountWithVersion(string $accountId, array $data, int $version): bool;

    /**
     * 使用悲观锁获取账户
     *
     * @param UserInterface $user         用户
     * @param string        $creditTypeId 积分类型ID
     *
     * @return CreditAccountInterface 带锁的积分账户
     *
     * @throws CreditServiceException 账户不存在或获取锁失败时抛出异常
     */
    public function getAccountWithLock(UserInterface $user, string $creditTypeId): CreditAccountInterface;

    /**
     * 更新账户信息
     *
     * @param string $accountId 账户ID
     * @param array<string, mixed>  $data      更新数据
     *
     * @return bool 更新是否成功
     *
     * @throws CreditServiceException 账户不存在时抛出异常
     */
    public function updateAccount(string $accountId, array $data): bool;

    /**
     * 释放账户锁
     *
     * @param string $accountId 账户ID
     *
     * @return bool 释放是否成功
     */
    public function releaseLock(string $accountId): bool;

    /**
     * 校正账户余额
     *
     * 当账户余额与交易记录不一致时，用于修正账户余额
     *
     * @param string $accountId         账户ID
     * @param int    $calculatedBalance 计算得出的正确余额
     * @param string $reason            校正原因
     *
     * @return bool 校正是否成功
     *
     * @throws CreditServiceException 账户不存在时抛出异常
     */
    public function correctBalance(string $accountId, int $calculatedBalance, string $reason): bool;

    /**
     * 批量创建账户
     *
     * @param array<mixed> $accountsData 账户数据列表，每项包含user和creditTypeId
     *
     * @return array<mixed> 创建的账户列表
     *
     * @throws CreditServiceException 创建失败时抛出异常
     */
    public function batchCreateAccounts(array $accountsData): array;

    /**
     * 获取即将过期的积分
     *
     * @param UserInterface $user          用户
     * @param string        $creditTypeId  积分类型ID
     * @param int           $daysThreshold 过期天数阈值，默认30天
     *
     * @return array<mixed> 即将过期的积分信息
     *
     * @throws CreditServiceException 账户不存在时抛出异常
     */
    public function getExpiringCredits(UserInterface $user, string $creditTypeId, int $daysThreshold = 30): array;

    /**
     * 冻结积分
     *
     * 用于订单支付等场景，防止用户在下单后、支付前使用积分
     *
     * @param string $accountId 账户ID
     * @param int    $amount    冻结金额
     * @param string $reason    冻结原因
     * @param array<string, mixed>  $metadata  元数据
     *
     * @return bool 冻结是否成功
     *
     * @throws CreditServiceException 账户不存在或余额不足时抛出异常
     */
    public function freezeCredits(
        string $accountId,
        int $amount,
        string $reason,
        array $metadata = [],
    ): bool;

    /**
     * 解冻积分
     *
     * 用于订单取消等场景，将之前冻结的积分恢复可用
     *
     * @param string $accountId 账户ID
     * @param int    $amount    解冻金额
     * @param string $reason    解冻原因
     * @param array<string, mixed>  $metadata  元数据
     *
     * @return bool 解冻是否成功
     *
     * @throws CreditServiceException 账户不存在或冻结金额不足时抛出异常
     */
    public function unfreezeCredits(
        string $accountId,
        int $amount,
        string $reason,
        array $metadata = [],
    ): bool;

    /**
     * 获取账户余额快照
     *
     * 用于记录特定时间点的账户状态，便于后续审计和分析
     *
     * @param string $accountId 账户ID
     *
     * @return array<string, mixed> 账户快照数据
     *
     * @throws CreditServiceException 账户不存在时抛出异常
     */
    public function getAccountSnapshot(string $accountId): array;

    /**
     * 批量获取账户信息
     *
     * @param array<string> $accountIds 账户ID列表
     *
     * @return array<mixed> 账户信息列表
     */
    public function batchGetAccounts(array $accountIds): array;

    /**
     * 获取特定积分类型的所有账户
     *
     * @param string $creditTypeId 积分类型ID
     * @param array<string, mixed>  $filters      过滤条件
     * @param int    $page         页码
     * @param int    $pageSize     每页数量
     *
     * @return array<mixed> 账户列表和分页信息
     */
    public function getAccountsByCreditType(
        string $creditTypeId,
        array $filters = [],
        int $page = 1,
        int $pageSize = 20,
    ): array;

    /**
     * 处理积分过期
     *
     * 自动扫描并处理已过期的积分
     *
     * @param string                  $accountId     账户ID
     * @param \DateTimeInterface|null $referenceDate 参考日期，默认为当前时间
     *
     * @return int 处理的过期积分数量
     *
     * @throws CreditServiceException 账户不存在时抛出异常
     */
    public function processExpiredCredits(
        string $accountId,
        ?\DateTimeInterface $referenceDate = null,
    ): int;

    /**
     * 批量处理过期积分
     *
     * @param array<string>                   $accountIds    账户ID列表
     * @param \DateTimeInterface|null $referenceDate 参考日期，默认为当前时间
     *
     * @return array<mixed> 处理结果，包含每个账户的处理状态和数量
     */
    public function batchProcessExpiredCredits(
        array $accountIds,
        ?\DateTimeInterface $referenceDate = null,
    ): array;

    /**
     * 设置账户状态
     *
     * @param string      $accountId 账户ID
     * @param bool        $isActive  是否激活
     * @param string|null $reason    原因
     *
     * @return bool 操作是否成功
     *
     * @throws CreditServiceException 账户不存在时抛出异常
     */
    public function setAccountStatus(string $accountId, bool $isActive, ?string $reason = null): bool;

    /**
     * 验证账户操作权限
     *
     * @param string        $accountId 账户ID
     * @param UserInterface $user      用户
     * @param string        $operation 操作类型
     *
     * @return bool 是否有权限
     *
     * @throws CreditServiceException 账户不存在时抛出异常
     */
    public function verifyAccountPermission(
        string $accountId,
        UserInterface $user,
        string $operation,
    ): bool;

    /**
     * A/B测试账户规则
     *
     * 用于支持不同用户群体采用不同积分规则的场景
     *
     * @param UserInterface $user     用户
     * @param string        $testName 测试名称
     * @param array<string, mixed>         $context  上下文信息
     *
     * @return string 适用的规则组ID
     */
    public function getAbTestRuleGroup(
        UserInterface $user,
        string $testName,
        array $context = [],
    ): string;

    /**
     * 同步不同系统间的积分账户
     *
     * 用于多系统集成场景，确保不同系统间的账户数据一致
     *
     * @param string $accountId         本地账户ID
     * @param string $externalSystem    外部系统标识
     * @param string $externalAccountId 外部系统账户ID
     * @param array<string, mixed>  $options           同步选项
     *
     * @return bool 同步是否成功
     *
     * @throws CreditServiceException 账户不存在或同步失败时抛出异常
     */
    public function syncWithExternalAccount(
        string $accountId,
        string $externalSystem,
        string $externalAccountId,
        array $options = [],
    ): bool;
}
