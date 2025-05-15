# 场景十五：跨系统积分集成

## 业务场景

当企业拥有多个业务系统或通过合作伙伴关系需要进行积分互通时，需要处理跨系统积分集成问题。典型场景包括：

1. 企业内部不同业务系统之间的积分互通（如电商系统与会员系统）
2. 与合作伙伴建立积分联盟，实现积分跨企业互认
3. 企业并购后的积分系统整合
4. 分布式架构下多个微服务间的积分数据同步

## 实现方案

### 1. 建立统一的积分识别标准

定义跨系统积分换算规则和统一标识：

```php
/**
 * 获取系统间积分转换率
 */
function getCreditExchangeRate(string $sourceSystem, string $targetSystem, string $creditTypeId) {
    // 查询转换规则
    $exchangeRule = $creditService->account()->syncWithExternalAccount(
        null,
        $targetSystem,
        null,
        [
            'action' => 'get_exchange_rate',
            'source_system' => $sourceSystem,
            'credit_type_id' => $creditTypeId
        ]
    );
    
    if (!$exchangeRule || !isset($exchangeRule['rate'])) {
        throw new ExchangeRuleNotFoundException("找不到从{$sourceSystem}到{$targetSystem}的积分转换规则");
    }
    
    return $exchangeRule['rate'];
}

/**
 * 生成跨系统唯一积分账户标识
 */
function generateCrossSystemAccountIdentifier(string $userId, string $systemId, string $creditTypeId) {
    // 使用一致性哈希确保不同系统生成相同标识
    $identifier = hash('sha256', "{$userId}:{$systemId}:{$creditTypeId}");
    
    return "CS:{$identifier}";
}
```

### 2. 实现跨系统积分兑换

处理系统间积分兑换流程：

```php
/**
 * 跨系统积分兑换
 */
function exchangeCreditsBetweenSystems(
    string $userId, 
    string $sourceSystem, 
    string $targetSystem,
    string $sourceCreditTypeId,
    string $targetCreditTypeId,
    int $amount
) {
    // 1. 获取转换率
    $exchangeRate = getCreditExchangeRate($sourceSystem, $targetSystem, $sourceCreditTypeId);
    
    // 2. 计算目标系统积分数量
    $targetAmount = (int)($amount * $exchangeRate);
    
    // 3. 生成唯一操作ID
    $operationId = generateUniqueId();
    
    // 4. 记录操作日志
    $creditService->transaction()->logOperation([
        'type' => 'cross_system_exchange',
        'operation_id' => $operationId,
        'user_id' => $userId,
        'source_system' => $sourceSystem,
        'target_system' => $targetSystem,
        'source_credit_type' => $sourceCreditTypeId,
        'target_credit_type' => $targetCreditTypeId,
        'source_amount' => $amount,
        'target_amount' => $targetAmount,
        'exchange_rate' => $exchangeRate,
        'status' => 'started'
    ]);
    
    try {
        // 5. 第一阶段：从源系统扣减积分
        $sourceAccount = $accountService->getAccount(new UserIdentifier($userId), $sourceCreditTypeId);
        
        // 检查余额
        if ($sourceAccount->getAvailableBalance() < $amount) {
            throw CreditServiceException::insufficientBalance(
                $amount,
                $sourceAccount->getAvailableBalance()
            );
        }
        
        // 记录事务步骤
        $creditService->transaction()->updateOperationStep(
            $operationId,
            'deduct_source_credits',
            'in_progress'
        );
        
        // 冻结源系统积分
        $freezeResult = $creditService->account()->freezeCredits(
            $sourceAccount->getId(),
            $amount,
            '跨系统积分兑换-冻结',
            [
                'operation_id' => $operationId,
                'business_code' => 'cross_system.exchange',
                'business_id' => $operationId
            ]
        );
        
        if (!$freezeResult) {
            throw new CrossSystemExchangeException('源系统积分冻结失败');
        }
        
        $creditService->transaction()->updateOperationStep(
            $operationId,
            'deduct_source_credits',
            'completed',
            ['freeze_result' => $freezeResult]
        );
        
        // 6. 第二阶段：向目标系统发送积分增加请求
        $creditService->transaction()->updateOperationStep(
            $operationId,
            'add_target_credits',
            'in_progress'
        );
        
        // 调用目标系统API添加积分
        $targetSystemResponse = $targetSystemClient->addCredits([
            'user_id' => $userId,
            'credit_type_id' => $targetCreditTypeId,
            'amount' => $targetAmount,
            'source' => $sourceSystem,
            'operation_id' => $operationId,
            'business_code' => 'cross_system.exchange',
            'business_id' => $operationId,
            'metadata' => [
                'source_system' => $sourceSystem,
                'source_credit_type' => $sourceCreditTypeId,
                'source_amount' => $amount,
                'exchange_rate' => $exchangeRate
            ]
        ]);
        
        // 检查目标系统响应
        if (!$targetSystemResponse['success']) {
            // 目标系统添加失败，需要回滚
            throw new CrossSystemExchangeException(
                '目标系统积分添加失败: ' . ($targetSystemResponse['message'] ?? '未知错误')
            );
        }
        
        $creditService->transaction()->updateOperationStep(
            $operationId,
            'add_target_credits',
            'completed',
            ['target_response' => $targetSystemResponse]
        );
        
        // 7. 第三阶段：确认源系统积分扣减
        $creditService->transaction()->updateOperationStep(
            $operationId,
            'confirm_source_deduction',
            'in_progress'
        );
        
        // 使用冻结的积分
        $creditService->operation()->deductCredits(
            $sourceAccount,
            $amount,
            'cross_system.exchange.confirmed',
            $operationId,
            '跨系统积分兑换-确认',
            [
                'target_system' => $targetSystem,
                'target_credit_type' => $targetCreditTypeId,
                'target_amount' => $targetAmount,
                'exchange_rate' => $exchangeRate,
                'operation_id' => $operationId
            ]
        );
        
        $creditService->transaction()->updateOperationStep(
            $operationId,
            'confirm_source_deduction',
            'completed'
        );
        
        // 8. 完成整个操作
        $creditService->transaction()->updateOperationStatus(
            $operationId,
            'completed',
            [
                'source_amount' => $amount,
                'target_amount' => $targetAmount,
                'exchange_rate' => $exchangeRate
            ]
        );
        
        return [
            'success' => true,
            'operation_id' => $operationId,
            'source_amount' => $amount,
            'target_amount' => $targetAmount,
            'exchange_rate' => $exchangeRate
        ];
    } catch (Exception $e) {
        // 9. 异常处理和回滚
        $creditService->transaction()->updateOperationStatus(
            $operationId,
            'failed',
            ['error' => $e->getMessage()]
        );
        
        // 如果源系统已冻结积分但目标系统积分添加失败，需要解冻源系统积分
        if (isset($freezeResult) && $freezeResult && 
            (!isset($targetSystemResponse) || !$targetSystemResponse['success'])) {
            try {
                // 解冻源系统积分
                $creditService->account()->unfreezeCredits(
                    $sourceAccount->getId(),
                    $amount,
                    '跨系统积分兑换失败-解冻',
                    [
                        'operation_id' => $operationId,
                        'error' => $e->getMessage()
                    ]
                );
                
                $creditService->transaction()->updateOperationStep(
                    $operationId,
                    'rollback_source_freeze',
                    'completed'
                );
            } catch (Exception $unfreezeException) {
                // 解冻失败，记录日志并报警
                $creditService->transaction()->updateOperationStep(
                    $operationId,
                    'rollback_source_freeze',
                    'failed',
                    ['error' => $unfreezeException->getMessage()]
                );
                
                // 记录数据不一致
                $creditService->transaction()->logInconsistency(
                    'failed_unfreeze',
                    [
                        'operation_id' => $operationId,
                        'account_id' => $sourceAccount->getId(),
                        'amount' => $amount,
                        'error' => $unfreezeException->getMessage()
                    ]
                );
                
                // 发送警报
                alertAdmin('跨系统积分兑换回滚失败', [
                    'operation_id' => $operationId,
                    'account_id' => $sourceAccount->getId(),
                    'amount' => $amount,
                    'error' => $unfreezeException->getMessage()
                ]);
            }
        }
        
        // 如果目标系统已添加积分但源系统未确认扣减，需要在目标系统回滚
        if (isset($targetSystemResponse) && $targetSystemResponse['success'] && 
            !isset($deductResult)) {
            try {
                // 在目标系统回滚添加的积分
                $targetSystemClient->rollbackCredits([
                    'operation_id' => $operationId,
                    'user_id' => $userId,
                    'credit_type_id' => $targetCreditTypeId,
                    'amount' => $targetAmount,
                    'reason' => '跨系统积分兑换失败回滚'
                ]);
                
                $creditService->transaction()->updateOperationStep(
                    $operationId,
                    'rollback_target_addition',
                    'completed'
                );
            } catch (Exception $rollbackException) {
                // 目标系统回滚失败，记录并报警
                $creditService->transaction()->updateOperationStep(
                    $operationId,
                    'rollback_target_addition',
                    'failed',
                    ['error' => $rollbackException->getMessage()]
                );
                
                // 记录跨系统数据不一致
                $creditService->transaction()->logInconsistency(
                    'cross_system_mismatch',
                    [
                        'operation_id' => $operationId,
                        'source_system' => $sourceSystem,
                        'target_system' => $targetSystem,
                        'source_account_id' => $sourceAccount->getId(),
                        'target_user_id' => $userId,
                        'target_credit_type_id' => $targetCreditTypeId,
                        'target_amount' => $targetAmount,
                        'error' => $rollbackException->getMessage()
                    ]
                );
                
                // 发送警报
                alertAdmin('跨系统积分不一致', [
                    'operation_id' => $operationId,
                    'details' => '目标系统回滚失败，需要手动处理',
                    'error' => $rollbackException->getMessage()
                ]);
            }
        }
        
        throw $e;
    }
}
```

### 3. 跨系统定期对账

通过定期对账确保系统间数据一致性：

```php
/**
 * 跨系统积分对账
 */
function reconcileCrossSystemCredits(string $sourceSystem, string $targetSystem, DateTimeInterface $startTime, DateTimeInterface $endTime) {
    // 记录对账操作
    $reconciliationId = $creditService->transaction()->logOperation([
        'type' => 'cross_system_reconciliation',
        'source_system' => $sourceSystem,
        'target_system' => $targetSystem,
        'start_time' => $startTime->format('Y-m-d H:i:s'),
        'end_time' => $endTime->format('Y-m-d H:i:s'),
        'status' => 'started'
    ]);
    
    try {
        // 1. 从源系统获取跨系统操作记录
        $sourceOperations = $creditService->transaction()->getUserOperationLogs(
            null, // 所有用户
            [
                'type' => 'cross_system_exchange',
                'target_system' => $targetSystem,
                'created_after' => $startTime,
                'created_before' => $endTime,
                'status' => 'completed' // 只检查已完成的
            ]
        );
        
        // 2. 从目标系统获取相应记录
        $targetOperations = $targetSystemClient->getOperations([
            'type' => 'cross_system_exchange',
            'source_system' => $sourceSystem,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'status' => 'completed'
        ]);
        
        // 3. 比对记录，查找不一致
        $mismatches = [];
        $sourceOperationMap = [];
        
        // 建立源系统操作映射
        foreach ($sourceOperations['items'] as $operation) {
            $sourceOperationMap[$operation['operation_id']] = $operation;
        }
        
        // 检查目标系统操作是否在源系统存在，且数据一致
        foreach ($targetOperations['items'] as $targetOperation) {
            $operationId = $targetOperation['operation_id'];
            
            // 检查操作是否存在于源系统
            if (!isset($sourceOperationMap[$operationId])) {
                $mismatches[] = [
                    'type' => 'missing_in_source',
                    'operation_id' => $operationId,
                    'target_data' => $targetOperation
                ];
                continue;
            }
            
            $sourceOperation = $sourceOperationMap[$operationId];
            
            // 检查金额是否一致
            if ($sourceOperation['target_amount'] != $targetOperation['amount']) {
                $mismatches[] = [
                    'type' => 'amount_mismatch',
                    'operation_id' => $operationId,
                    'source_target_amount' => $sourceOperation['target_amount'],
                    'target_amount' => $targetOperation['amount']
                ];
            }
            
            // 标记为已检查
            unset($sourceOperationMap[$operationId]);
        }
        
        // 检查源系统中存在但目标系统不存在的操作
        foreach ($sourceOperationMap as $operationId => $operation) {
            $mismatches[] = [
                'type' => 'missing_in_target',
                'operation_id' => $operationId,
                'source_data' => $operation
            ];
        }
        
        // 4. 记录对账结果
        $creditService->transaction()->updateOperationStatus(
            $reconciliationId,
            'completed',
            [
                'source_operations_count' => count($sourceOperations['items']),
                'target_operations_count' => count($targetOperations['items']),
                'mismatches_count' => count($mismatches),
                'mismatches' => $mismatches
            ]
        );
        
        // 5. 如果存在不一致，记录并通知
        if (count($mismatches) > 0) {
            // 记录不一致
            $creditService->transaction()->logInconsistency(
                'cross_system_reconciliation',
                [
                    'reconciliation_id' => $reconciliationId,
                    'source_system' => $sourceSystem,
                    'target_system' => $targetSystem,
                    'mismatches' => $mismatches
                ]
            );
            
            // 生成对账报告
            $reportId = generateReconciliationReport($reconciliationId, $mismatches);
            
            // 发送通知
            if (count($mismatches) > 10) { // 如果不一致数量超过阈值，发送告警
                alertAdmin('跨系统对账异常', [
                    'reconciliation_id' => $reconciliationId,
                    'source_system' => $sourceSystem,
                    'target_system' => $targetSystem,
                    'mismatches_count' => count($mismatches),
                    'report_id' => $reportId
                ]);
            }
        }
        
        return [
            'success' => true,
            'reconciliation_id' => $reconciliationId,
            'source_operations_count' => count($sourceOperations['items']),
            'target_operations_count' => count($targetOperations['items']),
            'mismatches_count' => count($mismatches),
            'time_range' => [
                'start' => $startTime->format('Y-m-d H:i:s'),
                'end' => $endTime->format('Y-m-d H:i:s')
            ]
        ];
    } catch (Exception $e) {
        // 对账失败，记录状态
        $creditService->transaction()->updateOperationStatus(
            $reconciliationId,
            'failed',
            ['error' => $e->getMessage()]
        );
        
        throw $e;
    }
}
```

### 4. 系统间账户映射维护

维护多系统之间的用户账户映射关系：

```php
/**
 * 创建或更新跨系统账户映射
 */
function createOrUpdateAccountMapping(string $sourceUserId, string $sourceSystem, string $targetUserId, string $targetSystem) {
    // 检查映射是否已存在
    $existingMapping = $mappingService->findMapping([
        'source_user_id' => $sourceUserId,
        'source_system' => $sourceSystem,
        'target_system' => $targetSystem
    ]);
    
    if ($existingMapping) {
        // 更新已有映射
        return $mappingService->updateMapping(
            $existingMapping['id'],
            [
                'target_user_id' => $targetUserId,
                'updated_at' => new \DateTime()
            ]
        );
    } else {
        // 创建新映射
        return $mappingService->createMapping([
            'source_user_id' => $sourceUserId,
            'source_system' => $sourceSystem,
            'target_user_id' => $targetUserId,
            'target_system' => $targetSystem,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
            'status' => 'active'
        ]);
    }
}

/**
 * 获取跨系统用户ID
 */
function getTargetSystemUserId(string $sourceUserId, string $sourceSystem, string $targetSystem) {
    // 查询映射关系
    $mapping = $mappingService->findMapping([
        'source_user_id' => $sourceUserId,
        'source_system' => $sourceSystem,
        'target_system' => $targetSystem
    ]);
    
    if (!$mapping) {
        throw new UserMappingNotFoundException("找不到用户在目标系统的映射关系");
    }
    
    return $mapping['target_user_id'];
}
```

## 边缘情况处理

### 1. 用户在目标系统不存在

当用户只存在于一个系统时的处理：

```php
/**
 * 检查并处理目标系统用户不存在的情况
 */
function handleTargetUserNotExists(string $sourceUserId, string $sourceSystem, string $targetSystem) {
    try {
        // 尝试获取目标系统用户ID
        $targetUserId = getTargetSystemUserId($sourceUserId, $sourceSystem, $targetSystem);
        return $targetUserId;
    } catch (UserMappingNotFoundException $e) {
        // 映射不存在，尝试创建用户
        $userData = $userService->getUserInfo($sourceUserId);
        
        if (!$userData) {
            throw new UserNotFoundException("源系统用户信息不存在");
        }
        
        // 调用目标系统创建用户
        $targetUserResponse = $targetSystemClient->createUser([
            'username' => $userData['username'] . '_from_' . $sourceSystem,
            'email' => $userData['email'],
            'source_system' => $sourceSystem,
            'source_user_id' => $sourceUserId,
            'is_imported' => true
        ]);
        
        if (!$targetUserResponse['success']) {
            throw new UserCreationFailedException("在目标系统创建用户失败: " . ($targetUserResponse['message'] ?? '未知错误'));
        }
        
        // 创建用户映射
        $newTargetUserId = $targetUserResponse['user_id'];
        createOrUpdateAccountMapping($sourceUserId, $sourceSystem, $newTargetUserId, $targetSystem);
        
        return $newTargetUserId;
    }
}
```

### 2. 汇率变更的处理

积分兑换比率变更时的处理：

```php
/**
 * 处理汇率变更
 */
function handleExchangeRateChange(string $sourceSystem, string $targetSystem, string $creditTypeId, float $newRate) {
    // 记录操作
    $operationId = $creditService->transaction()->logOperation([
        'type' => 'exchange_rate_update',
        'source_system' => $sourceSystem,
        'target_system' => $targetSystem,
        'credit_type_id' => $creditTypeId,
        'old_rate' => null, // 将在下面更新
        'new_rate' => $newRate,
        'status' => 'started'
    ]);
    
    try {
        // 获取当前汇率
        $currentRate = getCreditExchangeRate($sourceSystem, $targetSystem, $creditTypeId);
        
        // 更新操作记录中的旧汇率
        $creditService->transaction()->updateOperationStep(
            $operationId,
            'get_current_rate',
            'completed',
            ['old_rate' => $currentRate]
        );
        
        // 计算变化百分比
        $changePercentage = abs(($newRate - $currentRate) / $currentRate * 100);
        
        // 如果变化太大，可能需要人工审核
        if ($changePercentage > 20) { // 超过20%的变化需要审核
            $creditService->transaction()->updateOperationStatus(
                $operationId,
                'pending_approval',
                [
                    'old_rate' => $currentRate,
                    'new_rate' => $newRate,
                    'change_percentage' => $changePercentage,
                    'message' => '汇率变化超过20%，需要人工审核'
                ]
            );
            
            // 通知管理员审核
            alertAdmin('积分汇率变化需要审核', [
                'operation_id' => $operationId,
                'source_system' => $sourceSystem,
                'target_system' => $targetSystem,
                'credit_type_id' => $creditTypeId,
                'old_rate' => $currentRate,
                'new_rate' => $newRate,
                'change_percentage' => $changePercentage
            ]);
            
            return [
                'success' => false,
                'needs_approval' => true,
                'operation_id' => $operationId,
                'message' => '汇率变化过大，已提交审核'
            ];
        }
        
        // 更新汇率
        $updateResult = $exchangeRateService->updateRate(
            $sourceSystem,
            $targetSystem,
            $creditTypeId,
            $newRate
        );
        
        if (!$updateResult) {
            throw new ExchangeRateUpdateException("更新汇率失败");
        }
        
        // 记录变更历史
        $historyId = $exchangeRateService->addRateHistory([
            'source_system' => $sourceSystem,
            'target_system' => $targetSystem,
            'credit_type_id' => $creditTypeId,
            'old_rate' => $currentRate,
            'new_rate' => $newRate,
            'changed_at' => new \DateTime(),
            'changed_by' => 'system',
            'operation_id' => $operationId
        ]);
        
        // 更新操作状态
        $creditService->transaction()->updateOperationStatus(
            $operationId,
            'completed',
            [
                'old_rate' => $currentRate,
                'new_rate' => $newRate,
                'history_id' => $historyId
            ]
        );
        
        // 发送通知
        publishEvent('exchange_rate.updated', [
            'source_system' => $sourceSystem,
            'target_system' => $targetSystem,
            'credit_type_id' => $creditTypeId,
            'old_rate' => $currentRate,
            'new_rate' => $newRate,
            'operation_id' => $operationId
        ]);
        
        return [
            'success' => true,
            'operation_id' => $operationId,
            'old_rate' => $currentRate,
            'new_rate' => $newRate
        ];
    } catch (Exception $e) {
        // 记录失败状态
        $creditService->transaction()->updateOperationStatus(
            $operationId,
            'failed',
            ['error' => $e->getMessage()]
        );
        
        throw $e;
    }
}
```

### 3. 跨系统积分操作的幂等性

确保跨系统操作的幂等性处理：

```php
/**
 * 幂等的跨系统积分添加
 */
function idempotentAddCredits(array $params) {
    // 从参数中提取关键信息
    $userId = $params['user_id'];
    $creditTypeId = $params['credit_type_id'];
    $amount = $params['amount'];
    $operationId = $params['operation_id'];
    $businessCode = $params['business_code'] ?? 'cross_system.add';
    $businessId = $params['business_id'] ?? $operationId;
    
    // 检查操作是否已执行
    $existingOperation = $creditService->transaction()->getOperationLog($operationId);
    
    if ($existingOperation && $existingOperation['status'] === 'completed') {
        // 操作已成功执行，直接返回之前的结果
        return [
            'success' => true,
            'idempotent' => true,
            'operation_id' => $operationId,
            'message' => '操作已执行',
            'result' => $existingOperation['data']
        ];
    }
    
    // 如果操作已存在但状态不是completed（可能是failed或in_progress）
    if ($existingOperation) {
        // 记录旧状态用于审计
        $oldStatus = $existingOperation['status'];
        $oldData = $existingOperation['data'];
        
        // 将状态更新为重试中
        $creditService->transaction()->updateOperationStatus(
            $operationId,
            'retrying',
            [
                'previous_status' => $oldStatus,
                'previous_data' => $oldData,
                'retry_time' => new \DateTime()
            ]
        );
    } else {
        // 不存在，创建新操作记录
        $creditService->transaction()->logOperation([
            'type' => 'cross_system_add_credits',
            'operation_id' => $operationId,
            'user_id' => $userId,
            'credit_type_id' => $creditTypeId,
            'amount' => $amount,
            'business_code' => $businessCode,
            'business_id' => $businessId,
            'metadata' => $params['metadata'] ?? [],
            'status' => 'started'
        ]);
    }
    
    try {
        // 检查交易记录是否已存在
        $existingTransaction = $creditService->transaction()->findByBusinessCodeAndId(
            $businessCode,
            $businessId
        );
        
        if ($existingTransaction) {
            // 交易已存在，更新操作状态并返回
            $creditService->transaction()->updateOperationStatus(
                $operationId,
                'completed',
                [
                    'transaction_id' => $existingTransaction->getId(),
                    'idempotent' => true
                ]
            );
            
            return [
                'success' => true,
                'idempotent' => true,
                'operation_id' => $operationId,
                'transaction_id' => $existingTransaction->getId()
            ];
        }
        
        // 执行实际的积分添加
        $account = $creditService->account()->getOrCreateAccount(
            new UserIdentifier($userId),
            $creditTypeId
        );
        
        // 创建交易并更新余额
        $transaction = createTransactionRecord(
            $account->getId(),
            $amount,
            CreditTransactionTypeEnum::CREDIT,
            $businessCode,
            $businessId,
            $operationId
        );
        
        // 更新账户余额
        $creditService->account()->updateAccount(
            $account->getId(),
            ['balance' => $account->getBalance() + $amount]
        );
        
        // 更新操作状态
        $creditService->transaction()->updateOperationStatus(
            $operationId,
            'completed',
            [
                'transaction_id' => $transaction->getId(),
                'new_balance' => $account->getBalance() + $amount
            ]
        );
        
        return [
            'success' => true,
            'operation_id' => $operationId,
            'transaction_id' => $transaction->getId(),
            'new_balance' => $account->getBalance() + $amount
        ];
    } catch (Exception $e) {
        // 更新操作状态
        $creditService->transaction()->updateOperationStatus(
            $operationId,
            'failed',
            ['error' => $e->getMessage()]
        );
        
        throw $e;
    }
}
```

## 安全考虑

1. **防伪造请求**: 实施严格的加密签名和验证机制，确保跨系统请求的真实性
2. **敏感数据加密**: 确保传输中和存储的敏感用户数据被适当加密
3. **访问控制**: 实施细粒度的权限控制，限制各系统能执行的操作
4. **审计日志**: 记录所有跨系统操作，便于事后审计和问题排查
5. **超时处理**: 对跨系统请求设置合理的超时时间，避免长时间阻塞

## 性能优化

1. **批量处理**: 对于大量用户的积分操作，使用批量接口减少网络往返
2. **异步处理**: 将非关键步骤如对账、日志等异步处理，提高响应速度
3. **预热数据**: 定期同步热点数据到本地缓存，减少跨系统调用
4. **限流保护**: 对跨系统接口实施限流，防止单一系统过载影响整体服务 