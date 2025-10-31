<?php

declare(strict_types=1);

namespace Tourze\CreditServiceContracts;

/**
 * 积分类型接口
 *
 * 定义了不同种类积分的基本属性和行为
 */
interface CreditTypeInterface
{
    /**
     * 获取积分类型ID
     */
    public function getId(): string;

    /**
     * 获取积分类型名称
     */
    public function getName(): string;

    /**
     * 获取积分类型代码
     */
    public function getCode(): string;

    /**
     * 获取积分单位名称
     */
    public function getUnitName(): string;

    /**
     * 获取积分过期策略
     */
    public function getExpirationPolicy(): ?string;

    /**
     * 获取积分有效期（天数）
     *
     * 为null表示永不过期
     */
    public function getValidityPeriod(): ?int;

    /**
     * 获取积分类型描述
     */
    public function getDescription(): ?string;

    /**
     * 获取积分类型图标URL
     */
    public function getIconUrl(): ?string;

    /**
     * 获取积分类型状态
     */
    public function isValid(): bool;

    /**
     * 获取积分类型创建时间
     */
    public function getCreateTime(): \DateTimeInterface;

    /**
     * 获取积分类型更新时间
     */
    public function getUpdateTime(): \DateTimeInterface;

    /**
     * 获取该积分类型的附加属性
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array;
}
