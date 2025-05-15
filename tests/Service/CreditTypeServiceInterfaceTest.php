<?php

namespace Tourze\CreditServiceContracts\Tests\Service;

use PHPUnit\Framework\TestCase;
use Tourze\CreditServiceContracts\CreditTypeInterface;
use Tourze\CreditServiceContracts\Service\CreditTypeServiceInterface;

class CreditTypeServiceInterfaceTest extends TestCase
{
    private CreditTypeServiceInterface $creditTypeService;
    private CreditTypeInterface $mockCreditType;
    private CreditTypeInterface $mockCreditType2;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 创建模拟的积分类型对象
        $this->mockCreditType = $this->createMock(CreditTypeInterface::class);
        $this->mockCreditType->method('getId')->willReturn('type-1');
        $this->mockCreditType->method('getCode')->willReturn('POINTS');
        $this->mockCreditType->method('getName')->willReturn('通用积分');
        $this->mockCreditType->method('isValid')->willReturn(true);
        
        $this->mockCreditType2 = $this->createMock(CreditTypeInterface::class);
        $this->mockCreditType2->method('getId')->willReturn('type-2');
        $this->mockCreditType2->method('getCode')->willReturn('COINS');
        $this->mockCreditType2->method('getName')->willReturn('金币');
        $this->mockCreditType2->method('isValid')->willReturn(true);
        
        // 创建模拟的积分类型服务
        $this->creditTypeService = $this->createMock(CreditTypeServiceInterface::class);
    }

    /**
     * 测试根据ID获取积分类型
     */
    public function testGetCreditTypeById_returnsCorrectType()
    {
        $this->creditTypeService
            ->method('getCreditTypeById')
            ->willReturnCallback(function ($typeId) {
                return match ($typeId) {
                    'type-1' => $this->mockCreditType,
                    'type-2' => $this->mockCreditType2,
                    default => null,
                };
            });
        
        // 测试存在的积分类型
        $creditType = $this->creditTypeService->getCreditTypeById('type-1');
        $this->assertSame($this->mockCreditType, $creditType);
        $this->assertSame('type-1', $creditType->getId());
        $this->assertSame('POINTS', $creditType->getCode());
        
        // 测试不同ID返回不同积分类型
        $creditType2 = $this->creditTypeService->getCreditTypeById('type-2');
        $this->assertSame($this->mockCreditType2, $creditType2);
        $this->assertSame('type-2', $creditType2->getId());
        $this->assertSame('COINS', $creditType2->getCode());
        
        // 测试不存在的积分类型返回null
        $this->assertNull($this->creditTypeService->getCreditTypeById('non-existent'));
    }

    /**
     * 测试根据代码获取积分类型
     */
    public function testGetCreditTypeByCode_returnsCorrectType()
    {
        $this->creditTypeService
            ->method('getCreditTypeByCode')
            ->willReturnCallback(function ($code) {
                return match ($code) {
                    'POINTS' => $this->mockCreditType,
                    'COINS' => $this->mockCreditType2,
                    default => null,
                };
            });
        
        // 测试存在的积分类型
        $creditType = $this->creditTypeService->getCreditTypeByCode('POINTS');
        $this->assertSame($this->mockCreditType, $creditType);
        $this->assertSame('POINTS', $creditType->getCode());
        $this->assertSame('type-1', $creditType->getId());
        
        // 测试不同代码返回不同积分类型
        $creditType2 = $this->creditTypeService->getCreditTypeByCode('COINS');
        $this->assertSame($this->mockCreditType2, $creditType2);
        $this->assertSame('COINS', $creditType2->getCode());
        $this->assertSame('type-2', $creditType2->getId());
        
        // 测试不存在的积分类型代码返回null
        $this->assertNull($this->creditTypeService->getCreditTypeByCode('NON_EXISTENT'));
    }

    /**
     * 测试获取所有积分类型
     */
    public function testGetAllCreditTypes_returnsAllTypes()
    {
        $invalidCreditType = $this->createMock(CreditTypeInterface::class);
        $invalidCreditType->method('getId')->willReturn('type-3');
        $invalidCreditType->method('getCode')->willReturn('STARS');
        $invalidCreditType->method('isValid')->willReturn(false);
        
        $allTypes = [$this->mockCreditType, $this->mockCreditType2, $invalidCreditType];
        $validTypes = [$this->mockCreditType, $this->mockCreditType2];
        
        $this->creditTypeService
            ->method('getAllCreditTypes')
            ->willReturnCallback(function ($onlyValid) use ($allTypes, $validTypes) {
                return $onlyValid ? $validTypes : $allTypes;
            });
        
        // 测试默认只返回有效的积分类型
        $creditTypes = $this->creditTypeService->getAllCreditTypes();
        $this->assertCount(2, $creditTypes);
        $this->assertContains($this->mockCreditType, $creditTypes);
        $this->assertContains($this->mockCreditType2, $creditTypes);
        $this->assertNotContains($invalidCreditType, $creditTypes);
        
        // 测试返回所有积分类型（包括无效的）
        $allCreditTypes = $this->creditTypeService->getAllCreditTypes(false);
        $this->assertCount(3, $allCreditTypes);
        $this->assertContains($this->mockCreditType, $allCreditTypes);
        $this->assertContains($this->mockCreditType2, $allCreditTypes);
        $this->assertContains($invalidCreditType, $allCreditTypes);
    }

    /**
     * 测试空结果的情况
     */
    public function testEmptyResults()
    {
        $emptyService = $this->createMock(CreditTypeServiceInterface::class);
        $emptyService->method('getAllCreditTypes')->willReturn([]);
        $emptyService->method('getCreditTypeById')->willReturn(null);
        $emptyService->method('getCreditTypeByCode')->willReturn(null);
        
        // 测试没有积分类型时返回空数组
        $this->assertEmpty($emptyService->getAllCreditTypes());
        
        // 测试ID和代码查找时找不到返回null
        $this->assertNull($emptyService->getCreditTypeById('any-id'));
        $this->assertNull($emptyService->getCreditTypeByCode('any-code'));
    }

    /**
     * 测试大小写不敏感的代码查询
     */
    public function testCaseInsensitiveCodeLookup()
    {
        $caseInsensitiveService = $this->createMock(CreditTypeServiceInterface::class);
        $caseInsensitiveService
            ->method('getCreditTypeByCode')
            ->willReturnCallback(function ($code) {
                return match (strtoupper($code)) {
                    'POINTS' => $this->mockCreditType,
                    'COINS' => $this->mockCreditType2,
                    default => null,
                };
            });
        
        // 测试不同大小写的代码都能找到对应的积分类型
        $this->assertSame($this->mockCreditType, $caseInsensitiveService->getCreditTypeByCode('POINTS'));
        $this->assertSame($this->mockCreditType, $caseInsensitiveService->getCreditTypeByCode('points'));
        $this->assertSame($this->mockCreditType, $caseInsensitiveService->getCreditTypeByCode('Points'));
        
        $this->assertSame($this->mockCreditType2, $caseInsensitiveService->getCreditTypeByCode('COINS'));
        $this->assertSame($this->mockCreditType2, $caseInsensitiveService->getCreditTypeByCode('coins'));
        $this->assertSame($this->mockCreditType2, $caseInsensitiveService->getCreditTypeByCode('Coins'));
    }
} 