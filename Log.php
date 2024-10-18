<?php
namespace Tanbolt\Logger;

use DateTimeImmutable;
use Tanbolt\Logger\Formatter\Line;
use Tanbolt\Logger\Formatter\Html;
use Tanbolt\Logger\Formatter\Javascript;

/**
 * Class Log: 单条日志
 * @package Tanbolt\Logger
 *
 * @property-read string $channel  日志频道
 * @property-read int $level 日志等级
 * @property-read string $levelName 日志等级名称
 * @property-read string $message  日志消息
 * @property-read DateTimeImmutable $datetime  日志发生时间
 * @property-read array  $context  日志上下文
 * @property-read array  $record   日志信息汇总
 */
class Log
{
    /**
     * 日志频道
     * @var string
     */
    private $channel;

    /**
     * 日志等级
     * @var int
     */
    private $level;

    /**
     * 日志等级名称
     * @var string
     */
    private $levelName;

    /**
     * 日志消息
     * @var string
     */
    private $message;

    /**
     * 日志发生时间
     * @var DateTimeImmutable
     */
    private $datetime;

    /**
     * 日志上下文
     * @var array
     */
    private $context;

    /**
     * 创建 Log 对象
     * @param string $channel
     * @param string|int $level
     * @param mixed $message
     * @param array $context
     */
    public function __construct(string $channel, $level, $message, array $context = [])
    {
        if (is_int($level)) {
            $levelName = LogUtils::getLevelName($levelInt = $level);
        } else {
            $levelInt = LogUtils::getLevel($levelName = strtoupper((string) $level));
        }
        $this->channel = $channel;
        $this->level = $levelInt;
        $this->levelName = $levelName;
        $this->message = $message;
        $this->context = $context;
        $this->datetime = new DateTimeImmutable();
    }

    /**
     * 归一化当前日志为数组: 方便后续处理
     * @param bool $withTraces 是否保留日志中的 exception stacktrace
     * @return array
     */
    public function normalizer(bool $withTraces = true)
    {
        return LogUtils::normalizeLog($this->getRecord(), $withTraces);
    }

    /**
     * 将当前日志归一化, 并序列化为字符串: 适合用于保存到数据库以便提取分析
     * @param bool $withTraces 是否保留日志中的 exception stacktrace
     * @return string
     */
    public function serialize(bool $withTraces = true)
    {
        return serialize($this->normalizer($withTraces));
    }

    /**
     * 将当前日志归一化, 并转为 json string: 适合用于保存到数据库以便提取分析
     * @param bool $withTraces 是否保留日志中的 exception stacktrace
     * @return string
     */
    public function json(bool $withTraces = true)
    {
        return LogUtils::toJson($this->normalizer($withTraces));
    }

    /**
     * 将当前日志转为单行文本: 适合存储到文本日志文件
     * @param ?string $format 格式，默认为 "[%datetime%] %levelName%: %message% %context%"
     * @param bool $withTraces 是否保留日志中的 exception stacktrace
     * @param bool $keepInlineLineBreaks 是否保留换行符
     * @return string
     */
    public function line(string $format = null, bool $withTraces = true, bool $keepInlineLineBreaks = false)
    {
        return Line::format($this->normalizer($withTraces), $format, $keepInlineLineBreaks);
    }

    /**
     * 格式化 HTML, 适合用于可视化查看 或 发送到邮箱
     * @param bool $toJson 返回 json 格式，适合用于 js 渲染的 data
     * @param bool $withTraces 是否保留日志中的 exception stacktrace
     * @return string
     */
    public function html(bool $toJson = false, bool $withTraces = true)
    {
        return Html::format($this->normalizer($withTraces), $toJson);
    }

    /**
     * 格式化 Javascript, 适合用于嵌入到页面, 输出到浏览器控制台
     * @param bool $withTag 是否返回 script 标签
     * @param bool $withTraces 是否保留日志中的 exception stacktrace
     * @return string
     */
    public function javascript(bool $withTag = true, bool $withTraces = true)
    {
        return Javascript::format($this->normalizer($withTraces), $withTag);
    }

    /**
     * 日志信息汇总
     * @param bool $formatTime
     * @return array
     */
    protected function getRecord(bool $formatTime = true)
    {
        return [
            'channel' => $this->channel,
            'level' => $this->level,
            'levelName' => $this->levelName,
            'message' => $this->message,
            'datetime' => $formatTime ? $this->datetime->format(LogUtils::$timestamp) : $this->datetime,
            'context' => $this->context,
        ];
    }

    /**
     * 获取 Log 属性值
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if ('record' === $name) {
            return $this->getRecord(false);
        }
        return $this->{$name};
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->line();
    }
}
