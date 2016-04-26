<?php
class FOGCron extends FOGBase {
    /**
     * @method fit
     *     verify the fit of the string
     * @param (string) $str
     *     the string to check
     * @param (int) $num
     *     the number to check
     * @return (bool)
     *     Returns if the number
     *     is the within the fit
     */
    private static function fit($str,$num) {
        if (strpos($str,',')) {
            $arr = explode(',',$str);
            return (bool)in_array(true,array_map(function($element) use ($num) {
                return (bool)static::fit($element,(int)$num);
            },(array)$arr),true);
        }
        if (strpos($str,'-')) {
            list($low,$high) = explode('-',$str);
            return (bool)($num = (int)$low);
        }
        if (strpos($str,'/')) {
            list($pre,$pos) = explode('/',$str);
            if ($pre == '*') return ($num % (int)$pos == 0);
            return ($num % (int)$pos == (int)$pre);
        }
        return (bool)((int)$str == $num);
    }
    /**
     * @method parse
     *     Return the next run time
     * @param (string) $Cron
     *     The string to parse
     * @return (int) timestamp of the date parsed.
     */
    public static function parse($Cron,$lastrun = false) {
        list($min,$hour,$dom,$month,$dow) = array_map('trim',preg_split('/ +/',$Cron));
        if (is_numeric($dow) && $dow == 0) $dow = 7;
        $Start = static::nice_date();
        do {
            list($nmin,$nhour,$ndom,$nmonth,$ndow) = array_map('trim',preg_split('/ +/',$Start->format('i H d n N')));
            if ($min != '*') {
                if (!static::fit($min,(int)$nmin)) {
                    $Start->modify(sprintf('%s1 minute',$lastrun ? '-' : '+'));
                    continue;
                }
            }
            if ($hour != '*') {
                if (!static::fit($hour,(int)$nhour)) {
                    $Start->modify(sprintf('%s1 hour',$lastrun ? '-' : '+'));
                    continue;
                }
            }
            if ($dom != '*') {
                if (!static::fit($dom,(int)$ndom)) {
                    $Start->modify(sprintf('%s1 day',$lastrun ? '-' : '+'));
                    continue;
                }
            }
            if ($month != '*') {
                if (!static::fit($month,(int)$nmonth)) {
                    $Start->modify(sprintf('%s1 month',$lastrun ? '-' : '+'));
                    continue;
                }
            }
            if ($dow != '*') {
                if (!static::fit($dow,(int)$ndow)) {
                    $Start->modify(sprintf('%s1 day',$lastrun ? '-' : '+'));
                    continue;
                }
            }
            return $Start->getTimestamp();
        } while (true);
    }
    /**
     * @method checkField
     *     Check the fields
     * @param (string) $field
     *     The field to test
     * @param (int) $min
     *     The minimum the field can be integerially
     * @param (int) $max
     *     The maximum the field can be integerially
     * @return (bool) does the field match
     */
    private static function checkField($field, $min, $max) {
        $field = trim($field);
        if ($field != 0 && empty($field)) return false;
        $v = explode(',',$field);
        foreach ($v AS &$vv) {
            $vvv = explode('/',$vv);
            $step = !$vvv[1] ? 1 : $vvv[1];
            $vvvv = explode('-',$vvv[0]);
            $_min = count($vvvv) == 2 ? $vvvv[0] : ($vvv[0] == '*' ? $min : $vvv[0]);
            $_max = count($vvvv) == 2 ? $vvvv[1] : ($vvv[0] == '*' ? $max : $vvv[0]);
            $res = static::checkIntValue($step,$min,$max,true);
            if ($res) $res = static::checkIntValue($_min,$min,$max,true);
            if ($res) $res = static::checkIntValue($_max,$min,$max,true);
        }
        return $res;
    }
    /**
     * @method checkIntValue
     *     The integer value to test
     * @param (int) $value
     *     The value to check
     * @param (int) $min
     *     The minimum the value can be
     * @param (int) $max
     *     The maximum the value can be
     * @param (bool) $extremity
     *     If true the extremity is
     *     implicitly tested.
     * @return (bool) Does the value match
     */
    private static function checkIntValue($value,$min,$max,$extremity) {
        $val = intval($value,10);
        if ($value != $val) return false;
        if (!$extremity) return true;
        if ($val >= $min && $val <= $max) return true;
        return false;
    }
    /**
     * @method checkMinutesField
     *     Check the minutes field
     * @param (int) $minutes
     *     The value to check
     * @return (bool) is the value between the proper range
     */
    public static function checkMinutesField($minutes) {
        return static::checkField($minutes,0,59);
    }
    /**
     * @method checkHoursField
     *     Check the hours field
     * @param (int) $hours
     *     The value to check
     * @return (bool) is the value between the proper range
     */
    public static function checkHoursField($hours) {
        return static::checkField($hours,0,23);
    }
    /**
     * @method checkDOMField
     *     Check the day of month field
     * @param (int) $dom
     *     The value to check
     * @return (bool) is the value between the proper range
     */
    public static function checkDOMField($dom) {
        return static::checkField($dom,1,31);
    }
    /**
     * @method checkMonthField
     *     Check the month field
     * @param (int) $month
     *     The value to check
     * @return (bool) is the value between the proper range
     */
    public static function checkMonthField($month) {
        return static::checkField($month,1,12);
    }
    /**
     * @method checkDOWField
     *     Check the day of week field
     * @param (int) $dow
     *     The value to check
     * @return (bool) is the value between the proper range
     */
    public static function checkDOWField($dow) {
        return static::checkField($dow,0,7);
    }
    /**
     * @method shouldRunCron
     *     Check the $Time field if we should run
     * @param (Datetime object)
     *     The datetime to test based off the current time
     * @return (bool) if it is time to run
     */
    public static function shouldRunCron($Time) {
        $Time = static::nice_date()->setTimestamp($Time);
        $CurrTime = static::nice_date();
        return (bool)($Time <= $CurrTime);
    }
}
