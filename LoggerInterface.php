<?php
namespace Tanbolt\Logger;

/**
 * Interface LoggerInterface: 根据 PSR-3 标准
 * @package Tanbolt\Logger
 */
interface LoggerInterface
{
    /**
     * 设置日志监听回调
     * @param ?callable $listener
     * @return static
     */
    public function setListener(callable $listener = null);

    /**
     * 最严重错误: 系统不可用
     * @param mixed $message
     * @param array $context
     * @return static
     */
    public function emergency($message, array $context = []);

    /**
     * 崩溃错误: 必须立刻采取行动; 如：在整个网站都垮掉了、数据库不可用了或者其他的情况下，应该发送一条警报短信通知管理员
     * @param mixed $message
     * @param array $context
     * @return static
     */
    public function alert($message, array $context = []);

    /**
     * 临界错误: 紧急情况; 如：系统在崩溃编译，程序组件不可用或者出现非预期的异常
     * @param mixed $message
     * @param array $context
     * @return static
     */
    public function critical($message, array $context = []);

    /**
     * 运行错误: 运行时出现的错误，不需要立刻采取行动，但必须记录下来以备检测
     * @param mixed $message
     * @param array $context
     * @return static
     */
    public function error($message, array $context = []);

    /**
     * 非错误性异常; 如：使用了被弃用的 API, 错误地使用了 API 或者非预想的不必要错误
     * @param mixed $message
     * @param array $context
     * @return static
     */
    public function warning($message, array $context = []);

    /**
     * 重要事件通知, 非错误异常
     * @param mixed $message
     * @param array $context
     * @return static
     */
    public function notice($message, array $context = []);

    /**
     * 值得记录的信息; 如：用户登录和SQL记录
     * @param mixed $message
     * @param array $context
     * @return static
     */
    public function info($message, array $context = []);

    /**
     * 调试信息
     * @param mixed $message
     * @param array $context
     * @return static
     */
    public function debug($message, array $context = []);

    /**
     * 任意级别记录日志
     * @param mixed $level
     * @param mixed $message
     * @param array $context
     * @return static
     */
    public function log($level, $message, array $context = []);
}
