# 场景十：签到打卡连续累积奖励

## 业务流程

1. 用户每日登录APP进行签到
2. 系统检查用户连续签到天数
3. 根据连续天数给予递增的积分奖励
4. 达到里程碑天数（如7天、30天）给予额外奖励

## 实现方式

通过连续累积奖励机制提高用户活跃度和留存率，鼓励用户形成每日使用习惯。

## API调用流程

```php
// 1. 用户进行每日签到
$checkInId = generateUniqueId();
$userId = $user->getId();
$checkInDate = new \DateTime();

// 2. 检查是否已经签到
$todayChecked = $checkInService->hasCheckedToday($userId);
if ($todayChecked) {
    return [
        'success' => false,
        'message' => '今日已签到，请明天再来'
    ];
}

// 3. 获取用户连续签到记录
$continuousDays = $checkInService->getContinuousCheckInDays($userId);
$newContinuousDays = $continuousDays + 1;

// 4. 计算积分奖励（基础奖励+连续奖励+里程碑奖励）
$basePoints = 5;  // 基础积分
$continuousBonus = min(15, $newContinuousDays - 1);  // 连续签到加分，最多15分

// 检查是否达到里程碑
$milestoneBonus = 0;
$milestone = '';
if ($newContinuousDays == 7) {
    $milestoneBonus = 30;
    $milestone = '连续签到7天';
} elseif ($newContinuousDays == 30) {
    $milestoneBonus = 100;
    $milestone = '连续签到30天';
} elseif ($newContinuousDays == 365) {
    $milestoneBonus = 1000;
    $milestone = '连续签到365天';
}

$totalPoints = $basePoints + $continuousBonus + $milestoneBonus;

// 5. 记录签到信息
$checkInRecord = $checkInService->recordCheckIn([
    'user_id' => $userId,
    'check_in_id' => $checkInId,
    'check_in_date' => $checkInDate,
    'continuous_days' => $newContinuousDays,
    'points_earned' => $totalPoints,
    'has_milestone' => !empty($milestone)
]);

// 6. 发放积分奖励
$transaction = $creditService->operation()->addCredits(
    $userAccount,
    $totalPoints,
    'daily.check_in',
    $checkInId,
    '每日签到奖励' . (!empty($milestone) ? '（'.$milestone.'）' : ''),
    [
        'check_in_date' => $checkInDate->format('Y-m-d'),
        'continuous_days' => $newContinuousDays,
        'base_points' => $basePoints,
        'continuous_bonus' => $continuousBonus,
        'milestone_bonus' => $milestoneBonus,
        'milestone' => $milestone
    ]
);

// 7. 返回签到结果
return [
    'success' => true,
    'continuous_days' => $newContinuousDays,
    'today_points' => $totalPoints,
    'base_points' => $basePoints,
    'continuous_bonus' => $continuousBonus,
    'milestone_bonus' => $milestoneBonus,
    'milestone' => $milestone,
    'next_milestone' => $nextMilestone(),
    'message' => '签到成功，获得'.$totalPoints.'积分' . (!empty($milestone) ? '，恭喜达成'.$milestone.'！' : '')
];
```

