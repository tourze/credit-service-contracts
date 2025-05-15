# 积分系统应用场景

本目录包含了使用积分服务合约实现的各种业务场景及其处理流程。

## 场景概览

1. [用户积分兑换商品后取消订单](./scene01-order-cancel.md) - 使用"冻结-解冻"模式处理订单取消时的积分返还
2. [用户积分购买商品后分账](./scene02-point-distribution.md) - 实现积分从用户到多个商家/平台的分账流程
3. [用户A转账给用户B](./scene03-user-transfer.md) - 用户间积分转赠的实现方式
4. [积分过期导致兑换失败](./scene04-points-expiry.md) - 处理积分过期对兑换操作的影响
5. [购物返积分且设置积分有效期](./scene05-shopping-rewards.md) - 积分奖励及有效期管理
6. [多种类型积分混合支付](./scene06-mixed-payment.md) - 处理不同积分类型组合使用的场景
7. [会员等级积分加权计算](./scene07-member-level-weight.md) - 基于会员等级的积分奖励差异化策略
8. [积分兑换代金券](./scene08-coupon-exchange.md) - 积分与代金券之间的转换机制
9. [社交分享获取积分](./scene09-social-sharing.md) - 通过社交分享行为奖励积分
10. [签到打卡连续累积奖励](./scene10-check-in-rewards.md) - 连续签到的递增奖励机制
11. [积分抽奖活动](./scene11-points-lottery.md) - 使用积分参与抽奖获得奖励
12. [企业组织内积分激励与分配](./scene12-enterprise-rewards.md) - 企业内部积分激励体系

## 数据一致性

[数据一致性保障](./data-consistency.md) - 积分系统中确保数据一致性的关键策略与实现方式
