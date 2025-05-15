<?php

namespace Tourze\CreditServiceContracts\Service;

use Tourze\CreditServiceContracts\CreditTypeInterface;

/**
 * 积分类型服务接口
 */
interface CreditTypeServiceInterface
{
    /**
     * 根据ID获取积分类型
     *
     * @param string $typeId 类型ID
     * @return CreditTypeInterface|null 积分类型，不存在时返回null
     */
    public function getCreditTypeById(string $typeId): ?CreditTypeInterface;

    /**
     * 根据代码获取积分类型
     *
     * @param string $code 类型代码
     * @return CreditTypeInterface|null 积分类型，不存在时返回null
     */
    public function getCreditTypeByCode(string $code): ?CreditTypeInterface;

    /**
     * 获取所有积分类型
     *
     * @param bool $onlyValid 是否只返回有效的
     * @return CreditTypeInterface[] 积分类型列表
     */
    public function getAllCreditTypes(bool $onlyValid = true): array;
}
