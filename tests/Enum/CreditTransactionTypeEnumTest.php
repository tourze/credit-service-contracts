<?php

declare(strict_types=1);

namespace Tourze\CreditServiceContracts\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\CreditServiceContracts\Enum\CreditTransactionTypeEnum;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(CreditTransactionTypeEnum::class)]
final class CreditTransactionTypeEnumTest extends AbstractEnumTestCase
{
    /**
     * 测试toArray方法是否正确返回枚举值和标签
     */
    public function testToArrayConvertsEnumToArrayCorrectly(): void
    {
        $expectedArray = [
            'value' => 1,
            'label' => '收入',
        ];

        $this->assertEquals($expectedArray, CreditTransactionTypeEnum::INCOME->toArray());
    }

    /**
     * 测试toSelectItem方法是否正确返回选择项格式
     */
    public function testToSelectItemReturnsCorrectSelectFormat(): void
    {
        $expectedSelectItem = [
            'value' => 2,
            'label' => '支出',
            'text' => '支出',
            'name' => '支出',
        ];

        $this->assertEquals($expectedSelectItem, CreditTransactionTypeEnum::EXPENSE->toSelectItem());
    }
}
