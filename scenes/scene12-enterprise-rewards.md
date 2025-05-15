# 场景十二：企业组织内积分激励与分配

## 业务流程

1. 企业管理员设置团队激励任务与积分分配规则
2. 员工完成任务或达成目标
3. 系统根据绩效自动分配积分或管理员手动分配积分
4. 员工可使用积分兑换企业内部福利或实物奖励

## 实现方式

企业级积分体系有助于团队激励与文化建设，通过积分量化员工贡献，并提供多样化的积分使用场景。

## API调用流程

```php
// 1. 管理员分配积分给团队成员（例如季度绩效奖励）
$batchId = generateUniqueId();
$departmentId = 'DEPT123';
$occasion = '2023年Q2季度绩效奖励';
$totalPoints = 50000;  // 总积分池
$allocatorId = $admin->getId();

// 2. 获取部门成员及其绩效评分
$members = $orgService->getDepartmentMembers($departmentId);
$performanceScores = $hrService->getQuarterlyPerformance($departmentId, '2023Q2');

// 3. 根据绩效比例计算每个成员应得积分
$allocations = [];
$totalScore = array_sum(array_column($performanceScores, 'score'));

foreach ($members as $member) {
    $memberId = $member['id'];
    $memberAccount = $accountService->getAccount($memberId, 'performance');
    
    // 根据绩效评分计算积分分配比例
    $memberScore = $performanceScores[$memberId]['score'] ?? 0;
    $proportion = $totalScore > 0 ? $memberScore / $totalScore : 0;
    $pointsToAllocate = round($totalPoints * $proportion);
    
    if ($pointsToAllocate > 0) {
        $allocations[] = [
            'user_id' => $memberId,
            'account' => $memberAccount,
            'score' => $memberScore,
            'proportion' => $proportion,
            'points' => $pointsToAllocate
        ];
    }
}

// 4. 开始事务处理
startTransaction();

try {
    // 5. 为每个成员增加积分
    $transactionIds = [];
    foreach ($allocations as $allocation) {
        $transaction = $creditService->operation()->addCredits(
            $allocation['account'],
            $allocation['points'],
            'org.performance_reward',
            $batchId,
            $occasion,
            [
                'department_id' => $departmentId,
                'allocator_id' => $allocatorId,
                'performance_score' => $allocation['score'],
                'proportion' => $allocation['proportion'],
                'quarter' => '2023Q2'
            ]
        );
        
        $transactionIds[$allocation['user_id']] = $transaction->getId();
    }
    
    // 6. 记录批量分配记录
    $allocationRecord = $orgService->recordPointsAllocation([
        'batch_id' => $batchId,
        'department_id' => $departmentId,
        'allocator_id' => $allocatorId,
        'occasion' => $occasion,
        'total_points' => $totalPoints,
        'allocation_time' => new \DateTime(),
        'allocation_details' => $allocations,
        'transaction_ids' => $transactionIds
    ]);
    
    // 7. 提交事务
    commitTransaction();
    
    // 8. 通知员工获得积分
    foreach ($allocations as $allocation) {
        $notificationService->notify(
            $allocation['user_id'],
            '绩效积分奖励',
            '您在'.$occasion.'中获得了'.$allocation['points'].'积分奖励'
        );
    }
    
    // 9. 返回分配结果
    return [
        'success' => true,
        'batch_id' => $batchId,
        'department_id' => $departmentId,
        'total_points' => $totalPoints,
        'member_count' => count($allocations),
        'allocation_details' => $allocations,
        'message' => '积分分配完成'
    ];
} catch (\Exception $e) {
    // 10. 异常处理，回滚事务
    rollbackTransaction();
    throw $e;
}
```

# 数据一致性保障

在积分系统的业务流程中，保证数据一致性至关重要。以下是几种确保数据一致性的关键策略：

