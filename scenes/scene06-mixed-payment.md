## 场景六：多种类型积分混合支付

### 业务流程

1. 用户想使用多种积分类型（如通用积分和专享积分）组合支付订单
2. 系统检查各类型积分余额是否足够
3. 系统按照优先规则扣减多种积分

### 实现方式

此场景需要处理多种积分类型的组合使用，关键在于设置合理的积分扣减优先级（如优先使用即将过期的积分、特定类型的积分等）。

### API调用流程

```php
// 1. 用户发起混合积分支付
$orderId = 'ORDER345678';
$totalRequired = 2000; // 订单总积分需求

// 获取用户的多种积分账户
$generalAccount = $accountService->getAccount($user, 'general'); // 通用积分
$exclusiveAccount = $accountService->getAccount($user, 'exclusive'); // 专享积分

// 2. 检查积分余额情况
$generalBalance = $generalAccount->getAvailableBalance();
$exclusiveBalance = $exclusiveAccount->getAvailableBalance();

// 3. 确定积分扣减方案（这里采用先用专享积分，再用通用积分的策略）
$deductionPlan = [];

if ($exclusiveBalance > 0) {
    $exclusiveAmount = min($exclusiveBalance, $totalRequired);
    $deductionPlan[] = [
        'account' => $exclusiveAccount,
        'amount' => $exclusiveAmount,
        'type' => 'exclusive'
    ];
    $totalRequired -= $exclusiveAmount;
}

if ($totalRequired > 0 && $generalBalance > 0) {
    $generalAmount = min($generalBalance, $totalRequired);
    $deductionPlan[] = [
        'account' => $generalAccount,
        'amount' => $generalAmount,
        'type' => 'general'
    ];
    $totalRequired -= $generalAmount;
}

// 4. 检查是否所有需要的积分都已经安排好扣除计划
if ($totalRequired > 0) {
    throw new \Exception('积分不足，无法完成支付');
}

// 5. 执行多种积分扣减
startTransaction();

try {
    $transactionIds = [];
    
    foreach ($deductionPlan as $plan) {
        $transaction = $creditService->operation()->deductCredits(
            $plan['account'],
            $plan['amount'],
            'order.payment',
            $orderId,
            $plan['type'] . '积分支付',
            [
                'credit_type' => $plan['type'],
                'order_id' => $orderId
            ]
        );
        
        $transactionIds[] = $transaction->getId();
    }
    
    // 6. 记录混合支付明细
    savePaymentDetails($orderId, $deductionPlan, $transactionIds);
    
    commitTransaction();
    
    return [
        'success' => true,
        'message' => '混合积分支付成功',
        'payment_details' => $deductionPlan
    ];
} catch (\Exception $e) {
    rollbackTransaction();
    throw $e;
}
```
