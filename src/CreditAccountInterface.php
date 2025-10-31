<?php

declare(strict_types=1);

namespace Tourze\CreditServiceContracts;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\UserIDBundle\Contracts\AccountInterface;

/**
 * 积分账户接口
 *
 * 定义了积分账户的基本操作和属性
 */
interface CreditAccountInterface
{
    /**
     * 获取账户ID
     */
    public function getId(): string;

    /**
     * 获取用户
     */
    public function getUser(): UserInterface;

    /**
     * 获取账户
     */
    public function getAccount(): AccountInterface;

    /**
     * 获取积分类型ID
     */
    public function getCreditTypeId(): string;

    /**
     * 获取当前积分余额
     */
    public function getBalance(): int;

    /**
     * 获取总收入积分
     */
    public function getTotalIncome(): int;

    /**
     * 获取总支出积分
     */
    public function getTotalExpense(): int;

    /**
     * 获取冻结积分
     */
    public function getFrozenAmount(): int;

    /**
     * 获取可用积分（余额减去冻结积分）
     */
    public function getAvailableBalance(): int;

    /**
     * 获取账户创建时间
     */
    public function getCreateTime(): \DateTimeInterface;

    /**
     * 获取账户更新时间
     */
    public function getUpdateTime(): \DateTimeInterface;

    /**
     * 获取账户状态
     */
    public function isValid(): bool;

    /**
     * 获取账户等级
     */
    public function getLevel(): int;

    /**
     * 获取账户备注
     */
    public function getRemark(): ?string;
}
