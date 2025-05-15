# 数据一致性保障

在积分系统的业务流程中，保证数据一致性至关重要。以下是几种确保数据一致性的关键策略：

## 1. 数据库事务（Transaction）

在所有涉及多步操作的场景中，应使用数据库事务来确保操作的原子性。

```php
// 开始事务
startTransaction();

try {
    // 执行积分扣减
    $creditService->operation()->deductCredits(...);
    
    // 执行商品发货或服务开通
    $orderService->fulfillOrder(...);
    
    // 提交事务
    commitTransaction();
} catch (\Exception $e) {
    // 异常回滚
    rollbackTransaction();
    throw $e;
}
```

## 2. 分布式事务处理

对于跨多个微服务的操作，可以采用以下方案：

### 2.1 两阶段提交（2PC）

```php
// 准备阶段
$prepareResults = [];
$prepareResults[] = $creditService->prepareDeductCredits($userId, $amount);
$prepareResults[] = $couponService->prepareCreateCoupon($userId, $couponData);

// 检查所有准备操作是否成功
$allPrepared = !in_array(false, $prepareResults);

if ($allPrepared) {
    // 提交阶段
    $creditService->commitDeductCredits($prepareResults[0]['prepare_id']);
    $couponService->commitCreateCoupon($prepareResults[1]['prepare_id']);
} else {
    // 回滚阶段
    foreach ($prepareResults as $result) {
        if (isset($result['prepare_id'])) {
            $service->rollbackOperation($result['prepare_id']);
        }
    }
}
```

### 2.2 最终一致性（BASE模型）

对于部分场景，可采用最终一致性模型：

```php
// 1. 先完成关键操作
$transactionId = $creditService->operation()->deductCredits($userAccount, $amount, ...);

// 2. 记录后续待完成的操作
$messageQueue->send('credit_exchange', [
    'transaction_id' => $transactionId,
    'user_id' => $userId,
    'coupon_data' => $couponData,
    'retry_count' => 0,
    'max_retries' => 3
]);

// 3. 异步消费者处理后续操作
function processExchange($message) {
    try {
        $couponService->createCoupon($message['user_id'], $message['coupon_data']);
        $creditService->markTransactionCompleted($message['transaction_id']);
    } catch (\Exception $e) {
        if ($message['retry_count'] < $message['max_retries']) {
            // 增加重试次数并重新入队
            $message['retry_count']++;
            $messageQueue->send('credit_exchange', $message, 60); // 延迟60秒重试
        } else {
            // 超过重试次数，记录失败并通知人工处理
            $alertService->alert('积分兑换失败', $message, $e->getMessage());
        }
    }
}
```

## 3. 乐观锁与悲观锁

### 3.1 乐观锁

适用于并发冲突较少的场景：

```php
// 查询当前账户数据，包含版本号
$account = $creditService->account()->getAccount($user, $creditTypeId);
$currentVersion = $account->getVersion();

// 计算新余额
$newBalance = $account->getBalance() - $amount;

// 使用版本号进行更新，防止并发更新冲突
$updated = $creditService->account()->updateAccountWithVersion(
    $account->getId(),
    [
        'balance' => $newBalance,
        'updated_at' => new \DateTime()
    ],
    $currentVersion
);

if (!$updated) {
    // 更新失败，说明数据已被其他进程修改，需要重试或报错
    throw new \ConcurrentModificationException('账户数据已被修改，请重试');
}
```

### 3.2 悲观锁

适用于高并发写入场景：

```php
// 加锁查询账户
$account = $creditService->account()->getAccountWithLock($user, $creditTypeId);

// 在锁定状态下更新账户
$creditService->account()->updateAccount(
    $account->getId(),
    [
        'balance' => $account->getBalance() - $amount,
        'updated_at' => new \DateTime()
    ]
);

// 操作完成后释放锁
$creditService->account()->releaseLock($account->getId());
```

## 4. 幂等性设计

确保相同操作多次执行的结果一致：

```php
// 检查操作是否已执行
$existingTransaction = $creditService->transaction()->findByBusinessCodeAndId(
    'order.payment',
    $orderId
);

if ($existingTransaction) {
    // 操作已执行，直接返回之前的结果
    return [
        'success' => true,
        'transaction_id' => $existingTransaction->getId(),
        'idempotent' => true,
        'message' => '操作已执行'
    ];
}

// 未执行过，执行实际操作
$transaction = $creditService->operation()->deductCredits(...);
```

## 5. 补偿事务

对于无法回滚的操作，使用补偿事务：

```php
// 1. 记录操作步骤
$operationId = generateUniqueId();
$operationLog = $creditService->log()->logOperation([
    'operation_id' => $operationId,
    'type' => 'exchange_coupon',
    'status' => 'started',
    'steps' => [
        ['id' => 'deduct_credit', 'status' => 'pending'],
        ['id' => 'create_coupon', 'status' => 'pending']
    ]
]);

try {
    // 2. 扣减积分
    $transaction = $creditService->operation()->deductCredits(...);
    $creditService->log()->updateOperationStep($operationId, 'deduct_credit', 'completed', [
        'transaction_id' => $transaction->getId()
    ]);
    
    try {
        // 3. 创建优惠券
        $coupon = $couponService->createCoupon(...);
        $creditService->log()->updateOperationStep($operationId, 'create_coupon', 'completed', [
            'coupon_id' => $coupon->getId()
        ]);
        
        // 4. 标记整个操作完成
        $creditService->log()->updateOperationStatus($operationId, 'completed');
    } catch (\Exception $e) {
        // 5. 创建优惠券失败，执行补偿操作
        $compensationTransaction = $creditService->operation()->addCredits(
            $userAccount,
            $amount,
            'compensation',
            $operationId,
            '创建优惠券失败的补偿还原',
            [
                'original_transaction_id' => $transaction->getId(),
                'failure_reason' => $e->getMessage()
            ]
        );
        
        $creditService->log()->updateOperationStatus($operationId, 'compensated', [
            'compensation_transaction_id' => $compensationTransaction->getId()
        ]);
        
        throw $e;
    }
} catch (\Exception $e) {
    $creditService->log()->updateOperationStatus($operationId, 'failed', [
        'error' => $e->getMessage()
    ]);
    throw $e;
}
```

## 6. 审计日志与追踪

记录所有关键操作，用于验证和问题排查：

```php
$auditLog = [
    'service' => 'credit_service',
    'operation' => 'deduct_credits',
    'user_id' => $user->getId(),
    'amount' => $amount,
    'business_code' => $businessCode,
    'business_id' => $businessId,
    'ip' => $request->getClientIp(),
    'request_id' => $requestId,
    'timestamp' => microtime(true),
    'before_state' => [
        'balance' => $account->getBalance(),
        'frozen_amount' => $account->getFrozenAmount()
    ]
];

// 执行操作
$result = $creditService->operation()->deductCredits(...);

// 记录操作结果
$auditLog['after_state'] = [
    'balance' => $account->getBalance(),
    'frozen_amount' => $account->getFrozenAmount()
];
$auditLog['transaction_id'] = $result->getId();
$auditLog['success'] = true;

$logService->logAudit('credit_operation', $auditLog);
```

## 7. 定时校验与自动修复

定期执行数据一致性检查，发现并修复不一致问题：

```php
// 定期任务：检查账户余额与交易历史的一致性
function validateAccountBalance($accountId) {
    $account = $creditService->account()->getAccount($accountId);
    
    // 计算所有交易的净值
    $transactions = $creditService->transaction()->getAccountTransactions($accountId);
    $calculatedBalance = calculateNetBalance($transactions);
    
    if ($account->getBalance() != $calculatedBalance) {
        // 记录不一致
        $logService->logInconsistency('account_balance', [
            'account_id' => $accountId,
            'recorded_balance' => $account->getBalance(),
            'calculated_balance' => $calculatedBalance,
            'difference' => $account->getBalance() - $calculatedBalance
        ]);
        
        // 自动修复或通知人工介入
        if (abs($account->getBalance() - $calculatedBalance) < 100) {
            // 差异较小，自动修复
            $creditService->account()->correctBalance($accountId, $calculatedBalance, 'system_correction');
        } else {
            // 差异较大，通知人工处理
            $alertService->alertAccountInconsistency($accountId, $account->getBalance(), $calculatedBalance);
        }
    }
}
```
