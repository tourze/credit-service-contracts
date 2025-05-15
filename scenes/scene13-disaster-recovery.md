# 场景十三：积分系统灾难恢复

## 业务场景

在极端情况下（如数据库损坏、系统崩溃等），积分系统可能需要进行灾难恢复。这种情况下，需要确保用户积分数据的完整性和一致性，防止数据丢失或错误。

## 关键挑战

1. 账户余额与交易记录不一致
2. 部分交易记录丢失或损坏
3. 账户状态异常（如永久锁定）
4. 跨系统数据不一致（如订单系统与积分系统）

## 解决方案

### 1. 账户余额重建

基于交易记录重新计算账户余额，确保余额的准确性。

```php
/**
 * 重建账户余额
 * 通过分析所有交易记录，重新计算账户余额
 */
function rebuildAccountBalance(string $accountId) {
    // 记录操作日志
    $operationId = $creditService->transaction()->logOperation([
        'type' => 'account_rebuild',
        'account_id' => $accountId,
        'status' => 'started',
        'initiated_by' => 'system.disaster_recovery'
    ]);
    
    try {
        // 获取账户信息
        $account = $creditService->account()->getAccountById(
            new AccountIdentifier($accountId)
        );
        
        // 获取所有交易记录
        $transactions = $creditService->transaction()->getAllAccountTransactions($accountId);
        
        // 重新计算余额
        $calculatedBalance = 0;
        $calculatedFrozen = 0;
        
        foreach ($transactions as $transaction) {
            $type = $transaction->getType();
            $status = $transaction->getStatus();
            $amount = $transaction->getAmount();
            
            // 只计算已完成的交易
            if ($status === CreditTransactionStatusEnum::COMPLETED) {
                if ($type === CreditTransactionTypeEnum::CREDIT) {
                    $calculatedBalance += $amount;
                } elseif ($type === CreditTransactionTypeEnum::DEBIT) {
                    $calculatedBalance -= $amount;
                }
            }
            
            // 计算冻结金额
            if ($status === CreditTransactionStatusEnum::FROZEN) {
                $calculatedFrozen += $amount;
            }
        }
        
        // 记录计算结果
        $creditService->transaction()->updateOperationStep(
            $operationId,
            'calculation',
            'completed',
            [
                'calculated_balance' => $calculatedBalance,
                'calculated_frozen' => $calculatedFrozen,
                'current_balance' => $account->getBalance(),
                'current_frozen' => $account->getFrozenAmount()
            ]
        );
        
        // 校正账户余额
        $isBalanceCorrected = $creditService->account()->correctBalance(
            $accountId,
            $calculatedBalance,
            '灾难恢复余额校正'
        );
        
        // 校正冻结金额（需要直接更新账户数据）
        $isDataUpdated = $creditService->account()->updateAccount(
            $accountId,
            ['frozen_amount' => $calculatedFrozen]
        );
        
        // 记录审计日志
        $creditService->transaction()->logAudit(
            'disaster_recovery',
            [
                'account_id' => $accountId,
                'action' => 'balance_rebuild',
                'previous_balance' => $account->getBalance(),
                'corrected_balance' => $calculatedBalance,
                'previous_frozen' => $account->getFrozenAmount(),
                'corrected_frozen' => $calculatedFrozen
            ]
        );
        
        // 更新操作状态
        $creditService->transaction()->updateOperationStatus(
            $operationId,
            'completed',
            [
                'balance_corrected' => $isBalanceCorrected,
                'data_updated' => $isDataUpdated
            ]
        );
        
        return [
            'success' => true,
            'account_id' => $accountId,
            'previous_balance' => $account->getBalance(),
            'corrected_balance' => $calculatedBalance,
            'previous_frozen' => $account->getFrozenAmount(),
            'corrected_frozen' => $calculatedFrozen
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

### 2. 交易记录恢复与验证

验证交易记录的完整性，并尝试恢复丢失的数据。

```php
/**
 * 验证交易记录完整性
 * 检查交易记录链是否完整，尝试修复不一致
 */
function verifyTransactionIntegrity(string $accountId) {
    // 记录操作
    $operationId = $creditService->transaction()->logOperation([
        'type' => 'transaction_verify',
        'account_id' => $accountId,
        'status' => 'started'
    ]);
    
    try {
        // 获取所有交易记录并按时间排序
        $transactions = $creditService->transaction()->getAllAccountTransactions($accountId);
        usort($transactions, function($a, $b) {
            return $a->getCreatedAt() <=> $b->getCreatedAt();
        });
        
        // 检查交易链完整性
        $issues = [];
        $runningBalance = 0;
        
        foreach ($transactions as $index => $transaction) {
            $type = $transaction->getType();
            $status = $transaction->getStatus();
            $amount = $transaction->getAmount();
            
            // 只考虑已完成的交易
            if ($status === CreditTransactionStatusEnum::COMPLETED) {
                // 更新运行余额
                if ($type === CreditTransactionTypeEnum::CREDIT) {
                    $runningBalance += $amount;
                } elseif ($type === CreditTransactionTypeEnum::DEBIT) {
                    $runningBalance -= $amount;
                }
                
                // 检查是否出现负余额（理论上不应该发生）
                if ($runningBalance < 0) {
                    $issues[] = [
                        'type' => 'negative_balance',
                        'transaction_id' => $transaction->getId(),
                        'transaction_type' => $type->value,
                        'amount' => $amount,
                        'running_balance' => $runningBalance,
                        'position' => $index
                    ];
                }
                
                // 检查关联交易是否存在（如冻结-解冻对）
                if ($transaction->getRelatedTransactionId() !== null) {
                    $relatedExists = false;
                    foreach ($transactions as $t) {
                        if ($t->getId() === $transaction->getRelatedTransactionId()) {
                            $relatedExists = true;
                            break;
                        }
                    }
                    
                    if (!$relatedExists) {
                        $issues[] = [
                            'type' => 'missing_related_transaction',
                            'transaction_id' => $transaction->getId(),
                            'related_transaction_id' => $transaction->getRelatedTransactionId()
                        ];
                    }
                }
            }
            
            // 检查交易状态异常
            if ($status === CreditTransactionStatusEnum::PENDING && 
                $transaction->getCreatedAt() < new \DateTime('-24 hours')) {
                $issues[] = [
                    'type' => 'stale_pending_transaction',
                    'transaction_id' => $transaction->getId(),
                    'created_at' => $transaction->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }
        }
        
        // 记录验证结果
        $creditService->transaction()->updateOperationStatus(
            $operationId,
            'completed',
            [
                'issues_count' => count($issues),
                'issues' => $issues,
                'final_balance' => $runningBalance
            ]
        );
        
        // 处理发现的问题
        if (count($issues) > 0) {
            // 记录不一致
            $creditService->transaction()->logInconsistency(
                'transaction_integrity',
                [
                    'account_id' => $accountId,
                    'issues' => $issues
                ]
            );
            
            // 自动处理一些问题
            foreach ($issues as $issue) {
                if ($issue['type'] === 'stale_pending_transaction') {
                    // 自动完成或取消长时间处于pending状态的交易
                    processStalePendingTransaction($issue['transaction_id']);
                }
            }
        }
        
        return [
            'success' => true,
            'account_id' => $accountId,
            'issues_count' => count($issues),
            'issues' => $issues,
            'calculated_balance' => $runningBalance
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

### 3. 跨系统数据对账

与其他系统（如订单系统）进行数据对账，确保数据一致性。

```php
/**
 * 与订单系统对账
 * 确保积分订单与订单系统数据一致
 */
function reconcileWithOrderSystem(array $options = []) {
    // 记录操作
    $operationId = $creditService->transaction()->logOperation([
        'type' => 'system_reconciliation',
        'target_system' => 'order_system',
        'status' => 'started',
        'options' => $options
    ]);
    
    try {
        // 获取时间范围
        $startTime = $options['start_time'] ?? new \DateTime('-24 hours');
        $endTime = $options['end_time'] ?? new \DateTime();
        
        // 从订单系统获取积分相关订单
        $orderSystemOrders = $orderService->getPointsRelatedOrders($startTime, $endTime);
        
        // 从积分系统获取订单相关交易
        $creditTransactions = $creditService->transaction()->getUserTransactionsByTimeRange(
            null, // 所有用户
            $startTime,
            $endTime,
            ['business_code' => 'order.payment']
        );
        
        // 对账处理
        $mismatches = [];
        $orderMap = [];
        
        // 建立订单映射
        foreach ($creditTransactions['items'] as $transaction) {
            $metadata = $transaction->getMetadata();
            if (isset($metadata['order_id'])) {
                $orderId = $metadata['order_id'];
                if (!isset($orderMap[$orderId])) {
                    $orderMap[$orderId] = [];
                }
                $orderMap[$orderId][] = $transaction;
            }
        }
        
        // 对比订单系统数据
        foreach ($orderSystemOrders as $order) {
            $orderId = $order['order_id'];
            $orderPoints = $order['points_amount'];
            
            // 检查积分系统是否有对应交易
            if (!isset($orderMap[$orderId])) {
                $mismatches[] = [
                    'type' => 'missing_credit_transaction',
                    'order_id' => $orderId,
                    'order_points' => $orderPoints,
                    'order_status' => $order['status']
                ];
                continue;
            }
            
            // 检查金额是否一致
            $creditTotal = 0;
            foreach ($orderMap[$orderId] as $transaction) {
                if ($transaction->getStatus() === CreditTransactionStatusEnum::COMPLETED) {
                    $creditTotal += $transaction->getAmount();
                }
            }
            
            if ($creditTotal != $orderPoints) {
                $mismatches[] = [
                    'type' => 'amount_mismatch',
                    'order_id' => $orderId,
                    'order_points' => $orderPoints,
                    'credit_points' => $creditTotal,
                    'transactions' => array_map(function($t) {
                        return $t->getId();
                    }, $orderMap[$orderId])
                ];
            }
            
            // 检查订单状态与积分交易状态是否一致
            $orderStatus = $order['status'];
            $creditStatus = $orderMap[$orderId][0]->getStatus()->value;
            
            if (($orderStatus === 'completed' && $creditStatus !== 'completed') ||
                ($orderStatus === 'cancelled' && $creditStatus !== 'cancelled')) {
                $mismatches[] = [
                    'type' => 'status_mismatch',
                    'order_id' => $orderId,
                    'order_status' => $orderStatus,
                    'credit_status' => $creditStatus
                ];
            }
        }
        
        // 更新操作状态
        $creditService->transaction()->updateOperationStatus(
            $operationId,
            'completed',
            [
                'total_orders' => count($orderSystemOrders),
                'total_credit_transactions' => count($creditTransactions['items']),
                'mismatches_count' => count($mismatches),
                'mismatches' => $mismatches
            ]
        );
        
        // 如果存在不匹配，记录不一致并生成报告
        if (count($mismatches) > 0) {
            $creditService->transaction()->logInconsistency(
                'cross_system_mismatch',
                [
                    'system' => 'order_system',
                    'mismatches' => $mismatches,
                    'reconciliation_time' => new \DateTime()
                ]
            );
            
            // 生成对账报告
            generateReconciliationReport($operationId, $mismatches);
        }
        
        return [
            'success' => true,
            'total_orders' => count($orderSystemOrders),
            'total_transactions' => count($creditTransactions['items']),
            'mismatches_count' => count($mismatches)
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

### 4. 账户状态检查与恢复

检查并修复处于异常状态的账户。

```php
/**
 * 检查并修复异常账户状态
 */
function checkAndFixAccountStatus() {
    // 查找异常状态的账户
    $problematicAccounts = $creditService->account()->batchGetAccounts([
        'filters' => [
            'status' => 'locked',
            'locked_before' => new \DateTime('-1 day') // 锁定超过1天
        ]
    ]);
    
    $fixed = 0;
    $failed = 0;
    $results = [];
    
    foreach ($problematicAccounts as $account) {
        $accountId = $account->getId();
        
        // 记录操作
        $operationId = $creditService->transaction()->logOperation([
            'type' => 'account_status_fix',
            'account_id' => $accountId,
            'status' => 'started',
            'current_status' => $account->getStatus()
        ]);
        
        try {
            // 检查是否有未完成的交易
            $pendingTransactions = $creditService->transaction()->getAccountTransactions(
                new AccountIdentifier($accountId),
                ['status' => CreditTransactionStatusEnum::PENDING]
            );
            
            // 如果没有待处理交易，可以解锁账户
            if (count($pendingTransactions['items']) === 0) {
                // 解锁账户
                $creditService->account()->setAccountStatus(
                    $accountId,
                    true, // 激活
                    '系统自动恢复-无待处理交易'
                );
                
                // 记录审计日志
                $creditService->transaction()->logAudit(
                    'account_unlocked',
                    [
                        'account_id' => $accountId,
                        'reason' => '系统自动恢复-无待处理交易',
                        'locked_duration' => $account->getLockedAt()->diff(new \DateTime())->format('%d天%h小时%i分钟')
                    ]
                );
                
                $creditService->transaction()->updateOperationStatus(
                    $operationId,
                    'completed',
                    ['action' => 'unlocked']
                );
                
                $fixed++;
                $results[] = [
                    'account_id' => $accountId,
                    'action' => 'unlocked',
                    'success' => true
                ];
            } else {
                // 有待处理交易，需要检查交易状态
                $canUnlock = true;
                $pendingTooLong = [];
                
                foreach ($pendingTransactions['items'] as $transaction) {
                    $createdAt = $transaction->getCreatedAt();
                    $now = new \DateTime();
                    $diff = $now->getTimestamp() - $createdAt->getTimestamp();
                    
                    // 如果交易已经处于pending状态超过1小时，可能是有问题的交易
                    if ($diff > 3600) {
                        $pendingTooLong[] = $transaction->getId();
                    }
                }
                
                // 如果有长时间处于pending的交易，需要人工介入
                if (count($pendingTooLong) > 0) {
                    $creditService->transaction()->updateOperationStatus(
                        $operationId,
                        'manual_intervention_required',
                        [
                            'pending_transactions' => $pendingTooLong,
                            'message' => '账户有长时间未处理的交易，需要人工检查'
                        ]
                    );
                    
                    // 发送告警
                    alertAdmin('账户锁定状态异常', [
                        'account_id' => $accountId,
                        'locked_duration' => $account->getLockedAt()->diff(new \DateTime())->format('%d天%h小时%i分钟'),
                        'pending_transactions' => $pendingTooLong
                    ]);
                    
                    $failed++;
                    $results[] = [
                        'account_id' => $accountId,
                        'action' => 'manual_intervention_required',
                        'success' => false,
                        'pending_transactions' => $pendingTooLong
                    ];
                }
            }
        } catch (Exception $e) {
            $creditService->transaction()->updateOperationStatus(
                $operationId,
                'failed',
                ['error' => $e->getMessage()]
            );
            
            $failed++;
            $results[] = [
                'account_id' => $accountId,
                'action' => 'failed',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    return [
        'total_accounts_checked' => count($problematicAccounts),
        'fixed' => $fixed,
        'failed' => $failed,
        'results' => $results
    ];
}
```

## 灾难恢复执行流程

当检测到系统异常或需要进行灾难恢复时，应按照以下流程执行：

1. 首先进行系统状态检查，评估损失程度
2. 按账户分批处理，避免一次性处理过多数据
3. 对每个账户执行交易记录完整性验证
4. 重建账户余额，确保与交易记录一致
5. 修复账户锁定状态
6. 进行跨系统数据对账
7. 生成灾难恢复报告

```php
/**
 * 执行灾难恢复
 */
function executeDisasterRecovery() {
    // 记录操作
    $recoveryId = $creditService->transaction()->logOperation([
        'type' => 'disaster_recovery',
        'status' => 'started',
        'initiated_by' => 'admin'
    ]);
    
    try {
        // 1. 系统状态检查
        $systemStatus = checkSystemStatus();
        $creditService->transaction()->updateOperationStep(
            $recoveryId,
            'system_check',
            'completed',
            $systemStatus
        );
        
        // 如果系统状态严重异常，可能需要人工介入
        if ($systemStatus['severity'] === 'critical') {
            $creditService->transaction()->updateOperationStatus(
                $recoveryId,
                'manual_intervention_required',
                [
                    'message' => '系统状态严重异常，需要人工介入',
                    'status' => $systemStatus
                ]
            );
            
            alertAdmin('灾难恢复需要人工介入', [
                'recovery_id' => $recoveryId,
                'system_status' => $systemStatus
            ]);
            
            return [
                'success' => false,
                'message' => '系统状态严重异常，需要人工介入',
                'recovery_id' => $recoveryId
            ];
        }
        
        // 2. 分批处理账户
        $allAccounts = getAllAccountIds();
        $batchSize = 100;
        $totalAccounts = count($allAccounts);
        $batches = ceil($totalAccounts / $batchSize);
        
        $processingResults = [
            'total_accounts' => $totalAccounts,
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'batch_results' => []
        ];
        
        for ($i = 0; $i < $batches; $i++) {
            $batchAccounts = array_slice($allAccounts, $i * $batchSize, $batchSize);
            $batchResult = processBatch($batchAccounts, $recoveryId);
            
            $processingResults['processed'] += count($batchAccounts);
            $processingResults['success'] += $batchResult['success'];
            $processingResults['failed'] += $batchResult['failed'];
            $processingResults['batch_results'][] = $batchResult;
            
            // 更新恢复进度
            $creditService->transaction()->updateOperationStep(
                $recoveryId,
                'account_processing',
                'in_progress',
                [
                    'batch' => $i + 1,
                    'total_batches' => $batches,
                    'progress' => round(($i + 1) / $batches * 100, 2) . '%',
                    'batch_result' => $batchResult
                ]
            );
        }
        
        // 3. 跨系统对账
        $reconciliationResult = reconcileWithOrderSystem();
        $creditService->transaction()->updateOperationStep(
            $recoveryId,
            'system_reconciliation',
            'completed',
            $reconciliationResult
        );
        
        // 4. 生成恢复报告
        $reportId = generateRecoveryReport($recoveryId, $processingResults, $reconciliationResult);
        
        // 完成恢复流程
        $creditService->transaction()->updateOperationStatus(
            $recoveryId,
            'completed',
            [
                'report_id' => $reportId,
                'success_rate' => round($processingResults['success'] / $totalAccounts * 100, 2) . '%',
                'reconciliation_mismatches' => $reconciliationResult['mismatches_count']
            ]
        );
        
        return [
            'success' => true,
            'recovery_id' => $recoveryId,
            'report_id' => $reportId,
            'accounts_processed' => $processingResults['processed'],
            'success_rate' => round($processingResults['success'] / $totalAccounts * 100, 2) . '%'
        ];
    } catch (Exception $e) {
        // 更新操作状态
        $creditService->transaction()->updateOperationStatus(
            $recoveryId,
            'failed',
            ['error' => $e->getMessage()]
        );
        
        // 发送告警
        alertAdmin('灾难恢复失败', [
            'recovery_id' => $recoveryId,
            'error' => $e->getMessage()
        ]);
        
        throw $e;
    }
}
```

## 风险与注意事项

1. **数据一致性风险**：灾难恢复过程中，应确保操作的原子性和事务一致性，避免造成新的数据问题。

2. **性能影响**：恢复过程可能会消耗大量系统资源，应在系统负载较低时进行，或分批处理以减少影响。

3. **业务中断风险**：考虑在恢复过程中是否需要暂停部分业务操作，确保数据不会在恢复过程中被修改。

4. **审计跟踪**：所有恢复操作都应详细记录，便于后续审计和问题分析。

5. **备份策略**：在执行恢复前，应确保有当前数据的完整备份，以防恢复过程失败导致进一步的数据损失。 