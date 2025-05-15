<?php

namespace Tourze\CreditServiceContracts\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 积分过期策略枚举
 */
enum CreditExpirationPolicyEnum: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    /**
     * 永不过期
     */
    case NEVER_EXPIRE = 'never_expire';

    /**
     * 固定天数后过期
     *
     * 从获得积分时开始计算固定天数
     */
    case FIXED_DAYS = 'fixed_days';

    /**
     * 固定日期过期
     *
     * 指定具体过期日期，如年底过期
     */
    case FIXED_DATE = 'fixed_date';

    /**
     * 月底过期
     *
     * 获得积分的当月月底过期
     */
    case END_OF_MONTH = 'end_of_month';

    /**
     * 季度末过期
     *
     * 获得积分的当季度末过期
     */
    case END_OF_QUARTER = 'end_of_quarter';

    /**
     * 年底过期
     *
     * 获得积分的当年年底过期
     */
    case END_OF_YEAR = 'end_of_year';

    /**
     * 先进先出过期
     *
     * 最早获得的积分最先过期
     */
    case FIFO = 'fifo';

    public function getLabel(): string
    {
        return match ($this) {
            self::NEVER_EXPIRE => '永不过期',
            self::FIXED_DAYS => '固定天数后过期',
            self::FIXED_DATE => '固定日期过期',
            self::END_OF_MONTH => '月底过期',
            self::END_OF_QUARTER => '季度末过期',
            self::END_OF_YEAR => '年底过期',
            self::FIFO => '先进先出过期',
        };
    }
}
