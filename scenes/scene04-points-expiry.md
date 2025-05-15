# 场景四：积分过期导致兑换失败

## 业务流程

1. 用户获得了10000积分
2. 一段时间后，9000积分过期
3. 用户尝试兑换价值1500积分的商品
4. 系统检测到可用积分不足，兑换失败并提示用户

## 实现方式

此场景需要跟踪积分的有效期，在积分到期时标记为过期，并确保在检查余额时只计算有效积分。

## API调用流程

```php
// 1. 用户获得10000积分（假设在过去已经执行）
// ...

// 2. 积分过期处理（系统定时任务执行）
// 查询需要过期的积分
$expiringAmount = 9000;

// 执行过期操作
$expiryTransaction = $creditService->operation()->expireCredits(
    $userAccount,
    $expiringAmount,
    'credit.expiry',
    null,
    '积分到期自动过期',
    ['expiry_date' => date('Y-m-d')]
);

// 3. 用户尝试兑换商品
$exchangeAmount = 1500;
$productId = 'PROD123';

// 4. 检查可用积分是否足够（会考虑已过期和冻结的积分）
$hasEnoughCredits = $creditService->operation()->hasEnoughCredits(
    $user,
    $creditTypeId,
    $exchangeAmount
);

// 5. 处理兑换请求
if ($hasEnoughCredits) {
    // 执行兑换逻辑
    // ...
} else {
    // 返回错误信息给用户
    return [
        'success' => false,
        'error_code' => 'INSUFFICIENT_CREDITS',
        'message' => '积分不足，兑换失败。您当前的有效积分为: ' . $userAccount->getAvailableBalance(),
        'available_balance' => $userAccount->getAvailableBalance(),
        'required_amount' => $exchangeAmount,
        'expired_amount' => $expiringAmount
    ];
}
```
