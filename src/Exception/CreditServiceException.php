<?php

namespace Tourze\CreditServiceContracts\Exception;

use Exception;

/**
 * 积分服务异常基类
 */
class CreditServiceException extends Exception
{
    /**
     * 错误代码：通用错误
     */
    public const ERROR_GENERAL = 10000;
    
    /**
     * 错误代码：账户不存在
     */
    public const ERROR_ACCOUNT_NOT_FOUND = 10001;
    
    /**
     * 错误代码：账户已禁用
     */
    public const ERROR_ACCOUNT_DISABLED = 10002;
    
    /**
     * 错误代码：余额不足
     */
    public const ERROR_INSUFFICIENT_BALANCE = 10003;
    
    /**
     * 错误代码：积分类型不存在
     */
    public const ERROR_CREDIT_TYPE_NOT_FOUND = 10004;
    
    /**
     * 错误代码：积分类型已禁用
     */
    public const ERROR_CREDIT_TYPE_DISABLED = 10005;
    
    /**
     * 错误代码：交易不存在
     */
    public const ERROR_TRANSACTION_NOT_FOUND = 10009;
    
    /**
     * 错误代码：交易状态错误
     */
    public const ERROR_TRANSACTION_STATUS = 10010;
    
    /**
     * 错误代码：冻结积分不足
     */
    public const ERROR_INSUFFICIENT_FROZEN = 10017;
    
    /**
     * 错误代码：参数错误
     */
    public const ERROR_INVALID_PARAMETER = 10018;
    
    /**
     * 错误代码：数据库错误
     */
    public const ERROR_DATABASE = 10019;
    
    /**
     * 错误代码：系统错误
     */
    public const ERROR_SYSTEM = 10020;
}
