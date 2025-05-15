<?php

namespace Tourze\CreditServiceContracts\Service;

use DateTimeInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\CreditServiceContracts\CreditTransactionInterface;
use Tourze\CreditServiceContracts\Enum\CreditTransactionStatusEnum;
use Tourze\CreditServiceContracts\Enum\CreditTransactionTypeEnum;
use Tourze\UserIDBundle\Contracts\AccountInterface;

/**
 * 积分交易服务接口
 */
interface CreditTransactionServiceInterface
{
    /**
     * 根据ID获取交易记录
     *
     * @param string $transactionId 交易ID
     * @return CreditTransactionInterface|null 交易记录，不存在时返回null
     */
    public function getTransactionById(string $transactionId): ?CreditTransactionInterface;

    /**
     * 获取用户交易记录
     *
     * @param UserInterface $user 用户
     * @param array $filters 过滤条件
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array 交易记录列表和分页信息
     */
    public function getUserTransactions(
        UserInterface $user, 
        array $filters = [], 
        int $page = 1, 
        int $pageSize = 20
    ): array;

    /**
     * 获取账户交易记录
     *
     * @param AccountInterface $account 账户
     * @param array $filters 过滤条件
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array 交易记录列表和分页信息
     */
    public function getAccountTransactions(
        AccountInterface $account, 
        array $filters = [], 
        int $page = 1, 
        int $pageSize = 20
    ): array;

    /**
     * 根据业务码和业务ID获取交易记录
     *
     * @param string $businessCode 业务码
     * @param string $businessId 业务ID
     * @return CreditTransactionInterface[] 交易记录列表
     */
    public function getTransactionsByBusiness(string $businessCode, string $businessId): array;

    /**
     * 获取用户特定时间段内的交易记录
     *
     * @param UserInterface $user 用户
     * @param DateTimeInterface $startTime 开始时间
     * @param DateTimeInterface $endTime 结束时间
     * @param array $filters 过滤条件
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array 交易记录列表和分页信息
     */
    public function getUserTransactionsByTimeRange(
        UserInterface $user, 
        DateTimeInterface $startTime, 
        DateTimeInterface $endTime, 
        array $filters = [], 
        int $page = 1, 
        int $pageSize = 20
    ): array;

    /**
     * 获取用户特定类型的交易记录
     *
     * @param UserInterface $user 用户
     * @param CreditTransactionTypeEnum $type 交易类型
     * @param array $filters 过滤条件
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array 交易记录列表和分页信息
     */
    public function getUserTransactionsByType(
        UserInterface $user, 
        CreditTransactionTypeEnum $type, 
        array $filters = [], 
        int $page = 1, 
        int $pageSize = 20
    ): array;

    /**
     * 获取用户特定状态的交易记录
     *
     * @param UserInterface $user 用户
     * @param CreditTransactionStatusEnum $status 交易状态
     * @param array $filters 过滤条件
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array 交易记录列表和分页信息
     */
    public function getUserTransactionsByStatus(
        UserInterface $user, 
        CreditTransactionStatusEnum $status, 
        array $filters = [], 
        int $page = 1, 
        int $pageSize = 20
    ): array;

    /**
     * 更新交易状态
     *
     * @param string $transactionId 交易ID
     * @param CreditTransactionStatusEnum $status 交易状态
     * @param string|null $remark 备注
     * @return bool 操作是否成功
     */
    public function updateTransactionStatus(string $transactionId, CreditTransactionStatusEnum $status, ?string $remark = null): bool;

    /**
     * 批量更新交易状态
     *
     * @param array $transactionIds 交易ID列表
     * @param CreditTransactionStatusEnum $status 交易状态
     * @param string|null $remark 备注
     * @return bool 操作是否成功
     */
    public function batchUpdateTransactionStatus(array $transactionIds, CreditTransactionStatusEnum $status, ?string $remark = null): bool;

    /**
     * 获取交易统计信息
     *
     * @param UserInterface $user 用户
     * @param string|null $creditTypeId 积分类型ID
     * @param DateTimeInterface|null $startTime 开始时间
     * @param DateTimeInterface|null $endTime 结束时间
     * @return array 统计信息
     */
    public function getTransactionStatistics(
        UserInterface $user, 
        ?string $creditTypeId = null, 
        ?DateTimeInterface $startTime = null, 
        ?DateTimeInterface $endTime = null
    ): array;
}
