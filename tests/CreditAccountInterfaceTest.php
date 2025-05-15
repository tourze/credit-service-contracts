<?php

namespace Tourze\CreditServiceContracts\Tests;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\CreditServiceContracts\CreditAccountInterface;
use Tourze\UserIDBundle\Contracts\AccountInterface;

class CreditAccountInterfaceTest extends TestCase
{
    private CreditAccountInterface $creditAccount;
    private UserInterface $mockUser;
    private AccountInterface $mockAccount;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 创建模拟的用户和账户对象
        $this->mockUser = $this->createMock(UserInterface::class);
        $this->mockAccount = $this->createMock(AccountInterface::class);
        
        // 创建一个模拟的积分账户实现
        $this->creditAccount = $this->createMock(CreditAccountInterface::class);
        
        // 设置通用预期行为
        $this->creditAccount->method('getId')->willReturn('account-123');
        $this->creditAccount->method('getUser')->willReturn($this->mockUser);
        $this->creditAccount->method('getAccount')->willReturn($this->mockAccount);
        $this->creditAccount->method('getCreditTypeId')->willReturn('type-123');
        $this->creditAccount->method('getBalance')->willReturn(1000);
        $this->creditAccount->method('getTotalIncome')->willReturn(2000);
        $this->creditAccount->method('getTotalExpense')->willReturn(1000);
        $this->creditAccount->method('getFrozenAmount')->willReturn(200);
        $this->creditAccount->method('getAvailableBalance')->willReturn(800);
        $this->creditAccount->method('getCreateTime')->willReturn(new DateTimeImmutable('2023-01-01'));
        $this->creditAccount->method('getUpdateTime')->willReturn(new DateTimeImmutable('2023-01-02'));
        $this->creditAccount->method('isValid')->willReturn(true);
        $this->creditAccount->method('getLevel')->willReturn(2);
        $this->creditAccount->method('getRemark')->willReturn('VIP会员');
    }

    /**
     * 测试getId方法返回正确的账户ID
     */
    public function testGetId_returnsCorrectAccountId()
    {
        $this->assertSame('account-123', $this->creditAccount->getId());
    }

    /**
     * 测试getUser方法返回正确的用户对象
     */
    public function testGetUser_returnsCorrectUserObject()
    {
        $this->assertSame($this->mockUser, $this->creditAccount->getUser());
    }

    /**
     * 测试getAccount方法返回正确的账户对象
     */
    public function testGetAccount_returnsCorrectAccountObject()
    {
        $this->assertSame($this->mockAccount, $this->creditAccount->getAccount());
    }

    /**
     * 测试getCreditTypeId方法返回正确的积分类型ID
     */
    public function testGetCreditTypeId_returnsCorrectTypeId()
    {
        $this->assertSame('type-123', $this->creditAccount->getCreditTypeId());
    }

    /**
     * 测试getBalance方法返回正确的余额
     */
    public function testGetBalance_returnsCorrectBalance()
    {
        $this->assertSame(1000, $this->creditAccount->getBalance());
    }

    /**
     * 测试getTotalIncome方法返回正确的总收入
     */
    public function testGetTotalIncome_returnsCorrectTotalIncome()
    {
        $this->assertSame(2000, $this->creditAccount->getTotalIncome());
    }

    /**
     * 测试getTotalExpense方法返回正确的总支出
     */
    public function testGetTotalExpense_returnsCorrectTotalExpense()
    {
        $this->assertSame(1000, $this->creditAccount->getTotalExpense());
    }

    /**
     * 测试getFrozenAmount方法返回正确的冻结金额
     */
    public function testGetFrozenAmount_returnsCorrectFrozenAmount()
    {
        $this->assertSame(200, $this->creditAccount->getFrozenAmount());
    }

    /**
     * 测试getAvailableBalance方法返回正确的可用余额
     */
    public function testGetAvailableBalance_returnsCorrectAvailableBalance()
    {
        $this->assertSame(800, $this->creditAccount->getAvailableBalance());
        
        // 验证可用余额是否等于总余额减去冻结金额
        $this->assertEquals(
            $this->creditAccount->getBalance() - $this->creditAccount->getFrozenAmount(),
            $this->creditAccount->getAvailableBalance()
        );
    }

    /**
     * 测试getCreateTime方法返回正确的创建时间
     */
    public function testGetCreateTime_returnsCorrectCreateTime()
    {
        $createTime = $this->creditAccount->getCreateTime();
        $this->assertInstanceOf(DateTimeInterface::class, $createTime);
        $this->assertEquals('2023-01-01', $createTime->format('Y-m-d'));
    }

    /**
     * 测试getUpdateTime方法返回正确的更新时间
     */
    public function testGetUpdateTime_returnsCorrectUpdateTime()
    {
        $updateTime = $this->creditAccount->getUpdateTime();
        $this->assertInstanceOf(DateTimeInterface::class, $updateTime);
        $this->assertEquals('2023-01-02', $updateTime->format('Y-m-d'));
    }

    /**
     * 测试isValid方法返回正确的账户状态
     */
    public function testIsValid_returnsCorrectStatus()
    {
        $this->assertTrue($this->creditAccount->isValid());
    }

    /**
     * 测试getLevel方法返回正确的账户等级
     */
    public function testGetLevel_returnsCorrectLevel()
    {
        $this->assertSame(2, $this->creditAccount->getLevel());
    }

    /**
     * 测试getRemark方法返回正确的备注
     */
    public function testGetRemark_returnsCorrectRemark()
    {
        $this->assertSame('VIP会员', $this->creditAccount->getRemark());
    }

    /**
     * 测试备注为null的情况
     */
    public function testRemark_canBeNull()
    {
        $accountWithNullRemark = $this->createMock(CreditAccountInterface::class);
        $accountWithNullRemark->method('getRemark')->willReturn(null);
        
        $this->assertNull($accountWithNullRemark->getRemark());
    }

    /**
     * 测试零余额账户
     */
    public function testZeroBalanceAccount_hasCorrectValues()
    {
        $zeroBalanceAccount = $this->createMock(CreditAccountInterface::class);
        $zeroBalanceAccount->method('getBalance')->willReturn(0);
        $zeroBalanceAccount->method('getTotalIncome')->willReturn(1000);
        $zeroBalanceAccount->method('getTotalExpense')->willReturn(1000);
        $zeroBalanceAccount->method('getFrozenAmount')->willReturn(0);
        $zeroBalanceAccount->method('getAvailableBalance')->willReturn(0);
        
        $this->assertSame(0, $zeroBalanceAccount->getBalance());
        $this->assertSame(0, $zeroBalanceAccount->getAvailableBalance());
        $this->assertSame(0, $zeroBalanceAccount->getFrozenAmount());
        $this->assertEquals(
            $zeroBalanceAccount->getTotalIncome() - $zeroBalanceAccount->getTotalExpense(),
            $zeroBalanceAccount->getBalance()
        );
    }

    /**
     * 测试全部余额被冻结的账户
     */
    public function testFullyFrozenAccount_hasZeroAvailableBalance()
    {
        $fullyFrozenAccount = $this->createMock(CreditAccountInterface::class);
        $fullyFrozenAccount->method('getBalance')->willReturn(1000);
        $fullyFrozenAccount->method('getFrozenAmount')->willReturn(1000);
        $fullyFrozenAccount->method('getAvailableBalance')->willReturn(0);
        
        $this->assertSame(0, $fullyFrozenAccount->getAvailableBalance());
        $this->assertEquals(
            $fullyFrozenAccount->getBalance() - $fullyFrozenAccount->getFrozenAmount(),
            $fullyFrozenAccount->getAvailableBalance()
        );
    }
} 