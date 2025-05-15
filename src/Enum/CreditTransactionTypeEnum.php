<?php

namespace Tourze\CreditServiceContracts\Enum;

use Tourze\EnumExtra\EnumTrait;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 积分交易类型枚举
 */
enum CreditTransactionTypeEnum: int implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    use EnumTrait;

    /**
     * 收入
     */
    case INCOME = 1;

    /**
     * 支出
     */
    case EXPENSE = 2;

    /**
     * 冻结
     */
    case FROZEN = 3;

    /**
     * 解冻
     */
    case UNFROZEN = 4;

    /**
     * 过期
     */
    case EXPIRED = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::INCOME => '收入',
            self::EXPENSE => '支出',
            self::FROZEN => '冻结',
            self::UNFROZEN => '解冻',
            self::EXPIRED => '过期',
        };
    }
}
