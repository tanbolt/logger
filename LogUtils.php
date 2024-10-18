<?php
namespace Tanbolt\Logger;

use Throwable;
use JsonSerializable;
use DateTimeInterface;

/**
 * Class LogUtils: 处理日志的静态工具函数
 * @package Tanbolt\Logger
 */
class LogUtils
{
    const T_NULL     = 'Null';
    const T_BOOLEAN  = 'Boolean';
    const T_INT      = 'Int';
    const T_FLOAT    = 'Float';
    const T_STRING   = 'String';
    const T_ARRAY    = 'Array';
    const T_RESOURCE = 'Resource';
    const T_DATETIME   = 'Datetime';
    const T_OBJECT   = 'Object';

    /**
     * toJson 函数缺省 json_encode flags，可外部重置
     * @var int
     */
    public static $jsonFlags = null;

    /**
     * Datetime 对象转为时间字符串的格式，可外部重置
     * @var string
     */
    public static $timestamp = 'Y-m-d H:i:s';

    /**
     * 日志数据中 array 类型数据的 最大解析条数，可外部重置
     * @var int
     */
    public static $maxNormalizeItemCount = 20;

    /**
     * 日志数据中 array 类型数据的 最大解析深度，可外部重置
     * @var int
     */
    public static $maxNormalizeDepth = 10;

    /**
     * @var array
     */
    protected static $levelNames = [
        Logger::DEBUG     => 'DEBUG',
        Logger::INFO      => 'INFO',
        Logger::NOTICE    => 'NOTICE',
        Logger::WARNING   => 'WARNING',
        Logger::ERROR     => 'ERROR',
        Logger::CRITICAL  => 'CRITICAL',
        Logger::ALERT     => 'ALERT',
        Logger::EMERGENCY => 'EMERGENCY',
    ];

    /**
     * @var array
     */
    protected static $levelColors = [
        Logger::DEBUG     => '#cccccc',
        Logger::INFO      => '#468847',
        Logger::NOTICE    => '#3a87ad',
        Logger::WARNING   => '#c09853',
        Logger::ERROR     => '#f0ad4e',
        Logger::CRITICAL  => '#FF7708',
        Logger::ALERT     => '#C12A19',
        Logger::EMERGENCY => '#000000',
    ];

    /**
     * 获取所有 lever name 的映射数组
     * @return string[]
     */
    public static function levelNames()
    {
        return static::$levelNames;
    }

    /**
     * 由等级名称获取等级值
     * @param string $levelName
     * @return int
     */
    public static function getLevel(string $levelName)
    {
        $level = array_search(strtoupper($levelName), static::$levelNames);
        return false === $level ? 0 : $level;
    }

    /**
     * 由等级值获取等级名称
     * @param int $level
     * @return string
     */
    public static function getLevelName(int $level)
    {
        return static::$levelNames[$level] ?? 'LOG';
    }

    /**
     * 由等级值获取颜色
     * @param int $level
     * @return string
     */
    public static function getLevelColor(int $level)
    {
        return static::$levelColors[$level] ?? '#bebebe';
    }

    /**
     * 归一化 $log 数据
     * @param array $log
     * @param bool $withTraces
     * @return array
     */
    public static function normalizeLog(array $log, bool $withTraces = true)
    {
        return static::normalizeData($log, $withTraces);
    }

    /**
     * 归一化 $data, 最终返回结果只有 数组 和 标量(Null|Bool|String|Int|Float)
     * @param mixed $data
     * @param bool $withTraces
     * @param bool $expandObject
     * @param int $depth
     * @return array|string
     */
    protected static function normalizeData($data, bool $withTraces = true, bool $expandObject = true, int $depth = 0) {
        if ($depth > static::$maxNormalizeDepth - 1) {
            return '[*DEEP NESTED ARRAY*]';
        }
        $type = static::getVariableType($data);
        switch ($type) {
            case self::T_NULL:
            case self::T_BOOLEAN:
            case self::T_INT:
            case self::T_STRING:
                return $data;
            case self::T_FLOAT:
                // 这里对于 INF/NAN 保存为字符串, 若保存为标量会导致 json_encode 会解析错误
                return is_infinite($data) ? ($data < 0 ? '-INF' : 'INF') : (is_nan($data) ? 'NAN' : $data);
            case self::T_DATETIME:
                return 'DateTime(' . $data->format(static::$timestamp) . ')';
            case self::T_RESOURCE:
                return 'Resource(' . get_resource_type($data) . ')';
            case self::T_ARRAY:
                $count = 1;
                $normalized = [];
                foreach ($data as $key => $value) {
                    if ($count++ >= static::$maxNormalizeItemCount) {
                        $normalized['...'] = 'skipped over '.static::$maxNormalizeItemCount .'/'.count($data).' options...';
                        break;
                    }
                    $key = str_replace("\0", ' ', $key);
                    $normalized[$key] = static::normalizeData($value, $withTraces, $expandObject, $depth + 1);
                }
                return $normalized;
            case self::T_OBJECT:
                if ($data instanceof Throwable) {
                    return static::exceptionToString($data, $withTraces);
                } elseif ($data instanceof JsonSerializable) {
                    $value = $data->jsonSerialize();
                } elseif (method_exists($data, '__toString')) {
                    $value = $data->__toString();
                } else if (!$expandObject) {
                    return 'Object(' . get_class($data) . ')';
                } else {
                    // Object 转为 array 继续处理, 如果再包含 Object, 就不再展开了, 否则可能掉入递归地狱
                    $value = static::normalizeData((array) $data, $withTraces, false, $depth + 1);
                }
                return [get_class($data) => $value];
        }
        return 'Type('.$type.')';
    }

    /**
     * 将 exception 转为 string, PHP 自带的 (string) $e 转换得到的 string 已经很不错了；
     * 之所以要重新处理, 是为了增加一个 $withTraces 参数, 另外对 Previous 采取倒叙输出
     * @param Throwable $e
     * @param bool $withTraces
     * @return string
     */
    protected static function exceptionToString(Throwable $e, bool $withTraces = true)
    {
        $str = '[Throwable] '.static::exceptionBasicToString($e);
        if ($withTraces) {
            $str .= "\n[Stacktrace]\n".$e->getTraceAsString();
        }
        if ($previous = $e->getPrevious()) {
            do {
                $str .= ($withTraces ? "\n" : '')."\n[PrevException] ".static::exceptionBasicToString($previous);
                if ($withTraces) {
                    $str .= "\n[Stacktrace]\n".$previous->getTraceAsString();
                }
            } while ($previous = $previous->getPrevious());
        }
        return $str;
    }

    /**
     * 格式化 exception 主要信息为字符串
     * @param Throwable $e
     * @return string
     */
    protected static function exceptionBasicToString(Throwable $e)
    {
        return get_class($e).'(code: '.$e->getCode().'): '. $e->getMessage().' in '.$e->getFile().':'.$e->getLine();
    }

    /**
     * 获取一个变量的类型
     * @param mixed $value
     * @return string
     */
    public static function getVariableType($value)
    {
        if (null === $value) {
            return self::T_NULL;
        } elseif (is_bool($value)) {
            return self::T_BOOLEAN;
        } elseif (is_int($value)) {
            return self::T_INT;
        } elseif (is_float($value)) {
            return self::T_FLOAT;
        } elseif (is_string($value)) {
            return self::T_STRING;
        } elseif (is_array($value)) {
            return self::T_ARRAY;
        } elseif (is_resource($value)) {
            return self::T_RESOURCE;
        } elseif ($value instanceof DateTimeInterface) {
            return self::T_DATETIME;
        } else if (is_object($value)) {
            return self::T_OBJECT;
        }
        return gettype($value);
    }

    /**
     * 将任意变量转为 json 字符串, 转换失败返回失败原因
     * @param mixed $data
     * @param mixed $encodeFlags
     * @return string|false
     */
    public static function toJson($data, $encodeFlags = null)
    {
        if (null === $encodeFlags) {
            if (null === static::$jsonFlags) {
                // 关闭各种可能转换失败的开关, log 应尽可能减少出错
                $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
                    | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_PARTIAL_OUTPUT_ON_ERROR;
                if (PHP_VERSION_ID >= 70200) {
                    $flags = $flags | JSON_INVALID_UTF8_SUBSTITUTE;
                }
                static::$jsonFlags = $flags;
            }
            $encodeFlags = static::$jsonFlags;
        }
        $json = json_encode($data, $encodeFlags);
        if (false === $json) {
            $json = static::handleJsonError(json_last_error(), $data, $encodeFlags);
        }
        return $json;
    }

    /**
     * 处理 json_encode 异常, 对于因编码问题发生错误的情况, 题尝试抢救一下
     * @param int $code
     * @param mixed $data
     * @param mixed $encodeFlags
     * @return string
     */
    protected static function handleJsonError(int $code, $data, $encodeFlags = null)
    {
        if ($code !== JSON_ERROR_UTF8) {
            return static::encodeJsonError($code, $data);
        }
        if (is_string($data)) {
            static::detectAndCleanUtf8($data);
        } elseif (is_array($data)) {
            array_walk_recursive($data, [__CLASS__, 'detectAndCleanUtf8']);
        } else {
            return static::encodeJsonError($code, $data);
        }
        $json = static::toJson($data, $encodeFlags);
        return false === $json ? static::encodeJsonError(json_last_error(), $data) : $json;
    }

    /**
     * 修正无效的 utf-8 字符串
     * @param $data
     */
    protected static function detectAndCleanUtf8(&$data)
    {
        if (is_string($data) && !preg_match('//u', $data)) {
            $data = preg_replace_callback('/[\x80-\xFF]+/', function ($m) {
                return utf8_encode($m[0]);
            }, $data);
            $data = str_replace(
                ['¤', '¦', '¨', '´', '¸', '¼', '½', '¾'],
                ['€', 'Š', 'š', 'Ž', 'ž', 'Œ', 'œ', 'Ÿ'],
                $data
            );
        }
    }

    /**
     * json_encode 异常信息
     * @param int $code
     * @param mixed $data
     * @return string
     */
    protected static function encodeJsonError(int $code, $data)
    {
        return 'JSON encoding failed['.$code.']: '.json_last_error_msg().'. Encoding: '.var_export($data, true);
    }

    /**
     * 标量转为字符串, 非标量返回 false
     * @param mixed $data
     * @return string|false
     */
    public static function stringifyScalar($data)
    {
        if (null === $data) {
            return 'NULL';
        }
        if (is_bool($data)) {
            return $data ? 'true' : 'false';
        }
        if (is_scalar($data)) {
            return (string) $data;
        }
        return false;
    }

    /**
     * 转 array 为可读性 string
     * @param array $data
     * @return string
     */
    public static function printArray(array $data)
    {
        $objects = [];
        $data = print_r(static::printArrayWithObject(['data' => $data], $objects)['data'], true);
        return str_replace(array_fill(0, count($objects), "stdClass Object\n"), $objects, $data);
    }

    /**
     * 转 array 为可读性 string, 并处理 object 类型的 array
     * @param array $data
     * @param array $objects
     * @return array
     */
    private static function printArrayWithObject(array $data, array &$objects = [])
    {
        foreach ($data as &$item) {
            if (!is_array($item)) {
                continue;
            }
            if (1 === count($item) && is_string($name = key($item))) {
                $objects[] = 'Object('.$name.")\n";
                $value = current($item);
                $item = (object) (is_array($value) ? $value : ['__toString' => $value]);
            } else {
                $item = static::printArrayWithObject($item, $objects);
            }
        }
        return $data;
    }
}
