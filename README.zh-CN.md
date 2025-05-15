# 积分服务合约

[![最新版本](https://img.shields.io/packagist/v/tourze/credit-service-contracts.svg?style=flat-square)](https://packagist.org/packages/tourze/credit-service-contracts)

本包提供了全面的积分服务系统的接口和合约。它定义了管理用户积分、点数和奖励的结构和行为。

## 功能特性

- 积分账户管理
- 积分类型定义
- 积分交易跟踪
- 积分规则和奖励
- 积分兑换功能
- 用户间积分转赠
- 全面的异常处理

## 安装

```bash
composer require tourze/credit-service-contracts
```

## 快速开始

```php
<?php

use Tourze\CreditServiceContracts\Service\CreditServiceInterface;
use Tourze\CreditServiceContracts\Enum\CreditBusinessCodeEnum;

// 注入积分服务
public function __construct(
    private readonly CreditServiceInterface $creditService
) {}

// 为用户账户添加积分
public function addCredits(string $userId, string $creditTypeCode): void
{
    // 获取用户此积分类型的账户
    $account = $this->creditService->account()->getAccount($userId, $creditTypeCode);
    
    // 为完成任务添加积分
    $this->creditService->operation()->increaseCredits(
        $account->getId(),
        100,
        CreditBusinessCodeEnum::TASK_COMPLETE->value,
        null,
        '完成每日任务'
    );
}
```

## 组件

### 核心接口

- `CreditAccountInterface` - 表示用户的积分账户
- `CreditTypeInterface` - 定义不同类型的积分/点数
- `CreditTransactionInterface` - 记录积分交易
- `CreditRuleInterface` - 定义赚取积分的规则
- `CreditExchangeInterface` - 管理积分兑换奖励
- `CreditTransferInterface` - 处理用户间的积分转赠

### 服务接口

- `CreditServiceInterface` - 所有积分服务的主入口点
- `CreditAccountServiceInterface` - 管理积分账户
- `CreditTypeServiceInterface` - 管理积分类型
- `CreditOperationServiceInterface` - 处理积分操作（增加/减少）
- `CreditRuleServiceInterface` - 管理积分规则
- `CreditTransactionServiceInterface` - 管理积分交易
- `CreditExchangeServiceInterface` - 管理积分兑换
- `CreditTransferServiceInterface` - 管理积分转赠

### 枚举

- `CreditBusinessCodeEnum` - 积分操作的业务代码
- `CreditExpirationPolicyEnum` - 积分过期策略

### 异常

- `CreditServiceException` - 积分服务错误的基础异常

## 贡献

请查看 [CONTRIBUTING.md](CONTRIBUTING.md) 了解详情。

## 许可证

MIT 许可证 (MIT)。请查看 [License File](LICENSE) 了解更多信息。 