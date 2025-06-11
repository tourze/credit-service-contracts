## 场景八：积分兑换代金券

### 业务流程

1. 用户申请将积分兑换为代金券
2. 系统验证积分余额并扣减积分
3. 系统生成相应面额的代金券
4. 用户接收代金券并可用于后续消费抵扣

### 实现方式

此场景将积分系统与代金券系统对接，实现资产形式的转换，扩展积分的使用场景。

### API调用流程

```php
// 1. 用户选择积分兑换代金券方案
$exchangeId = generateUniqueId();
$creditAmount = 1000;  // 消耗积分数量
$couponValue = 50;     // 代金券面额（元）
$couponType = 'universal_coupon';  // 代金券类型：通用券

// 2. 检查用户积分余额
$hasEnoughCredits = $creditService->operation()->hasEnoughCredits(
    $user,
    $creditTypeId,
    $creditAmount
);

if (!$hasEnoughCredits) {
    throw new \Exception('积分余额不足');
}

// 3. 开始事务处理
startTransaction();

try {
    // 4. 扣减用户积分
    $deductTransaction = $creditService->operation()->deductCredits(
        $userAccount,
        $creditAmount,
        'credit.exchange_coupon',
        $exchangeId,
        '积分兑换'.$couponValue.'元代金券',
        [
            'coupon_value' => $couponValue,
            'coupon_type' => $couponType
        ]
    );
    
    // 5. 生成代金券
    $coupon = $couponService->createCoupon([
        'user_id' => $user->getId(),
        'type' => $couponType,
        'value' => $couponValue,
        'valid_days' => 30,  // 有效期30天
        'source' => 'credit_exchange',
        'source_id' => $exchangeId,
        'transaction_id' => $deductTransaction->getId()
    ]);
    
    // 6. 记录兑换关系
    $exchangeRecord = [
        'exchange_id' => $exchangeId,
        'user_id' => $user->getId(),
        'credit_amount' => $creditAmount,
        'coupon_id' => $coupon->getId(),
        'coupon_value' => $couponValue,
        'exchange_time' => new \DateTime(),
        'transaction_id' => $deductTransaction->getId()
    ];
    saveExchangeRecord($exchangeRecord);
    
    // 7. 提交事务
    commitTransaction();
    
    // 8. 通知用户兑换成功
    $notificationService->notify(
        $user,
        '积分兑换成功',
        '您已成功使用'.$creditAmount.'积分兑换'.$couponValue.'元代金券，请在个人中心查看'
    );
    
    return [
        'success' => true,
        'exchange_id' => $exchangeId,
        'credit_amount' => $creditAmount,
        'coupon_value' => $couponValue,
        'coupon_id' => $coupon->getId(),
        'valid_until' => $coupon->getExpiryDate()->format('Y-m-d H:i:s')
    ];
} catch (\Throwable $e) {
    // 9. 异常处理，回滚事务
    rollbackTransaction();
    throw $e;
}
```
