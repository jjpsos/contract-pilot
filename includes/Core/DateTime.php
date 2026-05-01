<?php


namespace Otto\Core;

use DateTime as DT;

defined("ABSPATH") || exit();


class DateTime extends DT
{
    
    protected $utc_offset = 0;

    
    public function __toString()
    {
        return $this->date_mysql();
    }

    
    public function copy()
    {
        return clone $this;
    }

    
    public function set_utc_offset($offset)
    {
        $this->utc_offset = intval($offset);
    }

    
    public function getOffset()
    {
        return $this->utc_offset ? $this->utc_offset : parent::getOffset();
    }

    
    public function setTimezone($timezone)
    {
        $this->utc_offset = 0;
        return parent::setTimezone($timezone);
    }

    
    public function addYear($number = 1)
    {
        $this->add(new \DateInterval("P{$number}Y"));

        return $this;
    }

    
    public function addMonth($number = 1)
    {
        $this->add(new \DateInterval("P{$number}M"));

        return $this;
    }

    
    public function addDay($number = 1)
    {
        $this->add(new \DateInterval("P{$number}D"));

        return $this;
    }

    
    public function subYear($number = 1)
    {
        $this->sub(new \DateInterval("P{$number}Y"));

        return $this;
    }

    
    public function subMonth($number = 1)
    {
        $this->sub(new \DateInterval("P{$number}M"));

        return $this;
    }

    
    public function subDay($number = 1)
    {
        $this->sub(new \DateInterval("P{$number}D"));

        return $this;
    }

    
    public function getTimestamp()
    {
        return method_exists("DateTime", "getTimestamp")
            ? parent::getTimestamp()
            : $this->format("U");
    }

    
    public function getOffsetTimestamp()
    {
        return $this->getTimestamp() + $this->getOffset();
    }

    
    public function date($format)
    {
        return gmdate($format, $this->getOffsetTimestamp());
    }

    
    public function date_i18n($format = "Y-m-d")
    {
        return date_i18n($format, $this->getOffsetTimestamp());
    }

    
    public function date_mysql()
    {
        return wp_date("Y-m-d H:i:s", $this->getOffsetTimestamp());
    }

    
    public function quarter()
    {
        return ceil($this->format("m") / 3);
    }

    
    public function get_quarter()
    {
        return ceil($this->format("m") / 3);
    }
}
