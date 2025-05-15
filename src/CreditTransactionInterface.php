<?php

namespace Tourze\CreditServiceContracts;

use DateTimeInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\CreditServiceContracts\Enum\CreditTransactionStatusEnum;
use Tourze\CreditServiceContracts\Enum\CreditTransactionTypeEnum;
use Tourze\UserIDBundle\Contracts\AccountInterface;

/**
 * 积分交易接口
 *
 * 定义了积分交易记录的基本属性和行为
 */
interface CreditTransactionInterface
{
    /**
     * 获取交易ID
     */
    public function getId(): string;

    /**
     * 获取用户
     */
    public function getUser(): UserInterface;

    /**
     * 获取积分账户
     */
    public function getAccount(): AccountInterface;

    /**
     * 获取积分类型ID
     */
    public function getCreditTypeId(): string;

    /**
     * 获取交易类型
     *
     * @return CreditTransactionTypeEnum 返回交易类型枚举
     */
    public function getType(): CreditTransactionTypeEnum;

    /**
     * 获取交易金额（积分数量）
     */
    public function getAmount(): int;

    /**
     * 获取交易前余额
     */
    public function getBeforeBalance(): int;

    /**
     * 获取交易后余额
     */
    public function getAfterBalance(): int;

    /**
     * 获取业务码
     *
     * 用于标识积分来源/使用场景
     */
    public function getBusinessCode(): string;

    /**
     * 获取业务ID
     *
     * 关联的具体业务数据ID
     */
    public function getBusinessId(): ?string;

    /**
     * 获取交易备注
     */
    public function getRemark(): ?string;

    /**
     * 获取交易状态
     *
     * @return CreditTransactionStatusEnum 返回交易状态枚举
     */
    public function getStatus(): CreditTransactionStatusEnum;

    /**
     * 获取操作者ID
     */
    public function getOperatorId(): ?string;

    /**
     * 获取交易批次号
     */
    public function getBatchNo(): ?string;

    /**
     * 获取交易创建时间
     */
    public function getCreateTime(): DateTimeInterface;

    /**
     * 获取交易完成时间
     */
    public function getCompleteTime(): ?DateTimeInterface;

    /**
     * 获取积分有效期
     *
     * 针对有有效期的积分交易
     */
    public function getExpiryTime(): ?DateTimeInterface;

    /**
     * 获取交易IP地址
     */
    public function getIpAddress(): ?string;

    /**
     * 获取交易来源
     *
     * 例如：APP、网页、小程序等
     */
    public function getSource(): ?string;

    /**
     * 获取交易设备信息
     */
    public function getDevice(): ?string;

    /**
     * 获取交易额外数据
     */
    public function getExtraData(): array;
}
