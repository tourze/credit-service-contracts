<?php

namespace Tourze\CreditServiceContracts\Service;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\CreditServiceContracts\CreditTransactionInterface;
use Tourze\UserIDBundle\Contracts\AccountInterface;

/**
 * 积分操作服务接口
 *
 * 提供对积分账户的各种操作功能
 */
interface CreditOperationServiceInterface
{
    /**
     * 增加积分
     *
     * @param AccountInterface $account 账户
     * @param int $amount 增加金额
     * @param string $businessCode 业务码
     * @param string|null $businessId 业务ID
     * @param string|null $remark 备注
     * @param array $extraData 额外数据
     * @return CreditTransactionInterface 交易记录
     */
    public function addCredits(
        AccountInterface $account, 
        int $amount, 
        string $businessCode, 
        ?string $businessId = null, 
        ?string $remark = null, 
        array $extraData = []
    ): CreditTransactionInterface;

    /**
     * 扣减积分
     *
     * @param AccountInterface $account 账户
     * @param int $amount 扣减金额
     * @param string $businessCode 业务码
     * @param string|null $businessId 业务ID
     * @param string|null $remark 备注
     * @param array $extraData 额外数据
     * @return CreditTransactionInterface 交易记录
     */
    public function deductCredits(
        AccountInterface $account, 
        int $amount, 
        string $businessCode, 
        ?string $businessId = null, 
        ?string $remark = null, 
        array $extraData = []
    ): CreditTransactionInterface;

    /**
     * 冻结积分
     *
     * @param AccountInterface $account 账户
     * @param int $amount 冻结金额
     * @param string $businessCode 业务码
     * @param string|null $businessId 业务ID
     * @param string|null $remark 备注
     * @param array $extraData 额外数据
     * @return CreditTransactionInterface 交易记录
     */
    public function freezeCredits(
        AccountInterface $account, 
        int $amount, 
        string $businessCode, 
        ?string $businessId = null, 
        ?string $remark = null, 
        array $extraData = []
    ): CreditTransactionInterface;

    /**
     * 解冻积分
     *
     * @param AccountInterface $account 账户
     * @param int $amount 解冻金额
     * @param string $businessCode 业务码
     * @param string|null $businessId 业务ID
     * @param string|null $remark 备注
     * @param array $extraData 额外数据
     * @return CreditTransactionInterface 交易记录
     */
    public function unfreezeCredits(
        AccountInterface $account, 
        int $amount, 
        string $businessCode, 
        ?string $businessId = null, 
        ?string $remark = null, 
        array $extraData = []
    ): CreditTransactionInterface;

    /**
     * 积分过期
     *
     * @param AccountInterface $account 账户
     * @param int $amount 过期金额
     * @param string $businessCode 业务码
     * @param string|null $businessId 业务ID
     * @param string|null $remark 备注
     * @param array $extraData 额外数据
     * @return CreditTransactionInterface 交易记录
     */
    public function expireCredits(
        AccountInterface $account, 
        int $amount, 
        string $businessCode, 
        ?string $businessId = null, 
        ?string $remark = null, 
        array $extraData = []
    ): CreditTransactionInterface;

    /**
     * 批量增加积分
     *
     * @param array $accounts 账户列表
     * @param int $amount 增加金额
     * @param string $businessCode 业务码
     * @param string|null $businessId 业务ID
     * @param string|null $remark 备注
     * @param array $extraData 额外数据
     * @return array 交易记录列表
     */
    public function batchAddCredits(
        array $accounts, 
        int $amount, 
        string $businessCode, 
        ?string $businessId = null, 
        ?string $remark = null, 
        array $extraData = []
    ): array;

    /**
     * 批量扣减积分
     *
     * @param array $accounts 账户列表
     * @param int $amount 扣减金额
     * @param string $businessCode 业务码
     * @param string|null $businessId 业务ID
     * @param string|null $remark 备注
     * @param array $extraData 额外数据
     * @return array 交易记录列表
     */
    public function batchDeductCredits(
        array $accounts, 
        int $amount, 
        string $businessCode, 
        ?string $businessId = null, 
        ?string $remark = null, 
        array $extraData = []
    ): array;

    /**
     * 检查账户余额是否足够
     *
     * @param UserInterface $user 用户
     * @param string $creditTypeId 积分类型ID
     * @param int $amount 所需金额
     * @return bool 是否足够
     */
    public function hasEnoughCredits(
        UserInterface $user, 
        string $creditTypeId, 
        int $amount
    ): bool;
}
