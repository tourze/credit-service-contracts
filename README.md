# Credit Service Contracts

[English](README.md) | [中文](README.zh-CN.md)

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

use Tourze\CreditServiceContracts\Service\CreditAccountServiceInterface;
use Tourze\CreditServiceContracts\Service\CreditOperationServiceInterface;
use Symfony\Component\Security\Core\User\UserInterface;

// Inject the credit services
public function __construct(
    private readonly CreditAccountServiceInterface $accountService,
    private readonly CreditOperationServiceInterface $operationService
) {}

// Add credits to a user account
public function addCredits(UserInterface $user, string $creditTypeId): void
{
    // Get the user's account for this credit type
    $account = $this->accountService->getOrCreateAccount($user, $creditTypeId);
    
    // Add credits for completing a task
    $this->operationService->increaseCredits(
        $account->getId(),
        100,
        'task_complete',
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

### Service Interfaces

- `CreditAccountServiceInterface` - Manages credit accounts
- `CreditTypeServiceInterface` - Manages credit types
- `CreditOperationServiceInterface` - Handles credit operations (increase/decrease)
- `CreditTransactionServiceInterface` - Manages credit transactions

### Enums

- `CreditTransactionTypeEnum` - Types of credit transactions
- `CreditTransactionStatusEnum` - Status of credit transactions
- `CreditExpirationPolicyEnum` - Policies for credit expiration

### Exceptions

- `CreditServiceException` - Base exception for credit service errors

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
