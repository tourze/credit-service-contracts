<?php

namespace Tourze\CreditServiceContracts\Tests;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Tourze\CreditServiceContracts\CreditTypeInterface;

class CreditTypeInterfaceTest extends TestCase
{
    private CreditTypeInterface $creditType;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 创建一个模拟的积分类型实现
        $this->creditType = $this->createMock(CreditTypeInterface::class);
        
        // 设置通用预期行为
        $this->creditType->method('getId')->willReturn('type-123');
        $this->creditType->method('getName')->willReturn('积分');
        $this->creditType->method('getCode')->willReturn('POINTS');
        $this->creditType->method('getUnitName')->willReturn('分');
        $this->creditType->method('getExpirationPolicy')->willReturn('fixed_days');
        $this->creditType->method('getValidityPeriod')->willReturn(365);
        $this->creditType->method('getDescription')->willReturn('通用积分');
        $this->creditType->method('getIconUrl')->willReturn('https://example.com/icon.png');
        $this->creditType->method('isValid')->willReturn(true);
        $this->creditType->method('getCreateTime')->willReturn(new DateTimeImmutable('2023-01-01'));
        $this->creditType->method('getUpdateTime')->willReturn(new DateTimeImmutable('2023-01-02'));
        $this->creditType->method('getAttributes')->willReturn(['exchange_ratio' => 100]);
    }

    /**
     * 测试getId方法返回正确的积分类型ID
     */
    public function testGetId_returnsCorrectTypeId()
    {
        $this->assertSame('type-123', $this->creditType->getId());
    }

    /**
     * 测试getName方法返回正确的积分类型名称
     */
    public function testGetName_returnsCorrectTypeName()
    {
        $this->assertSame('积分', $this->creditType->getName());
    }

    /**
     * 测试getCode方法返回正确的积分类型代码
     */
    public function testGetCode_returnsCorrectTypeCode()
    {
        $this->assertSame('POINTS', $this->creditType->getCode());
    }

    /**
     * 测试getUnitName方法返回正确的积分单位名称
     */
    public function testGetUnitName_returnsCorrectUnitName()
    {
        $this->assertSame('分', $this->creditType->getUnitName());
    }

    /**
     * 测试getExpirationPolicy方法返回正确的过期策略
     */
    public function testGetExpirationPolicy_returnsCorrectPolicy()
    {
        $this->assertSame('fixed_days', $this->creditType->getExpirationPolicy());
    }

    /**
     * 测试getValidityPeriod方法返回正确的有效期天数
     */
    public function testGetValidityPeriod_returnsCorrectPeriod()
    {
        $this->assertSame(365, $this->creditType->getValidityPeriod());
    }

    /**
     * 测试getDescription方法返回正确的积分类型描述
     */
    public function testGetDescription_returnsCorrectDescription()
    {
        $this->assertSame('通用积分', $this->creditType->getDescription());
    }

    /**
     * 测试getIconUrl方法返回正确的图标URL
     */
    public function testGetIconUrl_returnsCorrectIconUrl()
    {
        $this->assertSame('https://example.com/icon.png', $this->creditType->getIconUrl());
    }

    /**
     * 测试isValid方法返回正确的状态
     */
    public function testIsValid_returnsCorrectStatus()
    {
        $this->assertTrue($this->creditType->isValid());
    }

    /**
     * 测试getCreateTime方法返回正确的创建时间
     */
    public function testGetCreateTime_returnsCorrectCreateTime()
    {
        $createTime = $this->creditType->getCreateTime();
        $this->assertInstanceOf(DateTimeInterface::class, $createTime);
        $this->assertEquals('2023-01-01', $createTime->format('Y-m-d'));
    }

    /**
     * 测试getUpdateTime方法返回正确的更新时间
     */
    public function testGetUpdateTime_returnsCorrectUpdateTime()
    {
        $updateTime = $this->creditType->getUpdateTime();
        $this->assertInstanceOf(DateTimeInterface::class, $updateTime);
        $this->assertEquals('2023-01-02', $updateTime->format('Y-m-d'));
    }

    /**
     * 测试getAttributes方法返回正确的附加属性
     */
    public function testGetAttributes_returnsCorrectAttributes()
    {
        $expectedAttributes = ['exchange_ratio' => 100];
        $this->assertSame($expectedAttributes, $this->creditType->getAttributes());
    }

    /**
     * 测试永不过期的情况
     */
    public function testNeverExpiringType_hasNullValidityPeriod()
    {
        $neverExpiringType = $this->createMock(CreditTypeInterface::class);
        $neverExpiringType->method('getExpirationPolicy')->willReturn('never_expire');
        $neverExpiringType->method('getValidityPeriod')->willReturn(null);
        
        $this->assertSame('never_expire', $neverExpiringType->getExpirationPolicy());
        $this->assertNull($neverExpiringType->getValidityPeriod());
    }

    /**
     * 测试可选字段为null的情况
     */
    public function testOptionalFields_canBeNull()
    {
        $typeWithNullFields = $this->createMock(CreditTypeInterface::class);
        $typeWithNullFields->method('getDescription')->willReturn(null);
        $typeWithNullFields->method('getIconUrl')->willReturn(null);
        $typeWithNullFields->method('getExpirationPolicy')->willReturn(null);
        $typeWithNullFields->method('getValidityPeriod')->willReturn(null);
        
        $this->assertNull($typeWithNullFields->getDescription());
        $this->assertNull($typeWithNullFields->getIconUrl());
        $this->assertNull($typeWithNullFields->getExpirationPolicy());
        $this->assertNull($typeWithNullFields->getValidityPeriod());
    }
} 