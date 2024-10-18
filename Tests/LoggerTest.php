<?php

use Tanbolt\Logger\Log;
use Tanbolt\Logger\Logger;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    protected static $levelNames = [
        'DEBUG' => 100,
        'INFO' => 200,
        'NOTICE' => 250,
        'WARNING' => 300,
        'ERROR' => 400,
        'CRITICAL' => 500,
        'ALERT' => 550,
        'EMERGENCY' => 600,
    ];

    protected function logLevelMethodTest($method, $level = null)
    {
        $timestamp = 'Y-m-d H:i:s';
        $logger = new Logger('channel');

        $methodLevel = null === $level ? $method : $level;
        if (null === $level) {
            static::assertSame($logger, $logger->$method('foo'.$method, ['foo' => 'bar']));
        } else {
            static::assertSame($logger, $logger->$method($level, 'foo'.$level, ['foo' => 'bar']));
        }

        $log = $logger->last();
        static::assertInstanceOf(Log::class, $log);

        static::assertEquals('channel', $log->channel);
        static::assertEquals(self::$levelNames[strtoupper($methodLevel)], $log->level);
        static::assertEquals(strtoupper($methodLevel), $log->levelName);
        static::assertEquals('foo'.$methodLevel, $log->message);
        static::assertInstanceOf('\DateTimeInterface', $log->datetime);
        static::assertSame(['foo' => 'bar'], $log->context);
        $record = [
            'channel' => $log->channel,
            'level' => $log->level,
            'levelName' => $log->levelName,
            'message' => $log->message,
            'datetime' => $log->datetime,
            'context' => $log->context,
        ];
        static::assertEquals($record, $log->record);

        $time = $record['datetime']->format($timestamp);
        $record['datetime'] = $time;

        static::assertEquals($record, $log->normalizer());
        static::assertEquals(serialize($record), $log->serialize());
    }

    /**
     * @dataProvider LogMethod
     * @param $level
     */
    public function testLogMethod($level)
    {
        $this->logLevelMethodTest($level);
        $this->logLevelMethodTest('log', $level);
    }

    public function LogMethod()
    {
        $methods = [];
        foreach(self::$levelNames as $level => $num) {
            $methods[] = [strtolower($level)];
        }
        return $methods;
    }

    public function testLogQueue()
    {
        $logger = new Logger();
        $logger->info('foo');
        $logger->debug('bar');
        $queues = $logger->queue();
        static::assertCount(2, $queues);
        $last = $logger->last();
        static::assertSame($last, $queues[1]);
        static::assertEquals('INFO', $queues[0]->levelName);
        static::assertEquals('foo', $queues[0]->message);

        static::assertEquals('DEBUG', $queues[1]->levelName);
        static::assertEquals('bar', $queues[1]->message);
    }

    public function testLogWriteToFile()
    {
        Logger::$logEnable = true;
        Logger::$logDirectory = __DIR__;
        $logger = new Logger('channel');
        $logger->info('foo_bar');
        $logFile = Logger::$logDirectory . '/channel';
        static::assertTrue(is_file($logFile));
        static::assertTrue(false !== strpos(file_get_contents($logFile), 'foo_bar'));

        @unlink($logFile);
        Logger::$logEnable = false;
        Logger::$logDirectory = null;
    }

    public function testLogListenMethod()
    {
        $message = null;
        $callback = function(Log $log) use (&$message) {
            $message = $log->levelName.':'.$log->message;
        };
        $logger = new Logger();
        $logger->setListener($callback);
        $logger->info('foo');
        static::assertEquals('INFO:foo', $message);

        $logger->debug('foo');
        static::assertEquals('DEBUG:foo', $message);
    }

    public function testLogChannelRecord()
    {
        $logger = new Logger('channel', null, null);
        static::assertEquals('channel', $logger->getChannel());

        static::assertFalse(Logger::$logEnable);
        static::assertNull(Logger::$logFormat);
        static::assertFalse($logger->getRecord());
        static::assertNull($logger->getFormat());

        Logger::$logEnable = true;
        Logger::$logFormat = 'format';
        static::assertTrue($logger->getRecord());
        static::assertEquals(Logger::$logFormat, $logger->getFormat());

        Logger::$logEnable = false;
        Logger::$logFormat = null;
        static::assertFalse($logger->getRecord());
        static::assertNull($logger->getFormat());

        Logger::$logDirectory = __DIR__;
        $logFile = __DIR__.'/channel';
        $message = uniqid();

        $logger = new Logger('channel', true, 'format');
        static::assertTrue($logger->getRecord());
        static::assertEquals('format', $logger->getFormat());

        static::assertSame($logger, $logger->setRecord(false));
        static::assertFalse($logger->getRecord());

        static::assertSame($logger, $logger->setFormat($format = '%levelName%: %message%'));
        static::assertEquals($format, $logger->getFormat());

        $logger->warning($message);
        static::assertFileNotExists($logFile);

        $logger->setRecord(true);
        $logger->warning($message);
        static::assertFileExists($logFile);
        static::assertTrue(false !== strpos(file_get_contents($logFile), 'WARNING: '.$message));
        @unlink($logFile);
    }
}
