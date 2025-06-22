<?php

namespace Tourze\CreditServiceContracts\Service;

use DateTimeInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\CreditServiceContracts\CreditTransactionInterface;
use Tourze\CreditServiceContracts\Enum\CreditTransactionStatusEnum;
use Tourze\CreditServiceContracts\Enum\CreditTransactionTypeEnum;
use Tourze\CreditServiceContracts\Exception\CreditServiceException;
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
     * @return CreditTransactionInterface 交易记录
     * @throws CreditServiceException 交易不存在时抛出异常
     */
    public function getTransactionById(string $transactionId): CreditTransactionInterface;

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
     * 获取账户所有交易记录（不分页，用于计算余额）
     *
     * @param string $accountId 账户ID
     * @return CreditTransactionInterface[] 交易记录列表
     */
    public function getAllAccountTransactions(string $accountId): array;

    /**
     * 根据业务码和业务ID获取交易记录
     *
     * @param string $businessCode 业务码
     * @param string $businessId 业务ID
     * @return CreditTransactionInterface[] 交易记录列表
     */
    public function getTransactionsByBusiness(string $businessCode, string $businessId): array;
    
    /**
     * 根据业务码和业务ID查找单个交易记录
     *
     * 用于幂等性处理
     *
     * @param string $businessCode 业务码
     * @param string $businessId 业务ID
     * @return CreditTransactionInterface|null 交易记录，不存在时返回null
     */
    public function findByBusinessCodeAndId(string $businessCode, string $businessId): ?CreditTransactionInterface;

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
     * @throws CreditServiceException 交易不存在或状态错误时抛出异常
     */
    public function updateTransactionStatus(
        string $transactionId, 
        CreditTransactionStatusEnum $status, 
        ?string $remark = null
    ): bool;

    /**
     * 批量更新交易状态
     *
     * @param array $transactionIds 交易ID列表
     * @param CreditTransactionStatusEnum $status 交易状态
     * @param string|null $remark 备注
     * @return array 更新结果，包含成功和失败信息
     */
    public function batchUpdateTransactionStatus(
        array $transactionIds, 
        CreditTransactionStatusEnum $status, 
        ?string $remark = null
    ): array;

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
    
    /**
     * 标记交易完成
     *
     * @param string $transactionId 交易ID
     * @param string|null $remark 备注
     * @return bool 操作是否成功
     * @throws CreditServiceException 交易不存在或状态错误时抛出异常
     */
    public function markTransactionCompleted(
        string $transactionId, 
        ?string $remark = null
    ): bool;
    
    /**
     * 记录操作日志
     *
     * @param array $logData 日志数据
     * @return string 日志ID
     */
    public function logOperation(array $logData): string;
    
    /**
     * 更新操作步骤状态
     *
     * @param string $operationId 操作ID
     * @param string $stepId 步骤ID
     * @param string $status 状态
     * @param array $data 附加数据
     * @return bool 操作是否成功
     */
    public function updateOperationStep(
        string $operationId, 
        string $stepId, 
        string $status, 
        array $data = []
    ): bool;
    
    /**
     * 更新操作状态
     *
     * @param string $operationId 操作ID
     * @param string $status 状态
     * @param array $data 附加数据
     * @return bool 操作是否成功
     */
    public function updateOperationStatus(
        string $operationId, 
        string $status, 
        array $data = []
    ): bool;
    
    /**
     * 记录审计日志
     *
     * @param string $type 日志类型
     * @param array $data 日志数据
     * @return string 日志ID
     */
    public function logAudit(string $type, array $data): string;
    
    /**
     * 记录数据不一致信息
     *
     * @param string $type 不一致类型
     * @param array $data 不一致数据
     * @return string 记录ID
     */
    public function logInconsistency(string $type, array $data): string;
    
    /**
     * 获取交易详情
     *
     * 包含交易的详细信息，如关联业务数据、操作人信息等
     *
     * @param string $transactionId 交易ID
     * @return array 交易详情
     * @throws CreditServiceException 交易不存在时抛出异常
     */
    public function getTransactionDetails(string $transactionId): array;
    
    /**
     * 创建交易备注
     *
     * @param string $transactionId 交易ID
     * @param string $remark 备注内容
     * @param UserInterface $user 操作用户
     * @return bool 操作是否成功
     * @throws CreditServiceException 交易不存在时抛出异常
     */
    public function addTransactionRemark(
        string $transactionId, 
        string $remark, 
        UserInterface $user
    ): bool;
    
    /**
     * 获取关联交易
     *
     * 用于查询与特定交易相关的其他交易记录，如冻结/解冻、补偿操作等
     *
     * @param string $transactionId 交易ID
     * @return CreditTransactionInterface[] 关联交易列表
     * @throws CreditServiceException 交易不存在时抛出异常
     */
    public function getRelatedTransactions(string $transactionId): array;
    
    /**
     * 导出交易记录
     *
     * @param array $filters 过滤条件
     * @param string $format 导出格式（如csv, excel）
     * @return string 导出文件路径或内容
     */
    public function exportTransactions(array $filters, string $format = 'csv'): string;
    
    /**
     * 验证交易完整性
     *
     * 检查交易记录是否被篡改或损坏
     *
     * @param string $transactionId 交易ID
     * @return bool 交易是否完整
     * @throws CreditServiceException 交易不存在时抛出异常
     */
    public function verifyTransactionIntegrity(string $transactionId): bool;
    
    /**
     * 批量验证交易完整性
     *
     * @param array $transactionIds 交易ID列表
     * @return array 验证结果
     */
    public function batchVerifyTransactionIntegrity(array $transactionIds): array;
    
    /**
     * 获取操作日志
     *
     * @param string $operationId 操作ID
     * @return array 操作日志详情
     */
    public function getOperationLog(string $operationId): array;
    
    /**
     * 获取用户操作日志
     *
     * @param UserInterface $user 用户
     * @param array $filters 过滤条件
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array 操作日志列表和分页信息
     */
    public function getUserOperationLogs(
        UserInterface $user, 
        array $filters = [], 
        int $page = 1, 
        int $pageSize = 20
    ): array;
    
    /**
     * 查询交易链路
     *
     * 跟踪一个业务流程中涉及的所有交易记录
     *
     * @param string $traceId 追踪ID
     * @return array 交易链路信息
     */
    public function getTransactionTrace(string $traceId): array;
    
    /**
     * 获取管理员操作审计日志
     *
     * @param array $filters 过滤条件
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array 审计日志列表和分页信息
     */
    public function getAdminAuditLogs(
        array $filters = [], 
        int $page = 1, 
        int $pageSize = 20
    ): array;
    
    /**
     * 交易重试
     *
     * 重新执行之前失败的交易
     *
     * @param string $transactionId 交易ID
     * @param array $options 重试选项
     * @return bool 重试是否成功
     * @throws CreditServiceException 交易不存在或状态错误时抛出异常
     */
    public function retryTransaction(string $transactionId, array $options = []): bool;
    
    /**
     * 批量交易重试
     *
     * @param array $transactionIds 交易ID列表
     * @param array $options 重试选项
     * @return array 重试结果
     */
    public function batchRetryTransactions(array $transactionIds, array $options = []): array;
    
    /**
     * 获取数据校验报告
     *
     * 用于系统自检，检查账户余额与交易记录是否一致
     *
     * @param array $filters 过滤条件，如积分类型、时间范围等
     * @return array 数据校验报告
     */
    public function getDataConsistencyReport(array $filters = []): array;
    
    /**
     * 获取系统风险警报
     *
     * 检测异常交易模式，如短时间内大量积分变动
     *
     * @param array $parameters 检测参数
     * @return array 风险警报列表
     */
    public function getRiskAlerts(array $parameters = []): array;
}
