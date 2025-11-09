## 场景五：购物返积分且设置积分有效期

### 业务流程

1. 用户购买商品
2. 系统根据购买金额赠送积分，并设置积分有效期
3. 用户查询自己的积分明细，包括即将过期的积分提醒

### 实现方式

此场景重点在于积分的创建时要设置有效期，并提供查询接口让用户了解自己的积分状态。

### API调用流程

```php
// 1. 用户购买商品完成支付后，系统赠送积分
$orderId = 'ORDER567890';
$orderAmount = 1000; // 订单金额（元）
$creditReward = $orderAmount * 10; // 假设按照1元=10积分的比例赠送
$validDays = 90; // 有效期90天

// 计算过期时间
$expiryTime = new \DateTime();
$expiryTime->modify('+' . $validDays . ' days');

// 2. 增加积分，并在extraData中记录过期时间
$rewardTransaction = $creditService->operation()->addCredits(
    $userAccount,
    $creditReward,
    'order.reward',
    $orderId,
    '购物返积分',
    [
        'order_amount' => $orderAmount,
        'expiry_time' => $expiryTime->format('Y-m-d H:i:s'),
        'valid_days' => $validDays
    ]
);

// 3. 用户查询积分明细，包括过期提醒
// 获取用户最近的积分交易记录
$transactions = $creditService->transaction()->getUserTransactionsByType(
    $user,
    CreditTransactionTypeEnum::INCOME,
    [],
    1,
    50
);

// 4. 筛选即将过期的积分（假设30天内过期的需要提醒）
$warningDate = new \DateTime();
$warningDate->modify('+30 days');

$expiringTransactions = [];
foreach ($transactions['items'] as $transaction) {
    $extraData = $transaction->getExtraData();
    
    if (isset($extraData['expiry_time'])) {
        $expiryTime = new \DateTime($extraData['expiry_time']);
        
        if ($expiryTime <= $warningDate) {
            $expiringTransactions[] = [
                'id' => $transaction->getId(),
                'amount' => $transaction->getAmount(),
                'expiry_date' => $extraData['expiry_time'],
                'days_left' => $expiryTime->diff(new \DateTime())->days
            ];
        }
    }
}

// 5. 返回积分过期提醒给用户
if (!empty($expiringTransactions)) {
    return [
        'warning' => true,
        'message' => '您有积分即将过期，请及时使用',
        'expiring_credits' => $expiringTransactions
    ];
}
```
