<?php
namespace think\model\concern;
use DateTime;
trait TimeStamp
{
    protected $autoWriteTimestamp;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $dateFormat;
    protected function formatDateTime($format, $time = 'now', $timestamp = false)
    {
        if (empty($time)) {
            return;
        }
        if (false === $format) {
            return $time;
        } elseif (false !== strpos($format, '\\')) {
            return new $format($time);
        }
        if ($timestamp) {
            $dateTime = new DateTime();
            $dateTime->setTimestamp($time);
        } else {
            $dateTime = new DateTime($time);
        }
        return $dateTime->format($format);
    }
    protected function checkTimeStampWrite()
    {
        if ($this->autoWriteTimestamp) {
            if ($this->createTime && !isset($this->data[$this->createTime])) {
                $this->data[$this->createTime] = $this->autoWriteTimestamp($this->createTime);
            }
            if ($this->updateTime && !isset($this->data[$this->updateTime])) {
                $this->data[$this->updateTime] = $this->autoWriteTimestamp($this->updateTime);
            }
        }
    }
}
