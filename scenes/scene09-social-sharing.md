## 场景九：社交分享获取积分

### 业务流程

1. 用户在社交媒体分享商品或活动
2. 其他用户通过分享链接访问或购买
3. 系统根据社交分享效果给予分享者积分奖励

### 实现方式

此场景鼓励用户进行社交传播，扩大产品影响力，同时通过积分激励强化用户的分享行为。

### API调用流程

```php
// 1. 用户完成社交分享，生成唯一分享ID
$shareId = generateUniqueId();
$platform = 'weixin';  // 分享平台

// 记录分享行为
$shareRecord = $socialService->recordShare([
    'user_id' => $user->getId(),
    'share_id' => $shareId,
    'platform' => $platform,
    'content_type' => 'product',
    'content_id' => $productId,
    'share_time' => new \DateTime()
]);

// 2. 其他用户通过分享链接点击或购买（通过异步事件处理）
// 当检测到分享链接带来的点击或转化时触发下面的流程

// 3. 根据分享效果计算奖励积分
$rewardType = 'share_click';  // 分享点击，还可能是share_purchase（分享购买）
$basePoints = ($rewardType == 'share_click') ? 10 : 50;  // 点击奖励10分，购买奖励50分

// 查询该分享链接已产生的奖励次数，控制奖励上限
$rewardCount = $socialService->getShareRewardCount($shareId, $rewardType);
$maxRewardTimes = ($rewardType == 'share_click') ? 20 : 5;  // 最多奖励20次点击或5次购买

if ($rewardCount >= $maxRewardTimes) {
    // 超出奖励上限，不再给予积分
    return [
        'success' => false,
        'message' => '该分享链接的'.$rewardType.'奖励已达上限'
    ];
}

// 4. 增加用户积分
$rewardTransaction = $creditService->operation()->addCredits(
    $userAccount,
    $basePoints,
    'social.share_reward',
    $shareId,
    '社交分享奖励（'.$rewardType.'）',
    [
        'share_id' => $shareId,
        'platform' => $platform,
        'reward_type' => $rewardType,
        'reward_count' => $rewardCount + 1
    ]
);

// 5. 更新分享效果统计
$socialService->updateShareStatistics($shareId, [
    $rewardType => $rewardCount + 1,
    'last_reward_time' => new \DateTime(),
    'total_reward_points' => ($rewardCount + 1) * $basePoints
]);

return [
    'success' => true,
    'reward_type' => $rewardType,
    'points' => $basePoints,
    'reward_count' => $rewardCount + 1,
    'message' => '社交分享奖励积分已发放'
];
```
