<?php
namespace think\helper;
class Time
{
    public static function today()
    {
        list($y, $m, $d) = explode('-', date('Y-m-d'));
        return [
            mktime(0, 0, 0, $m, $d, $y),
            mktime(23, 59, 59, $m, $d, $y)
        ];
    }
    public static function yesterday()
    {
        $yesterday = date('d') - 1;
        return [
            mktime(0, 0, 0, date('m'), $yesterday, date('Y')),
            mktime(23, 59, 59, date('m'), $yesterday, date('Y'))
        ];
    }
    public static function week()
    {
        list($y, $m, $d, $w) = explode('-', date('Y-m-d-w'));
        if($w == 0) $w = 7; //修正周日的问题
        return [
            mktime(0, 0, 0, $m, $d - $w + 1, $y), mktime(23, 59, 59, $m, $d - $w + 7, $y)
        ];
    }
    public static function lastWeek()
    {
        $timestamp = time();
        return [
            strtotime(date('Y-m-d', strtotime("last week Monday", $timestamp))),
            strtotime(date('Y-m-d', strtotime("last week Sunday", $timestamp))) + 24 * 3600 - 1
        ];
    }
    public static function month($everyDay = false)
    {
        list($y, $m, $t) = explode('-', date('Y-m-t'));
        return [
            mktime(0, 0, 0, $m, 1, $y),
            mktime(23, 59, 59, $m, $t, $y)
        ];
    }
    public static function lastMonth()
    {
        $y = date('Y');
        $m = date('m');
        $begin = mktime(0, 0, 0, $m - 1, 1, $y);
        $end = mktime(23, 59, 59, $m - 1, date('t', $begin), $y);
        return [$begin, $end];
    }
    public static function year()
    {
        $y = date('Y');
        return [
            mktime(0, 0, 0, 1, 1, $y),
            mktime(23, 59, 59, 12, 31, $y)
        ];
    }
    public static function lastYear()
    {
        $year = date('Y') - 1;
        return [
            mktime(0, 0, 0, 1, 1, $year),
            mktime(23, 59, 59, 12, 31, $year)
        ];
    }
    public static function dayOf()
    {
    }
    public static function dayToNow($day = 1, $now = true)
    {
        $end = time();
        if (!$now) {
            list($foo, $end) = self::yesterday();
        }
        return [
            mktime(0, 0, 0, date('m'), date('d') - $day, date('Y')),
            $end
        ];
    }
    public static function daysAgo($day = 1)
    {
        $nowTime = time();
        return $nowTime - self::daysToSecond($day);
    }
    public static function daysAfter($day = 1)
    {
        $nowTime = time();
        return $nowTime + self::daysToSecond($day);
    }
    public static function daysToSecond($day = 1)
    {
        return $day * 86400;
    }
    public static function weekToSecond($week = 1)
    {
        return self::daysToSecond() * 7 * $week;
    }
    private static function startTimeToEndTime()
    {
    }
}
