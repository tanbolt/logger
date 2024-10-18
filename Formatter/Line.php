<?php
namespace Tanbolt\Logger\Formatter;

use Tanbolt\Logger\LogUtils;

class Line
{
    /**
     * 格式化日志为字符串: 适合用于写入 log 文件
     * @param array $normalized 已归一化的日志数组
     * @param ?string $format   格式，可用变量 channel/level/levelName/message/datetime/context 或 context.key
     *                          设置为 null 则使用默认格式, 另外, message 中也可以使用 "{key}" 占位符，会被替换为 context[key]
     * @param bool $keepInlineLineBreaks  是否保留换行符
     * @return string
     */
    public static function format(
        array $normalized,
        string $format = null,
        bool $keepInlineLineBreaks = false
    ) {
        if (empty($format)) {
            $format = "[%datetime%] %levelName%: %message% %context%";
        }
        $replace = [];
        if (isset($normalized['context']) && is_array($normalized['context'])) {
            // 替换 format 和 message 中的 context 占位符
            $messageReplace = [];
            $message = isset($normalized['message']) && is_string($normalized['message']) &&
                false !== strpos($format, '%message%') ? $normalized['message'] : null;
            foreach ($normalized['context'] as $key => $val) {
                $findInFormat = false !== strpos($format, $formatKey = '%context.'.$key.'%');
                $findInMessage = $message && false !== strpos($message, $messageKey = '{'.$key.'}');
                if ($findInFormat || $findInMessage) {
                    $val = static::stringify($val, $keepInlineLineBreaks);
                    unset($normalized['context'][$key]);
                }
                if ($findInFormat) {
                    $replace[$formatKey] = $val;
                }
                if ($message && $findInMessage) {
                    $messageReplace[$messageKey] = $val;
                }
            }
            if (!$normalized['context']) {
                $normalized['context'] = '';
            }
            if (!empty($messageReplace)) {
                $normalized['message'] = strtr($message, $messageReplace);
            }
        }
        foreach ($normalized as $key => $val) {
            if (false !== strpos($format, $key = '%'.$key.'%')) {
                $replace[$key] = static::stringify($val, $keepInlineLineBreaks);
            }
        }
        return strtr($format, $replace);
    }

    /**
     * 将任意变量转为 string
     * @param mixed $data
     * @param bool $keepInlineLineBreaks
     * @return string
     */
    protected static function stringify($data, bool $keepInlineLineBreaks = false)
    {
        $str = false === ($str = LogUtils::stringifyScalar($data)) ? LogUtils::toJson($data) : $str;
        return $keepInlineLineBreaks ? $str : str_replace(["\r\n", "\r", "\n"], ' ', $str);
    }
}
