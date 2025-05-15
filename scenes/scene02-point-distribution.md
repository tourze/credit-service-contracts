# 场景二：用户积分购买商品后分账

## 业务流程

1. 用户A使用积分购买商品
2. 系统实际扣减用户A的积分
3. 系统需要将这笔积分分给多个商家/用户（B、C、D）

## 实现方式

此场景采用"支出-多次收入"模式实现。先从用户账户扣减积分，然后根据分账规则依次增加各接收方的积分。

## API调用流程

```php
// 1. 用户支付积分购买商品
$orderId = 'ORDER789012';
$totalAmount = 1000; // 订单总积分

// 扣减用户积分
$expenseTransaction = $creditService->operation()->deductCredits(
    $userAccount,
    $totalAmount,
    'order.payment',
    $orderId,
    '购买商品',
    ['product_id' => 'PROD456']
);

// 2. 分账明细
$distributions = [
    ['account' => $merchantAccount1, 'amount' => 500, 'reason' => '商品销售分成'],
    ['account' => $merchantAccount2, 'amount' => 300, 'reason' => '平台分成'],
    ['account' => $referrerAccount, 'amount' => 200, 'reason' => '推荐奖励']
];

// 3. 执行分账
$distributionTransactions = [];
foreach ($distributions as $dist) {
    $transaction = $creditService->operation()->addCredits(
        $dist['account'],
        $dist['amount'],
        'order.distribution',
        $orderId,
        $dist['reason'],
        [
            'source_transaction_id' => $expenseTransaction->getId(),
            'distribution_type' => 'order_revenue'
        ]
    );
    $distributionTransactions[] = $transaction;
}

// 4. 记录分账关系
saveDistributionRelationship($orderId, $expenseTransaction->getId(), $distributionTransactions);
```
