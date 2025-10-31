<?php

declare(strict_types=1);

namespace Tourze\CreditServiceContracts\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\CreditServiceContracts\Enum\CreditExpirationPolicyEnum;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(CreditExpirationPolicyEnum::class)]
final class CreditExpirationPolicyEnumTest extends AbstractEnumTestCase
{
    /**
     * 测试toArray方法是否正确返回枚举值和标签
     */
    public function testToArrayConvertsEnumToArrayCorrectly(): void
    {
        $expectedArray = [
            'value' => 'never_expire',
            'label' => '永不过期',
        ];

        $this->assertEquals($expectedArray, CreditExpirationPolicyEnum::NEVER_EXPIRE->toArray());
    }

    /**
     * 测试toSelectItem方法是否正确返回选择项格式
     */
    public function testToSelectItemReturnsCorrectSelectFormat(): void
    {
        $expectedSelectItem = [
            'value' => 'fixed_days',
            'label' => '固定天数后过期',
            'text' => '固定天数后过期',
            'name' => '固定天数后过期',
        ];

        $this->assertEquals($expectedSelectItem, CreditExpirationPolicyEnum::FIXED_DAYS->toSelectItem());
    }
}
