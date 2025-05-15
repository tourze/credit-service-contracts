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

// 冻结用户积分
$freezeTransaction = $creditService->operation()->freezeCredits(
    $userAccount,
    $orderAmount,
    'order.create',
    $orderId,
    '商品兑换订单冻结',
    ['product_id' => 'PROD789']
);

// 2. 保存冻结交易ID，与订单关联
$freezeTransactionId = $freezeTransaction->getId();
saveOrderWithTransaction($orderId, $freezeTransactionId);

// 3. 用户取消订单，解冻积分
$unfreezeTransaction = $creditService->operation()->unfreezeCredits(
    $userAccount,
    $orderAmount,
    'order.cancel',
    $orderId,
    '订单取消解冻积分',
    ['freeze_transaction_id' => $freezeTransactionId]
);

// 4. 更新交易状态
$creditService->transaction()->updateTransactionStatus(
    $freezeTransactionId,
    CreditTransactionStatusEnum::CANCELLED,
    '订单已取消'
);
```
