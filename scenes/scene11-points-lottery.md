# 场景十一：积分抽奖活动

## 业务流程

1. 用户参与积分抽奖活动
2. 系统扣除用户相应积分
3. 系统根据奖品概率进行抽奖
4. 用户获得奖励（积分、实物、优惠券等）

## 实现方式

通过积分抽奖提高用户积分使用率，增加平台互动性和趣味性，同时回收部分积分。

## API调用流程

```php
// 1. 用户选择积分抽奖活动并参与
$lotteryId = 'LOTTERY202301';
$drawId = generateUniqueId();
$costPoints = 100;  // 每次抽奖消耗积分

// 2. 获取抽奖活动信息
$lotteryInfo = $lotteryService->getLotteryInfo($lotteryId);
if (!$lotteryInfo['active']) {
    throw new \Exception('抽奖活动已结束');
}

// 3. 检查用户积分是否足够
$hasEnoughCredits = $creditService->operation()->hasEnoughCredits(
    $user,
    $creditTypeId,
    $costPoints
);

if (!$hasEnoughCredits) {
    throw new \Exception('积分不足，无法参与抽奖');
}

// 4. 开始事务处理
startTransaction();

try {
    // 5. 扣减用户积分
    $deductTransaction = $creditService->operation()->deductCredits(
        $userAccount,
        $costPoints,
        'lottery.participate',
        $drawId,
        '参与抽奖活动：'.$lotteryInfo['name'],
        [
            'lottery_id' => $lotteryId,
            'lottery_name' => $lotteryInfo['name']
        ]
    );
    
    // 6. 执行抽奖逻辑
    $prizeResult = $lotteryService->drawLottery($lotteryId, $user->getId());
    
    // 7. 处理中奖结果
    $prizeId = $prizeResult['prize_id'];
    $prizeName = $prizeResult['prize_name'];
    $prizeType = $prizeResult['prize_type'];
    $prizeValue = $prizeResult['prize_value'];
    
    // 8. 根据奖品类型发放不同奖励
    switch ($prizeType) {
        case 'credit':  // 积分奖励
            $creditTransaction = $creditService->operation()->addCredits(
                $userAccount,
                $prizeValue,
                'lottery.prize',
                $drawId,
                '抽奖获得积分奖励',
                [
                    'lottery_id' => $lotteryId,
                    'draw_id' => $drawId,
                    'prize_id' => $prizeId,
                    'prize_name' => $prizeName
                ]
            );
            break;
            
        case 'coupon':  // 优惠券奖励
            $coupon = $couponService->createCoupon([
                'user_id' => $user->getId(),
                'type' => $prizeResult['coupon_type'],
                'value' => $prizeValue,
                'valid_days' => 15,
                'source' => 'lottery_prize',
                'source_id' => $drawId
            ]);
            break;
            
        case 'physical':  // 实物奖励
            $shippingAddress = $user->getDefaultAddress();
            if (!$shippingAddress) {
                // 需要用户补充收货地址
                $requireAddress = true;
            }
            
            // 创建奖品发货记录
            $shipment = $shipmentService->createPrizeShipment([
                'user_id' => $user->getId(),
                'prize_id' => $prizeId,
                'prize_name' => $prizeName,
                'lottery_id' => $lotteryId,
                'draw_id' => $drawId,
                'address_id' => $shippingAddress ? $shippingAddress->getId() : null,
                'status' => $shippingAddress ? 'pending' : 'waiting_address'
            ]);
            break;
    }
    
    // 9. 记录抽奖结果
    $drawRecord = $lotteryService->recordDraw([
        'draw_id' => $drawId,
        'user_id' => $user->getId(),
        'lottery_id' => $lotteryId,
        'cost_points' => $costPoints,
        'transaction_id' => $deductTransaction->getId(),
        'prize_id' => $prizeId,
        'prize_name' => $prizeName,
        'prize_type' => $prizeType,
        'prize_value' => $prizeValue,
        'draw_time' => new \DateTime(),
        'result_data' => json_encode($prizeResult)
    ]);
    
    // 10. 提交事务
    commitTransaction();
    
    // 11. 返回抽奖结果
    return [
        'success' => true,
        'draw_id' => $drawId,
        'prize_name' => $prizeName,
        'prize_type' => $prizeType,
        'prize_value' => $prizeValue,
        'require_address' => $requireAddress ?? false,
        'message' => '恭喜获得：'.$prizeName
    ];
} catch (\Exception $e) {
    // 12. 异常处理，回滚事务
    rollbackTransaction();
    throw $e;
}
```

