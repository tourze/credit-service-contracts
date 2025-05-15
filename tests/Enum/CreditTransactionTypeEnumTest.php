<?php

namespace Tourze\CreditServiceContracts\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\CreditServiceContracts\Enum\CreditTransactionTypeEnum;

class CreditTransactionTypeEnumTest extends TestCase
{
    /**
     * 测试枚举值是否存在且正确
     */
    public function testEnumValues_areCorrectlyDefined()
    {
        $this->assertSame(1, CreditTransactionTypeEnum::INCOME->value);
        $this->assertSame(2, CreditTransactionTypeEnum::EXPENSE->value);
        $this->assertSame(3, CreditTransactionTypeEnum::FROZEN->value);
        $this->assertSame(4, CreditTransactionTypeEnum::UNFROZEN->value);
        $this->assertSame(5, CreditTransactionTypeEnum::EXPIRED->value);
    }

    /**
     * 测试getLabel方法是否返回正确的中文标签
     */
    public function testGetLabel_returnsCorrectChineseLabels()
    {
        $this->assertSame('收入', CreditTransactionTypeEnum::INCOME->getLabel());
        $this->assertSame('支出', CreditTransactionTypeEnum::EXPENSE->getLabel());
        $this->assertSame('冻结', CreditTransactionTypeEnum::FROZEN->getLabel());
        $this->assertSame('解冻', CreditTransactionTypeEnum::UNFROZEN->getLabel());
        $this->assertSame('过期', CreditTransactionTypeEnum::EXPIRED->getLabel());
    }

    /**
     * 测试toArray方法是否正确返回枚举值和标签
     */
    public function testToArray_convertsEnumToArrayCorrectly()
    {
        $expectedArray = [
            'value' => 1,
            'label' => '收入',
        ];

        $this->assertEquals($expectedArray, CreditTransactionTypeEnum::INCOME->toArray());
    }
} 