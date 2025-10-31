<?php

declare(strict_types=1);

namespace Tourze\CreditServiceContracts\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 积分交易状态枚举
 */
enum CreditTransactionStatusEnum: int implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    /**
     * 待处理
     */
    case PENDING = 0;

    /**
     * 已完成
     */
    case COMPLETED = 1;

    /**
     * 失败
     */
    case FAILED = 2;

    /**
     * 已取消
     */
    case CANCELLED = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::COMPLETED => '已完成',
            self::FAILED => '失败',
            self::CANCELLED => '已取消',
        };
    }
}
