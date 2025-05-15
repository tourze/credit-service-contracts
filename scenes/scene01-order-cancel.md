# 场景一：用户积分兑换商品后取消订单

## 业务流程

1. 用户使用积分兑换商品（下单）
2. 系统冻结用户对应金额的积分
3. 用户取消订单
4. 系统解冻被冻结的积分

## 实现方式

此场景采用"冻结-解冻"模式实现。当用户下单时，不是直接扣减积分，而是先冻结积分，待订单确认后再实际扣减。若取消订单，则解冻积分返还给用户。

## API调用流程

```php
// 1. 用户下单，冻结积分
$orderId = 'ORDER123456';
$orderAmount = 500; // 订单所需积分

try {
    // 获取账户，并使用悲观锁防止并发操作
    $userAccount = $creditService->account()->getAccountWithLock($user, $creditTypeId);
    
    // 记录操作日志
    $operationId = $creditService->transaction()->logOperation([
        'type' => 'order_freeze',
        'user_id' => $user->getId(),
        'order_id' => $orderId,
        'amount' => $orderAmount,
        'status' => 'started'
    ]);
    
    // 冻结用户积分
    $isFrozen = $creditService->account()->freezeCredits(
        $userAccount->getId(),
        $orderAmount,
        '商品兑换订单冻结',
        [
            'business_code' => 'order.create',
            'business_id' => $orderId,
            'operation_id' => $operationId,
            'product_id' => 'PROD789'
        ]
    );
    
    if (!$isFrozen) {
        throw CreditServiceException::insufficientBalance(
            $orderAmount, 
            $userAccount->getAvailableBalance()
        );
    }
    
    // 获取账户快照用于审计
    $snapshot = $creditService->account()->getAccountSnapshot($userAccount->getId());
    
    // 更新操作日志
    $creditService->transaction()->updateOperationStatus(
        $operationId,
        'completed',
        ['account_snapshot' => $snapshot]
    );
    
    // 释放账户锁
    $creditService->account()->releaseLock($userAccount->getId());
    
    // 2. 保存冻结交易记录ID，与订单关联
    saveOrderWithFreezeOperation($orderId, $operationId);
    
} catch (CreditServiceException $e) {
    if ($e->getCode() === CreditServiceException::ERROR_INSUFFICIENT_BALANCE) {
        // 处理余额不足的情况
        notifyUserInsufficientBalance($user, $orderAmount, $e->getContext()['available']);
    } else {
        // 处理其他异常
        logError('冻结积分失败', [
            'order_id' => $orderId,
            'exception' => $e->getMessage(),
            'context' => $e->getContext()
        ]);
    }
    
    // 如果已经创建了操作记录，标记为失败
    if (isset($operationId)) {
        $creditService->transaction()->updateOperationStatus(
            $operationId,
            'failed',
            ['error' => $e->getMessage()]
        );
    }
    
    // 确保释放锁
    if (isset($userAccount)) {
        $creditService->account()->releaseLock($userAccount->getId());
    }
    
    throw $e;
}

// 3. 用户取消订单，解冻积分
try {
    // 获取原始冻结操作ID
    $freezeOperationId = getOrderFreezeOperation($orderId);
    
    // 记录解冻操作
    $unfreezeOperationId = $creditService->transaction()->logOperation([
        'type' => 'order_unfreeze',
        'user_id' => $user->getId(),
        'order_id' => $orderId,
        'amount' => $orderAmount,
        'status' => 'started',
        'related_operation_id' => $freezeOperationId
    ]);
    
    // 使用乐观锁解冻积分
    $account = $creditService->account()->getAccount($user, $creditTypeId);
    $currentVersion = $account->getVersion();
    
    $isUnfrozen = $creditService->account()->unfreezeCredits(
        $account->getId(),
        $orderAmount,
        '订单取消解冻积分',
        [
            'business_code' => 'order.cancel',
            'business_id' => $orderId,
            'operation_id' => $unfreezeOperationId,
            'freeze_operation_id' => $freezeOperationId
        ]
    );
    
    if (!$isUnfrozen) {
        throw CreditServiceException::insufficientFrozen(
            $account->getId(),
            $orderAmount,
            $account->getFrozenAmount()
        );
    }
    
    // 记录审计日志
    $creditService->transaction()->logAudit(
        'credits_unfrozen',
        [
            'user_id' => $user->getId(),
            'account_id' => $account->getId(),
            'amount' => $orderAmount,
            'order_id' => $orderId,
            'operation_id' => $unfreezeOperationId
        ]
    );
    
    // 更新操作状态
    $creditService->transaction()->updateOperationStatus(
        $unfreezeOperationId,
        'completed',
        ['unfrozen_amount' => $orderAmount]
    );
    
} catch (CreditServiceException $e) {
    // 处理异常情况
    if ($e->getCode() === CreditServiceException::ERROR_INSUFFICIENT_FROZEN) {
        // 冻结金额不足，可能已经被其他操作解冻或使用
        // 记录数据不一致
        $creditService->transaction()->logInconsistency(
            'insufficient_frozen',
            [
                'order_id' => $orderId,
                'expected_amount' => $orderAmount,
                'actual_frozen' => $e->getContext()['available'],
                'account_id' => $e->getContext()['account_id']
            ]
        );
        
        // 通知管理员处理
        alertAdmin('积分冻结金额不一致', [
            'order_id' => $orderId,
            'user_id' => $user->getId(),
            'details' => $e->getContext()
        ]);
    }
    
    // 标记操作失败
    if (isset($unfreezeOperationId)) {
        $creditService->transaction()->updateOperationStatus(
            $unfreezeOperationId,
            'failed',
            ['error' => $e->getMessage()]
        );
    }
    
    throw $e;
}

// 4. 数据一致性校验
try {
    // 验证账户余额是否正确
    $transactions = $creditService->transaction()->getAllAccountTransactions($account->getId());
    $calculatedBalance = calculateBalanceFromTransactions($transactions);
    $actualBalance = $account->getBalance();
    
    if ($calculatedBalance !== $actualBalance) {
        // 账户余额与交易记录不一致，执行校正
        $creditService->account()->correctBalance(
            $account->getId(),
            $calculatedBalance,
            '订单取消后余额校正'
        );
        
        // 记录不一致情况
        $creditService->transaction()->logInconsistency(
            'balance_mismatch',
            [
                'account_id' => $account->getId(),
                'calculated_balance' => $calculatedBalance,
                'actual_balance' => $actualBalance,
                'correction_reason' => '订单取消后余额校正'
            ]
        );
    }
} catch (Exception $e) {
    // 校验失败不应影响主流程，只记录日志
    logError('余额校验失败', [
        'account_id' => $account->getId(),
        'exception' => $e->getMessage()
    ]);
}
```

## 边缘场景处理

### 1. 冻结后系统崩溃

如果积分已冻结但系统崩溃，订单状态未更新，可通过定时任务检查：

```php
// 定时任务：检查长时间处于冻结状态的积分
function checkStaleFrozenCredits() {
    $frozenOperations = $creditService->transaction()->getUserOperationLogs(
        null, // 查询所有用户
        [
            'type' => 'order_freeze',
            'status' => 'completed',
            'created_before' => new \DateTime('-24 hours') // 24小时前创建的
        ]
    );
    
    foreach ($frozenOperations['items'] as $operation) {
        // 查询对应订单状态
        $orderId = $operation['order_id'];
        $orderStatus = $orderService->getOrderStatus($orderId);
        
        // 如果订单不存在或已取消，但积分仍冻结，则自动解冻
        if ($orderStatus === null || $orderStatus === 'cancelled') {
            $accountId = $operation['account_id'];
            $amount = $operation['amount'];
            
            try {
                $creditService->account()->unfreezeCredits(
                    $accountId,
                    $amount,
                    '系统自动解冻-订单异常',
                    [
                        'business_code' => 'system.auto_unfreeze',
                        'business_id' => 'AUTO_' . $orderId,
                        'original_operation_id' => $operation['operation_id']
                    ]
                );
                
                // 记录自动修复日志
                $creditService->transaction()->logAudit(
                    'auto_recovery',
                    [
                        'account_id' => $accountId,
                        'amount' => $amount,
                        'order_id' => $orderId,
                        'reason' => '长时间冻结未使用'
                    ]
                );
            } catch (Exception $e) {
                // 自动解冻失败，发送告警
                alertAdmin('自动解冻失败', [
                    'operation' => $operation,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
```

### 2. 并发取消和确认

当订单同时收到取消和确认请求时：

```php
// 使用分布式锁确保订单操作的原子性
$lockKey = 'order_operation_' . $orderId;
$lock = $lockService->acquire($lockKey, 30); // 锁定30秒

if (!$lock) {
    throw new ConcurrentOperationException('订单正在处理中，请稍后重试');
}

try {
    // 再次检查订单状态，确保状态一致
    $currentStatus = $orderService->getOrderStatus($orderId);
    if ($currentStatus !== 'pending') {
        throw new InvalidOrderStatusException('订单状态已变更，当前状态: ' . $currentStatus);
    }
    
    // 执行取消或确认操作
    // ...
    
} finally {
    // 确保锁被释放
    $lockService->release($lockKey);
}
```
