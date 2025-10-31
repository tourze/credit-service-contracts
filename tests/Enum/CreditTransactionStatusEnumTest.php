<?php

declare(strict_types=1);

namespace Tourze\CreditServiceContracts\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\CreditServiceContracts\Enum\CreditTransactionStatusEnum;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(CreditTransactionStatusEnum::class)]
final class CreditTransactionStatusEnumTest extends AbstractEnumTestCase
{
    /**
     * 测试枚举值是否能被正确转换为数组
     */
    public function testToArrayConvertsEnumToArrayCorrectly(): void
    {
        $expectedItems = [
            'value' => 0,
            'label' => '待处理',
        ];

        $this->assertEquals($expectedItems, CreditTransactionStatusEnum::PENDING->toArray());
    }

    /**
     * 测试toSelectItem方法是否正确返回选择项格式
     */
    public function testToSelectItemReturnsCorrectSelectFormat(): void
    {
        $expectedSelectItem = [
            'value' => 1,
            'label' => '已完成',
            'text' => '已完成',
            'name' => '已完成',
        ];

        $this->assertEquals($expectedSelectItem, CreditTransactionStatusEnum::COMPLETED->toSelectItem());
    }
}
