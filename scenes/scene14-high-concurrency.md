# 场景十四：高并发下的积分争抢处理

## 业务场景

在秒杀、抢券等高并发场景下，大量用户同时请求使用积分，可能导致系统负载过高、数据一致性问题，甚至出现超发积分的风险。特别是在以下情况：

1. 限时抢购活动开始时，大量用户同时使用积分兑换商品
2. 热门积分兑换活动上线时，瞬时流量激增
3. 跨年、节假日等特殊时期的积分清零前，用户集中使用积分

## 实现方案

### 1. 分布式锁保护关键资源

使用分布式锁防止并发操作同一账户：

```php
/**
 * 高并发场景下的积分扣减处理
 */
function deductPointsInHighConcurrency(string $userId, string $creditTypeId, int $amount, string $businessCode, string $businessId) {
    // 构造锁的唯一键
    $lockKey = "user:{$userId}:creditType:{$creditTypeId}:lock";
    
    // 尝试获取分布式锁，设置超时时间为10秒，防止死锁
    $distributedLock = $lockService->acquire($lockKey, 10);
    
    if (!$distributedLock) {
        // 获取锁失败，说明有其他请求正在操作该账户
        throw CreditServiceException::operationLocked($userId, [
            'credit_type_id' => $creditTypeId,
            'retry_after' => 1 // 建议1秒后重试
        ]);
    }
    
    try {
        // 记录操作日志
        $operationId = $creditService->transaction()->logOperation([
            'type' => 'high_concurrency_deduction',
            'user_id' => $userId,
            'credit_type_id' => $creditTypeId,
            'amount' => $amount,
            'business_code' => $businessCode,
            'business_id' => $businessId,
            'status' => 'started'
        ]);
        
        // 检查幂等性，避免重复操作
        $existingTransaction = $creditService->transaction()->findByBusinessCodeAndId(
            $businessCode,
            $businessId
        );
        
        if ($existingTransaction) {
            // 已存在相同业务标识的交易，返回之前的结果
            $creditService->transaction()->updateOperationStatus(
                $operationId,
                'skipped',
                ['reason' => '操作已执行', 'existing_transaction_id' => $existingTransaction->getId()]
            );
            
            return [
                'success' => true,
                'idempotent' => true,
                'transaction_id' => $existingTransaction->getId()
            ];
        }
        
        // 获取用户账户
        $account = $creditService->account()->getAccount(
            new UserIdentifier($userId),
            $creditTypeId
        );
        
        // 检查余额
        if ($account->getAvailableBalance() < $amount) {
            throw CreditServiceException::insufficientBalance(
                $amount, 
                $account->getAvailableBalance()
            );
        }
        
        // 获取当前版本号用于乐观锁
        $currentVersion = $account->getVersion();
        
        // 计算新余额
        $newBalance = $account->getBalance() - $amount;
        
        // 使用乐观锁更新账户余额
        $updated = $creditService->account()->updateAccountWithVersion(
            $account->getId(),
            ['balance' => $newBalance],
            $currentVersion
        );
        
        if (!$updated) {
            // 乐观锁更新失败，说明在获取账户后余额已被其他请求修改
            throw CreditServiceException::versionConflict(
                $account->getId(),
                $currentVersion,
                $account->getVersion()
            );
        }
        
        // 创建交易记录
        $transaction = createTransactionRecord(
            $account->getId(),
            $amount,
            CreditTransactionTypeEnum::DEBIT,
            $businessCode,
            $businessId,
            $operationId
        );
        
        // 更新操作状态
        $creditService->transaction()->updateOperationStatus(
            $operationId,
            'completed',
            ['transaction_id' => $transaction->getId()]
        );
        
        // 发送异步事件通知，用于后续处理
        publishEvent('credit.deducted', [
            'user_id' => $userId,
            'account_id' => $account->getId(),
            'amount' => $amount,
            'business_code' => $businessCode,
            'business_id' => $businessId,
            'transaction_id' => $transaction->getId(),
            'operation_id' => $operationId
        ]);
        
        return [
            'success' => true,
            'transaction_id' => $transaction->getId()
        ];
    } catch (CreditServiceException $e) {
        // 记录失败状态
        if (isset($operationId)) {
            $creditService->transaction()->updateOperationStatus(
                $operationId,
                'failed',
                ['error' => $e->getMessage(), 'error_code' => $e->getCode()]
            );
        }
        
        throw $e;
    } finally {
        // 确保释放锁
        $lockService->release($lockKey);
    }
}
```

### 2. 令牌桶限流控制

使用令牌桶算法限制进入系统的请求数量：

```php
/**
 * 令牌桶限流处理
 */
function processWithRateLimit(string $userId, string $creditTypeId, callable $operation) {
    // 构造限流键
    $rateLimitKey = "rate:user:{$userId}:creditType:{$creditTypeId}";
    
    // 检查令牌桶中是否有可用令牌
    $tokenAvailable = $rateLimiter->consume($rateLimitKey, 1, [
        'rate' => 5,       // 每秒恢复5个令牌
        'burst' => 10,     // 最多允许10个并发请求
        'window' => 1      // 1秒的时间窗口
    ]);
    
    if (!$tokenAvailable) {
        // 没有可用令牌，说明请求过于频繁
        throw new RateLimitExceededException('请求过于频繁，请稍后再试', [
            'user_id' => $userId,
            'credit_type_id' => $creditTypeId,
            'retry_after' => $rateLimiter->getRetryAfter($rateLimitKey)
        ]);
    }
    
    // 有可用令牌，执行实际操作
    return $operation();
}
```

### 3. 库存预扣减与二次确认

活动库存采用预扣减策略，减少并发冲突：

```php
/**
 * 积分商品秒杀处理
 */
function processPointsFlashSale(string $userId, string $activityId, string $itemId) {
    // 1. 检查活动是否开始
    $activity = $activityService->getActivity($activityId);
    if (!$activity['is_active']) {
        throw new ActivityNotActiveException('活动未开始或已结束');
    }
    
    // 2. 尝试预占库存
    $reservationId = $inventoryService->tryReserve($activityId, $itemId);
    if (!$reservationId) {
        throw new ItemSoldOutException('商品已售罄');
    }
    
    try {
        // 3. 获取商品信息
        $item = $activityService->getItem($activityId, $itemId);
        $pointsRequired = $item['points_price'];
        
        // 4. 检查并扣减用户积分
        $result = deductPointsInHighConcurrency(
            $userId,
            $item['credit_type_id'],
            $pointsRequired,
            'flash_sale',
            $activityId . ':' . $itemId . ':' . $userId
        );
        
        // 5. 确认库存占用
        $inventoryService->confirmReservation($reservationId);
        
        // 6. 创建订单
        $orderId = $orderService->createFlashSaleOrder([
            'user_id' => $userId,
            'activity_id' => $activityId,
            'item_id' => $itemId,
            'points_paid' => $pointsRequired,
            'transaction_id' => $result['transaction_id']
        ]);
        
        return [
            'success' => true,
            'order_id' => $orderId
        ];
    } catch (Exception $e) {
        // 7. 发生异常，释放库存
        $inventoryService->cancelReservation($reservationId);
        
        // 如果已经扣减了积分，需要退回
        if (isset($result) && $result['success']) {
            refundPointsAsync($userId, $item['credit_type_id'], $pointsRequired, 'flash_sale_failed', $result['transaction_id']);
        }
        
        throw $e;
    }
}
```

### 4. 异步化处理非核心流程

将非核心业务逻辑异步处理，减轻主流程负担：

```php
/**
 * 异步退回积分
 */
function refundPointsAsync(string $userId, string $creditTypeId, int $amount, string $reason, string $originalTransactionId) {
    // 发送到消息队列，异步处理退款
    $messageQueue->send('credit.refund', [
        'user_id' => $userId,
        'credit_type_id' => $creditTypeId,
        'amount' => $amount,
        'reason' => $reason,
        'original_transaction_id' => $originalTransactionId,
        'attempt' => 1,
        'max_attempts' => 3
    ]);
}

/**
 * 消息队列处理器
 */
function processRefundMessage(array $message) {
    try {
        // 获取用户账户
        $account = $creditService->account()->getAccount(
            new UserIdentifier($message['user_id']),
            $message['credit_type_id']
        );
        
        // 记录操作
        $operationId = $creditService->transaction()->logOperation([
            'type' => 'async_refund',
            'user_id' => $message['user_id'],
            'credit_type_id' => $message['credit_type_id'],
            'amount' => $message['amount'],
            'reason' => $message['reason'],
            'original_transaction_id' => $message['original_transaction_id'],
            'status' => 'started'
        ]);
        
        // 添加积分
        $transaction = createTransactionRecord(
            $account->getId(),
            $message['amount'],
            CreditTransactionTypeEnum::CREDIT,
            'refund',
            $message['original_transaction_id'] . ':refund',
            $operationId
        );
        
        // 更新账户余额
        $creditService->account()->updateAccount(
            $account->getId(),
            ['balance' => $account->getBalance() + $message['amount']]
        );
        
        // 更新操作状态
        $creditService->transaction()->updateOperationStatus(
            $operationId,
            'completed',
            ['transaction_id' => $transaction->getId()]
        );
    } catch (Exception $e) {
        // 记录错误
        $logger->error('积分退款失败', [
            'message' => $message,
            'error' => $e->getMessage()
        ]);
        
        // 重试机制
        if ($message['attempt'] < $message['max_attempts']) {
            $message['attempt']++;
            $messageQueue->send('credit.refund', $message, 60 * $message['attempt']); // 指数退避
        } else {
            // 超过最大重试次数，发送警报
            alertAdmin('积分退款失败，需要人工处理', $message);
        }
    }
}
```

### 5. 分表分库与数据库负载均衡

对于高QPS场景，使用分表分库策略：

```php
/**
 * 根据用户ID确定分片
 */
function determineAccountShard(string $userId) {
    // 根据用户ID进行哈希，确定分片
    $hashValue = crc32($userId);
    $shardId = $hashValue % TOTAL_ACCOUNT_SHARDS;
    
    return "account_shard_{$shardId}";
}

/**
 * 使用分片表获取账户信息
 */
function getAccountFromShard(string $userId, string $creditTypeId) {
    // 确定分片
    $shardTable = determineAccountShard($userId);
    
    // 从特定分片查询数据
    $sql = "SELECT * FROM {$shardTable} WHERE user_id = :userId AND credit_type_id = :creditTypeId";
    $params = [
        ':userId' => $userId,
        ':creditTypeId' => $creditTypeId
    ];
    
    return $databaseService->executeQuery($sql, $params, $shardTable);
}
```

## 处理边缘情况

### 1. 同一账户重复请求处理

通过幂等性设计防止重复操作：

```php
// 检查请求是否已处理
$requestId = $request->headers->get('X-Request-Id');
if (!$requestId) {
    $requestId = generateUniqueId(); // 生成一个请求ID
    $response->headers->set('X-Request-Id', $requestId); // 返回给客户端留存
}

// 检查请求ID是否已存在于缓存中
$requestKey = "request:{$requestId}";
$cachedResult = $cache->get($requestKey);

if ($cachedResult) {
    // 请求已处理过，直接返回之前的结果
    return json_decode($cachedResult, true);
}

// 执行实际操作
$result = processPointsFlashSale($userId, $activityId, $itemId);

// 将结果存入缓存，设置60秒过期时间
$cache->set($requestKey, json_encode($result), 60);

return $result;
```

### 2. 超时请求的处理

对于处理超时的请求，客户端需要可靠的查询机制：

```php
/**
 * 查询操作状态
 */
function queryOperationStatus(string $operationId) {
    $operation = $creditService->transaction()->getOperationLog($operationId);
    
    if (!$operation) {
        throw new OperationNotFoundException('操作记录不存在');
    }
    
    // 根据操作状态提供不同的响应
    switch ($operation['status']) {
        case 'completed':
            // 操作已完成，返回成功结果
            return [
                'status' => 'completed',
                'data' => $operation['data']
            ];
            
        case 'failed':
            // 操作失败，返回错误信息
            return [
                'status' => 'failed',
                'error' => $operation['data']['error'] ?? '未知错误',
                'error_code' => $operation['data']['error_code'] ?? 10000
            ];
            
        case 'in_progress':
            // 操作仍在处理中
            return [
                'status' => 'in_progress',
                'started_at' => $operation['created_at'],
                'elapsed_seconds' => time() - strtotime($operation['created_at'])
            ];
            
        default:
            return [
                'status' => $operation['status'],
                'data' => $operation['data']
            ];
    }
}
```

### 3. 系统过载保护

实现熔断机制，保护系统在极端情况下的稳定性：

```php
/**
 * 带熔断器的服务调用
 */
function callWithCircuitBreaker(string $service, callable $operation) {
    $circuitKey = "circuit:{$service}";
    $circuitState = $cache->get($circuitKey);
    
    // 检查熔断器状态
    if ($circuitState === 'open') {
        // 熔断器打开，直接拒绝请求
        throw new ServiceUnavailableException("{$service}服务暂时不可用，请稍后再试");
    }
    
    if ($circuitState === 'half-open') {
        // 半开状态，只允许少量请求通过进行探测
        $allowProbing = (rand(1, 10) === 1); // 10%的概率允许通过
        if (!$allowProbing) {
            throw new ServiceUnavailableException("{$service}服务正在恢复中，请稍后再试");
        }
    }
    
    try {
        // 执行操作
        $result = $operation();
        
        // 操作成功，如果是半开状态，则关闭熔断器
        if ($circuitState === 'half-open') {
            $cache->set($circuitKey, 'closed', 300); // 关闭熔断器，5分钟有效期
        }
        
        // 重置错误计数
        $cache->delete("circuit:{$service}:errors");
        
        return $result;
    } catch (Exception $e) {
        // 增加错误计数
        $errorCount = $cache->increment("circuit:{$service}:errors", 1, 60);
        
        // 如果错误次数超过阈值，打开熔断器
        if ($errorCount >= 10) { // 1分钟内10次错误触发熔断
            $cache->set($circuitKey, 'open', 30); // 熔断器打开30秒
            
            // 30秒后自动切换到半开状态
            $taskScheduler->schedule(function() use ($circuitKey) {
                $cache->set($circuitKey, 'half-open', 120); // 半开状态持续2分钟
            }, new \DateTime('+30 seconds'));
        }
        
        throw $e;
    }
}
```

## 性能优化建议

1. **使用缓存**: 积极使用Redis等缓存热点数据，减少数据库访问
2. **读写分离**: 将读操作路由到从库，写操作集中在主库
3. **预热缓存**: 活动开始前预热相关数据到缓存
4. **批量操作**: 尽可能使用批量接口，减少网络往返
5. **监控告警**: 实时监控系统性能指标，及时发现性能瓶颈
6. **降级策略**: 准备好降级方案，在极端情况下保证核心功能可用 