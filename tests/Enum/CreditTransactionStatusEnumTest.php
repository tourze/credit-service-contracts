<?php

namespace Tourze\CreditServiceContracts\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\CreditServiceContracts\Enum\CreditTransactionStatusEnum;

class CreditTransactionStatusEnumTest extends TestCase
{
    /**
     * 测试枚举值是否存在且正确
     */
    public function testEnumValues_areCorrectlyDefined()
    {
        $this->assertSame(0, CreditTransactionStatusEnum::PENDING->value);
        $this->assertSame(1, CreditTransactionStatusEnum::COMPLETED->value);
        $this->assertSame(2, CreditTransactionStatusEnum::FAILED->value);
        $this->assertSame(3, CreditTransactionStatusEnum::CANCELLED->value);
    }

    /**
     * 测试getLabel方法是否返回正确的中文标签
     */
    public function testGetLabel_returnsCorrectChineseLabels()
    {
        $this->assertSame('待处理', CreditTransactionStatusEnum::PENDING->getLabel());
        $this->assertSame('已完成', CreditTransactionStatusEnum::COMPLETED->getLabel());
        $this->assertSame('失败', CreditTransactionStatusEnum::FAILED->getLabel());
        $this->assertSame('已取消', CreditTransactionStatusEnum::CANCELLED->getLabel());
    }

    /**
     * 测试枚举值是否能被正确转换为数组
     */
    public function testToArray_convertsEnumToArrayCorrectly()
    {
        $expectedItems = [
            'value' => 0,
            'label' => '待处理',
        ];

        $this->assertEquals($expectedItems, CreditTransactionStatusEnum::PENDING->toArray());
    }
}
