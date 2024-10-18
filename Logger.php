<?php
namespace Tanbolt\Logger;

/**
 * Class Logger: 符合 PSR-3 规范的日志接口
 * @package Tanbolt\Logger
 */
class Logger implements LoggerInterface
{
    const DEBUG = 100;
    const INFO = 200;
    const NOTICE = 250;
    const WARNING = 300;
    const ERROR = 400;
    const CRITICAL = 500;
    const ALERT = 550;
    const EMERGENCY = 600;

    /**
     * 全局设置: 是否开启文件日志，可外部重置
     * @var bool
     */
    public static $logEnable = false;

    /**
     * 全局设置: 文件日志的缺省格式，可外部重置
     * @var ?string
     */
    public static $logFormat;

    /**
     * 全局设置: 文件日志记录的保存目录，可外部重置
     * @var ?string
     */
    public static $logDirectory;

    /**
     * 当前日志的频道
     * @var string
     */
    protected $channel = null;

    /**
     * 当前频道: 是否记录文件日志
     * @var ?bool
     */
    protected $record = null;

    /**
     * 当前频道: 文件日志格式
     * @var ?string
     */
    protected $format = null;

    /**
     * 监听函数
     * @var ?callable
     */
    protected $listener = null;

    /**
     * 日志列队
     * @var Log[]
     */
    private $logQueues = [];

    /**
     * 创建 Logger 对象
     * @param string $channel 本条日志归属频道
     * @param ?bool $record   是否写入到文件日志, 设置为 null 则使用 self::$logEnable 全局设置
     * @param ?string $format 文件日志格式
     */
    public function __construct(string $channel = 'logger', bool $record = null, string $format = null)
    {
        $this->setChannel($channel)->setRecord($record)->setFormat($format);
    }

    /**
     * 设置当前日志的 channel
     * @param string $channel
     * @return $this
     */
    public function setChannel(string $channel)
    {
        if (empty($channel)) {
            throw new LoggerException('logger channel is empty');
        }
        $this->channel = $channel;
        return $this;
    }

    /**
     * 获取当前日志的 channel
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * 设置当期频道是否记录到文件日志
     * @param ?bool $record  设置为 null 则使用 self::$logEnable 全局设置
     * @return $this
     */
    public function setRecord(?bool $record = true)
    {
        $this->record = $record;
        return $this;
    }

    /**
     * 获取当期频道是否记录到文件日志
     * @return ?bool
     */
    public function getRecord()
    {
        return null === $this->record ? static::$logEnable : $this->record;
    }

    /**
     * 设置当前日志的文件日志格式
     * @param ?string $format  设置为 null 使用全局设置
     * @return $this
     */
    public function setFormat(string $format = null)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * 设置/获取 当前日志的文件日志格式
     * @return ?string
     */
    public function getFormat()
    {
        return null === $this->format ? static::$logFormat : $this->format;
    }

    /**
     * @inheritdoc
     */
    public function setListener(callable $listener = null)
    {
        $this->listener = $listener;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function emergency($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function alert($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function critical($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function error($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function warning($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function notice($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function info($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function debug($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = [])
    {
        $log = new Log($this->channel, $level, $message, $context);
        $this->writeTxtLog($log);
        if ($this->listener) {
            call_user_func($this->listener, $log);
        }
        $this->logQueues[] = $log;
        return $this;
    }

    /**
     * 写全局文件日志
     * @param Log $log
     * @return $this
     */
    protected function writeTxtLog(Log $log)
    {
        if (!$this->getRecord()) {
            return $this;
        }
        if (empty(static::$logDirectory)) {
            throw new LoggerException('logger directory is not set');
        }
        if (!is_dir(static::$logDirectory) && false === @mkdir(static::$logDirectory, 0777, true)) {
            throw new LoggerException(sprintf('Unable to create logger directory [%s]', static::$logDirectory));
        }
        if (false === $fp = @fopen(static::$logDirectory . DIRECTORY_SEPARATOR . $log->channel, 'a')) {
            throw new LoggerException(sprintf('Unable to write in logger directory [%s]', static::$logDirectory));
        }
        @fwrite($fp, $log->line($this->getFormat()) . PHP_EOL);
        @fclose($fp);
        return $this;
    }

    /**
     * 获取日志列队
     * @return Log[]
     */
    public function queue()
    {
        return $this->logQueues;
    }

    /**
     * 最后一条日志
     * @return Log
     */
    public function last()
    {
        return end($this->logQueues);
    }

    /**
     * 清空已经设置日志列队
     * @return $this
     */
    public function __destruct()
    {
        $this->logQueues = [];
        return $this;
    }
}
