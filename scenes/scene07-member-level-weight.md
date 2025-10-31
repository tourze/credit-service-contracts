## 场景七：会员等级积分加权计算

### 业务流程

1. 用户购买商品
2. 系统根据用户的会员等级（普通、银卡、金卡、钻石等）计算积分加权
3. 系统根据加权规则赠送不同数量的积分

### 实现方式

此场景通过会员等级差异化积分奖励策略，提高高级会员的权益感知，促进用户升级会员等级。

### API调用流程

```php
// 1. 用户购买商品完成支付
$orderId = 'ORDER987654';
$orderAmount = 1000; // 订单金额（元）
$baseReward = $orderAmount * 10; // 基础积分（1元=10积分）

// 2. 获取用户会员等级及对应积分加权倍数
$memberLevel = $memberService->getUserLevel($user);
$weightMap = [
    'normal' => 1.0,    // 普通会员 1倍积分
    'silver' => 1.2,    // 银卡会员 1.2倍积分
    'gold' => 1.5,      // 金卡会员 1.5倍积分
    'diamond' => 2.0    // 钻石会员 2倍积分
];

$weightRatio = $weightMap[$memberLevel] ?? 1.0;
$actualReward = round($baseReward * $weightRatio);

// 3. 赠送积分
$rewardTransaction = $creditService->operation()->addCredits(
    $userAccount,
    $actualReward,
    'order.reward',
    $orderId,
    '购物返积分（会员等级：'.$memberLevel.'）',
    [
        'order_amount' => $orderAmount,
        'base_reward' => $baseReward,
        'member_level' => $memberLevel,
        'weight_ratio' => $weightRatio
    ]
);

// 4. 返回积分赠送结果
return [
    'success' => true,
    'member_level' => $memberLevel,
    'base_points' => $baseReward,
    'weight_ratio' => $weightRatio,
    'actual_reward' => $actualReward,
    'message' => '已成功赠送'.$actualReward.'积分（含会员'.$weightRatio.'倍加权）'
];
```
