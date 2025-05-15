<?php

namespace Tourze\CreditServiceContracts\Service;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\CreditServiceContracts\CreditAccountInterface;
use Tourze\UserIDBundle\Contracts\AccountInterface;

/**
 * 积分账户服务接口
 */
interface CreditAccountServiceInterface
{
    /**
     * 获取用户特定积分类型的账户
     *
     * @param UserInterface $user 用户
     * @param string $creditTypeId 积分类型ID
     * @return CreditAccountInterface|null 积分账户，不存在时返回null
     */
    public function getAccount(UserInterface $user, string $creditTypeId): ?CreditAccountInterface;

    /**
     * 获取用户的所有积分账户
     *
     * @param UserInterface $user 用户
     * @return CreditAccountInterface[] 积分账户列表
     */
    public function getUserAccounts(UserInterface $user): array;

    /**
     * 根据账户ID获取账户
     *
     * @param AccountInterface $account 账户
     * @return CreditAccountInterface|null 积分账户，不存在时返回null
     */
    public function getAccountById(AccountInterface $account): ?CreditAccountInterface;
}
