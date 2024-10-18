<?php
namespace Tanbolt\Logger\Formatter;

use Tanbolt\Logger\LogUtils;

class Javascript
{
    /**
     * 格式化 Javascript, 适合用于嵌入到页面, 输出到浏览器控制台
     * @param array $normalized
     * @param bool $withTag
     * @return string
     */
    public static function format(array $normalized, bool $withTag = true)
    {
        $json = Html::format($normalized, true);
        $color = LogUtils::getLevelColor($normalized['level']);
        $js = static::jsFunction()."($json, '$color');";
        return $withTag ? '<script>'.$js.'</script>' : $js;
    }

    /**
     * @return string
     */
    private static function jsFunction()
    {
        return <<<'JS'
(function(log, color) {
    try {
        var title = '%c Tanbolt Logger: [' + log.datetime + '] '+ log.channel + ' - ' + log.levelName + ' ',
            style = 'color:#fff;font-weight:bold;background:' + color,
            stringMessage = typeof log.message === 'string',
            contextEmpty = true;
        for(var prop in log.context) {
            if(log.context.hasOwnProperty(prop)) {
                contextEmpty = false;
                break;
            }
        }
        if (contextEmpty && stringMessage && log.message.indexOf("\\n") < 0) {
            return console.log(title, style, ': ' + log.message);
        }
        var consoleObject = function (obj, caption) {
            console.group('%c---------------'+caption+'---------------', 'color:#999')
            for (var prop in obj) {
                if(obj.hasOwnProperty(prop)) {
                    console.groupCollapsed(prop)
                    console.log('%c'+obj[prop], 'color:#666')
                    console.groupEnd()
                }
            }
            console.groupEnd()
        }
        console.groupCollapsed(title, style);
        if (stringMessage) {
            console.log(log.message)
        } else {
            consoleObject(log.message, 'MESSAGE');
        }
        if (!contextEmpty) {
            consoleObject(log.context, 'CONTEXT');
        }
        console.groupEnd();
    } catch (e) {
      console.log(log)
    }
})
JS;
    }
}
