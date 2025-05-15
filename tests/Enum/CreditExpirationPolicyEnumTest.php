<?php

namespace Tourze\CreditServiceContracts\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\CreditServiceContracts\Enum\CreditExpirationPolicyEnum;

class CreditExpirationPolicyEnumTest extends TestCase
{
    /**
     * 测试枚举值是否存在且正确
     */
    public function testEnumValues_areCorrectlyDefined()
    {
        $this->assertSame('never_expire', CreditExpirationPolicyEnum::NEVER_EXPIRE->value);
        $this->assertSame('fixed_days', CreditExpirationPolicyEnum::FIXED_DAYS->value);
        $this->assertSame('fixed_date', CreditExpirationPolicyEnum::FIXED_DATE->value);
        $this->assertSame('end_of_month', CreditExpirationPolicyEnum::END_OF_MONTH->value);
        $this->assertSame('end_of_quarter', CreditExpirationPolicyEnum::END_OF_QUARTER->value);
        $this->assertSame('end_of_year', CreditExpirationPolicyEnum::END_OF_YEAR->value);
        $this->assertSame('fifo', CreditExpirationPolicyEnum::FIFO->value);
    }

    /**
     * 测试getLabel方法是否返回正确的中文标签
     */
    public function testGetLabel_returnsCorrectChineseLabels()
    {
        $this->assertSame('永不过期', CreditExpirationPolicyEnum::NEVER_EXPIRE->getLabel());
        $this->assertSame('固定天数后过期', CreditExpirationPolicyEnum::FIXED_DAYS->getLabel());
        $this->assertSame('固定日期过期', CreditExpirationPolicyEnum::FIXED_DATE->getLabel());
        $this->assertSame('月底过期', CreditExpirationPolicyEnum::END_OF_MONTH->getLabel());
        $this->assertSame('季度末过期', CreditExpirationPolicyEnum::END_OF_QUARTER->getLabel());
        $this->assertSame('年底过期', CreditExpirationPolicyEnum::END_OF_YEAR->getLabel());
        $this->assertSame('先进先出过期', CreditExpirationPolicyEnum::FIFO->getLabel());
    }

    /**
     * 测试toArray方法是否正确返回枚举值和标签
     */
    public function testToArray_convertsEnumToArrayCorrectly()
    {
        $expectedArray = [
            'value' => 'never_expire',
            'label' => '永不过期',
        ];

        $this->assertEquals($expectedArray, CreditExpirationPolicyEnum::NEVER_EXPIRE->toArray());
    }

    /**
     * 测试toItems方法是否返回所有枚举项的值和标签
     */
    public function testToItems_returnsCorrectLabeledArray()
    {
        $expectedItems = [
            ['value' => 'never_expire', 'label' => '永不过期'],
            ['value' => 'fixed_days', 'label' => '固定天数后过期'],
            ['value' => 'fixed_date', 'label' => '固定日期过期'],
            ['value' => 'end_of_month', 'label' => '月底过期'],
            ['value' => 'end_of_quarter', 'label' => '季度末过期'],
            ['value' => 'end_of_year', 'label' => '年底过期'],
            ['value' => 'fifo', 'label' => '先进先出过期'],
        ];

        $this->assertEquals($expectedItems, CreditExpirationPolicyEnum::toItems());
    }

    /**
     * 测试toSelect方法是否返回正确的select选项数组
     */
    public function testToSelect_returnsCorrectSelectOptions()
    {
        $expectedSelect = [
            'never_expire' => '永不过期',
            'fixed_days' => '固定天数后过期',
            'fixed_date' => '固定日期过期',
            'end_of_month' => '月底过期',
            'end_of_quarter' => '季度末过期',
            'end_of_year' => '年底过期',
            'fifo' => '先进先出过期',
        ];

        $this->assertSame($expectedSelect, CreditExpirationPolicyEnum::toSelect());
    }
} 