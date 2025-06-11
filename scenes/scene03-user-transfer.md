# 场景三：用户A转账给用户B

## 业务流程

1. 用户A发起转账
2. 系统验证用户A积分余额是否充足
3. 系统从用户A账户扣减积分
4. 系统向用户B账户增加积分

## 实现方式

此场景采用"扣减-增加"的配对交易模式，关键在于确保两个操作的原子性（要么都成功，要么都失败）。

## API调用流程

```php
// 1. 生成转账唯一ID
$transferId = generateUniqueId();
$transferAmount = 200;

// 2. 检查用户A账户余额是否充足
$hasEnoughCredits = $creditService->operation()->hasEnoughCredits(
    $userA,
    $creditTypeId,
    $transferAmount
);

if (!$hasEnoughCredits) {
    throw new \Exception('积分余额不足');
}

// 3. 开始事务，确保原子性
startTransaction();

try {
    // 4. 从用户A账户扣减积分
    $expenseTransaction = $creditService->operation()->deductCredits(
        $userAAccount,
        $transferAmount,
        'credit.transfer',
        $transferId,
        '转赠给用户' . $userB->getId(),
        ['recipient_user_id' => $userB->getId()]
    );

    // 5. 向用户B账户增加积分
    $incomeTransaction = $creditService->operation()->addCredits(
        $userBAccount,
        $transferAmount,
        'credit.received',
        $transferId,
        '来自用户' . $userA->getId() . '的转赠',
        [
            'sender_user_id' => $userA->getId(),
            'source_transaction_id' => $expenseTransaction->getId()
        ]
    );

    // 6. 记录转账关系
    saveTransferRelationship($transferId, $userA->getId(), $userB->getId(), $transferAmount, 
        $expenseTransaction->getId(), $incomeTransaction->getId());

    // 7. 提交事务
    commitTransaction();
} catch  (\Throwable $e) {
    // 8. 异常回滚
    rollbackTransaction();
    throw $e;
}
```
