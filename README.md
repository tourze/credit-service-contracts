# Credit Service Contracts

[![Latest Version](https://img.shields.io/packagist/v/tourze/credit-service-contracts.svg?style=flat-square)](https://packagist.org/packages/tourze/credit-service-contracts)

This package provides interfaces and contracts for a comprehensive credit service system. It defines the structure and behavior for managing user credits, points, and rewards.

## Features

- Credit account management
- Credit type definitions
- Credit transaction tracking
- Credit rules and rewards
- Credit exchange capabilities
- Credit transfer between users
- Comprehensive exception handling

## Installation

```bash
composer require tourze/credit-service-contracts
```

## Quick Start

```php
<?php

use Tourze\CreditServiceContracts\Service\CreditServiceInterface;
use Tourze\CreditServiceContracts\Enum\CreditBusinessCodeEnum;

// Inject the credit service
public function __construct(
    private readonly CreditServiceInterface $creditService
) {}

// Add credits to a user account
public function addCredits(string $userId, string $creditTypeCode): void
{
    // Get the user's account for this credit type
    $account = $this->creditService->account()->getAccount($userId, $creditTypeCode);
    
    // Add credits for completing a task
    $this->creditService->operation()->increaseCredits(
        $account->getId(),
        100,
        CreditBusinessCodeEnum::TASK_COMPLETE->value,
        null,
        'Completed daily task'
    );
}
```

## Components

### Core Interfaces

- `CreditAccountInterface` - Represents a user's credit account
- `CreditTypeInterface` - Defines different types of credits/points
- `CreditTransactionInterface` - Records credit transactions
- `CreditRuleInterface` - Defines rules for earning credits
- `CreditExchangeInterface` - Manages credit exchanges for rewards
- `CreditTransferInterface` - Handles credit transfers between users

### Service Interfaces

- `CreditServiceInterface` - Main entry point to all credit services
- `CreditAccountServiceInterface` - Manages credit accounts
- `CreditTypeServiceInterface` - Manages credit types
- `CreditOperationServiceInterface` - Handles credit operations (increase/decrease)
- `CreditRuleServiceInterface` - Manages credit rules
- `CreditTransactionServiceInterface` - Manages credit transactions
- `CreditExchangeServiceInterface` - Manages credit exchanges
- `CreditTransferServiceInterface` - Manages credit transfers

### Enums

- `CreditBusinessCodeEnum` - Business codes for credit operations
- `CreditExpirationPolicyEnum` - Policies for credit expiration

### Exceptions

- `CreditServiceException` - Base exception for credit service errors

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
