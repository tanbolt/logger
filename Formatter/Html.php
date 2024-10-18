<?php
namespace Tanbolt\Logger\Formatter;

use Tanbolt\Logger\LogUtils;

class Html
{
    /**
     * 格式化 HTML, 适合用于可视化查看 或 发送到邮箱
     * @param array $normalized 经过归一化处理的 log
     * @param bool $toJson 返回 json 格式, 适合用于 js 渲染的 data
     * @return string
     */
    public static function format(array $normalized, bool $toJson = false)
    {
        $log = static::formatNormalized($normalized, !$toJson);
        if ($toJson) {
            return LogUtils::toJson($log);
        }
        return static::addTable(
            static::addCaption($log).
            static::addRow('Channel', $log['channel']).
            static::addRow('Time', $log['datetime']).
            static::addSubTable('Message', $log['message']).
            static::addSubTable('Context', $log['context'])
        );
    }

    /**
     * 预处理 normalized log
     * @param array $normalized
     * @param bool $html
     * @param int $depth
     * @return array
     */
    private static function formatNormalized(array $normalized, bool $html = false, int $depth = 0)
    {
        $format = [];
        foreach ($normalized as $key => $item) {
            $key = static::htmlChars((string) $key, $html);
            $log = LogUtils::stringifyScalar($item);
            if (false !== $log) {
                $format[$key] = static::htmlChars($log, $html);
            } elseif ($depth > 0) {
                $format[$key] = static::htmlChars(LogUtils::printArray($item), $html);
            } else {
                $format[$key] = static::formatNormalized($item, $html, $depth + 1);
            }
        }
        return $format;
    }

    /**
     * 转特殊字符 为 HTML 实体
     * @param string $str
     * @param bool $html
     * @return string
     */
    private static function htmlChars(string $str, bool $html = false)
    {
        return $html ? htmlspecialchars($str, ENT_NOQUOTES) : $str;
    }

    /**
     * HTML Table
     * @param string $table
     * @return string
     */
    private static function addTable(string $table)
    {
        return '<table width="100%" cellspacing="1" cellpadding="0" bgcolor="#ccc">'.$table.'</table>';
    }

    /**
     * Table 标题
     * @param array $log
     * @return string
     */
    private static function addCaption(array $log)
    {
        $color = LogUtils::getLevelColor($log['level']);
        return '<caption style="color:#fff;padding:10px 0;background:'.$color.'">' . $log['levelName'] . '</caption>';
    }

    /**
     * Table 子表格
     * @param string $name
     * @param string|array $log
     * @return string
     */
    private static function addSubTable(string $name, $log)
    {
        if (!is_array($log)) {
            return static::addRow($name, (string) $log);
        }
        $embeddedTable = '';
        foreach ($log as $key => $value) {
            $embeddedTable .= static::addRow($key, $value);
        }
        return static::addRow($name, $embeddedTable, true);
    }

    /**
     * Table 行
     * @param string $th
     * @param string $td
     * @param false $subTable
     * @return string
     */
    private static function addRow(string $th, string $td, bool $subTable = false)
    {
        $td = $subTable ? static::addTable($td) : '<pre style="padding:10px;white-space:pre-wrap;">'.$td.'</pre>';
        return '<tr>'.
            '<th bgcolor="#eaebec" width="100">'.$th.'</th>'.
            '<td bgcolor="#fafbfc">'.$td.'</td>'.
            '</tr>';
    }
}
