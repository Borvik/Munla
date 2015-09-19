<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * is
 * Contains functions that check information.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class is extends extender{
    
    /**
     * Checks if SSL is enabled or not.
     * 
     * @return bool
     *   Returns TRUE if SSL is enabled, FALSE otherwise.
     */
    public static function ssl(){
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
    }
    
    /**
     * Checks if the script was called from AJAX or not.
     * 
     * @return boolean
     *   Returns True if the script was called from AJAX, false otherwise.
     */
    public static function ajax(){
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
    }
    
    /**
     * Checks whether a given array is an associative array
     * 
     * @param array
     *   The array to check.
     * 
     * @return bool
     *   Returns TRUE if the array is an associative array, FALSE otherwise.
     */
    public static function assoc_array($a){
        if( !is_array($a) ) return false;
        return (0 !== count(array_diff_key($a, array_keys(array_keys($a)))));
    }
    
    /**
     * Checks whether a given array key exists and is set.
     * 
     * @param array $array The array to check against.
     * @param mixed $key The key in the array to check.
     * 
     * @return bool TRUE if the key exists and is set, otherwise false.
     */
    public static function existset(array &$array, $key){
        return (array_key_exists($key, $array) && isset($array[$key]));
    }
    
    /**
     * Checks whether a given array key exists and is set, and if it matches the
     * given value.
     * 
     * @param array $array The array to check against.
     * @param mixed $key The key in the array to check.
     * @param mixed $value The value to match against.
     * 
     * @return bool TRUE if the key exists and matches the value, otherwise false.
     */
    public static function set(array &$array, $key, $value){
        if( !array_key_exists($key, $array) ) return false;
        return ($array[$key] == $value);
    }
    
    /**
     * Checks if a given object is a call of the specified type.
     * This routine takes into account the secureClass objects, and checks against the inner object.
     * 
     * If $classname is left off or is null, then this just checks if the object is a class.
     * 
     * @param mixed $value The value or object to check.
     * @param string $classname The name of the class to compare to.
     * @param bool $childClass Whether to check if the class is a INSTANCEOF the class (allows subclasses), or the EXACT class specified.
     * 
     * @return bool TRUE if $value is a class and matches the $classname, otherwise FALSE.
     */
    public static function of_class($value = null, $classname = null, $childClass = false){
        if( !isset($value) || !is_object($value) ) return false;
        if( !isset($classname) || !is_string($classname) ) return true;
        
        if( $value instanceof secureClass )
            return (($childClass && $value->getObject() instanceof $classname) || (!$childClass && get_class($value->getObject()) == $classname));
        return (($childClass && $value instanceof $classname) || (!$childClass && get_class($value) == $classname));
    }
    
    /**
     * Checks if the given object is a model object.
     * This routine takes into account the secureClass objects, and checks against the inner object.
     * 
     * @param mixed $value The value or object to check.
     * @param string $modelname If specified, checks whether the model is of the specified model type (may omit "Model" from the model name).
     * 
     * @return bool TRUE if $value is a class that is a model, otherwise FALSE.
     */
    public static function model($value = null, $modelname = null){
        if( !isset($value) || !is_object($value) ) return false;
        $is_model =  (($value instanceof model) || (($value instanceof secureClass) && ($value->getObject() instanceof model)));
        
        if( !isset($modelname) || !is_string($modelname) ) return $is_model;
        if( (strlen($modelname) >= 5 && substr($modelname, -5) != 'Model') || strlen($modelname) < 5 )
            $modelname .= 'Model';
        if( $value instanceof secureClass )
            return (get_class($value->getObject()) == $modelname);
        return (get_class($value) == $modelname);
    }
    
    /**
     * Checks if the given string is a valid http(s) or ftp url.
     * 
     * @param string $url
     *   The value to check.
     * 
     * @param bool $schemeOptional
     *   Allows the scheme (http, https, or ftp) to be missing.
     * 
     * @return mixed
     *   Return false upon failure, or an array containing the details of the url.
     *   At least fullmatch and one other key will be returned.
     *   - fullmatch: The full url matched.
     *   - scheme: e.g. http
     *   - host: The Domain name
     *   - port: The port specified
     *   - user: The user
     *   - pass: The password
     *   - path: The path after the domain name.
     *   - query: after the question mark ?
     *   - fragment: after the hashmark #
     */
    public static function url($url, $schemeOptional = false){
        //$urlregex = "/^((https?|ftp)\:\/\/)".($schemeOptional ? '?' : '')."([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?([a-z0-9-.]*)\.([a-z]{2,3})(\:[0-9]{2,5})?(\/([a-z0-9+\$_-]\.?)+)*\/?(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?(#[a-z_.-][a-z0-9+\$_.-]*)?$/i";
        $urlregex = "/^((https?|ftp)\:\/\/)".($schemeOptional ? '?' : '')."([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?([a-z0-9-.]*)\.([a-z]{2,3})(\:[0-9]{2,5})?(\/([a-z0-9+\$_%-]\.?)+)*\/?(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?(#[a-z_.-][a-z0-9+\$_.-]*)?$/i";
        $return = false;
        if( isset($url) && preg_match($urlregex, trim($url), $matches) ){
            $return = new url(parse_url(trim($url)));
            $return['fullmatch'] = $matches[0];
        }
        return $return;
    }
    
    /**
     * Checks if the given string is a valid email address.
     * 
     * Validates mostly to the spec defined here:
     * http://www.whatwg.org/specs/web-apps/current-work/multipage/states-of-the-type-attribute.html#valid-e-mail-address
     * Which in ABNF is: 1*( atext / "." ) "@" ldh-str *( "." ldh-str )
     * 
     * See http://www.ietf.org/rfc/rfc2234.txt for ABNF specifications.
     * 
     * There are however some rules, I felt should not be ignored, so I am validating
     * against the following ABNF: atext *( "." atext ) "@" ldh-str *( "." ldh-str )
     * This makes sure that the user and domain do not start with a stop character, and
     * do not have repeating stop characters ("..").
     * 
     * According to the documentation atext and ldh-str are defined as thus:
     * atext: a-zA-Z0-9!#$%&'*+-/=?^_`{|}~
     * ldh-str: a-zA-Z0-9-
     * 
     * @param string $email The value to check.
     * @param bool $allowMultiple Whether or not to allow multiple emails in the string (separated by commas).
     * 
     * @return mixed
     *   Returns false if the email address is invalid, otherwise a multi-dimensional array
     *   with the subarrays containing the following keys:
     *   - email: The full valid email address is the key to another array:
     *   - user: The left side of the email address identifying the user.
     *   - domain: The right side of the email address identifying the server.
     */
    public static function email($email, $allowMultiple = false){
        if( !isset($email) )
            return false;
        
        $emails = array(trim($email));
        if( $allowMultiple ) $emails = array_map('trim', explode(',', $email));
        
        $validEmails = new emailAddressList(); //array();
        $atext = 'a-zA-Z0-9!#$%&\'*+\\-\\/=?^_`{|}~';
        $ldhstr = 'a-zA-Z0-9-';
        $regex = "([$atext]+(?:\\.[$atext]+)*)@([$ldhstr]+(?:\\.[$ldhstr]+)+)";
        $idx = 0;
        foreach($emails as $eml){
            if( preg_match('/^'.$regex.'$/', $eml, $m) ){
               //$validEmails[$m[0]] = array('user' => $m[1], 'domain' => $m[2]);
               //$validEmails[] = array('email' => $m[0], 'user' => $m[1], 'domain' => $m[2]);
               $validEmails[$idx++] = new emailAddress($m[0], $m[1], $m[2]);
            }else{
                $validEmails = new emailAddressList(); //array();
                break;
            }
        }
        
        return (count($validEmails) < 1) ? false : ($allowMultiple ? $validEmails : $validEmails[0]);
    }
    
    /**
     * Checks to see if the given input is a valid date according to the HTML5
     * specifications used for date fields.
     * 
     * See http://dev.w3.org/html5/spec/common-microsyntaxes.html#dates
     *  for the rules on parsing dates
     * 
     * @param string $input
     *   The value to check.
     * 
     * @return mixed
     *   Returns a boolean false if the input is not a date, otherwise it
     *   returns an array with the following keys.
     *   - year: The numeric year.
     *   - month: The numeric month.
     *   - day: The numeric day.
     *   - unixdate: Unix timestamp as created from mktime.
     *   - datetime: A DateTime class containing the date.
     *   - formatted: US formatted date string m/d/y.
     *   - htmlspec: The formatted date as it should be entered in an HTML form (Y-m-d).
     */
    public static function date($input){
        if( !isset($input) ) return false;
        
        $in = trim($input);
        $year = self::getNextPart($in, '-');
        if( strlen($in) < 5 || strlen($year) < 1 || !ctype_digit($year) || (int)$year < 1 ) return false;
        
        $month = self::getNextPart($in, '-', 2);
        if( strlen($in) < 2 || !ctype_digit($in) || strlen($month) < 1 || !ctype_digit($month) || (int)$month < 1 || (int)$month > 12 )
            return false;
        
        $year = (int)$year;
        $month = (int)$month;
        $day = (int)$in;
        if( $day < 1 || $day > date('t', mktime(0,0,0, $month, 1, $year)) ) return false;
        $dt = new DateTime();
        $dt->setDate($year, $month, $day);
        $dt->setTime(0, 0);
        return new cDateTime($dt, cDateTime::DATE_KIND);
    }
    
    public static function month($input){
        if( !isset($input) ) return false;
        
        $in = trim($input);
        $year = self::getNextPart($in, '-');
        if( strlen($in) != 2 || strlen($year) < 1 || !ctype_digit($year) || !ctype_digit($in) || (int)$year < 1 || (int)$in < 1 || (int)$in > 12 ) return false;
        
        $dt = new DateTime();
        $dt->setDate((int)$year, (int)$in, 1);
        $dt->setTime(0, 0);
        return new cDateTime($dt, cDateTime::MONTH_KIND);
    }
    
    public static function dt_week($input){
        if( !isset($input) ) return false;
        
        $in = trim($input);
        $year = self::getNextPart($in, '-');
        if( strlen($in) != 3 || strlen($year) < 1 || !ctype_digit($year) || (int)$year < 1 || substr($in, 0, 1) != 'W' || !ctype_digit(substr($in, 1)) ) return false;
        
        $year = (int)$year;
        $week = (int)substr($in, 1);
        
        $dt = new DateTime();
        $dt->setISODate($year, 53);
        $weeksInYear = ($dt->format('W') === '53' ? 53 : 52);
        if( $week < 1 || $week > $weeksInYear ) return false;
        
        $dt->setISODate($year, $week);
        $dt->setTime(0, 0);
        return new cDateTime($dt, cDateTime::WEEK_KIND);
    }
    
    /**
     * Checks to see if the given input is a valid time according to the HTML5
     * specifications used for time fields.
     * 
     * See http://dev.w3.org/html5/spec/common-microsyntaxes.html#dates
     *  for the rules on parsing dates
     * 
     * @param string $input
     *   The value to check.
     * 
     * @return mixed
     *   Returns a boolean false if the input is not a time, otherwise it
     *   returns an array with the following keys.
     *   - hour: The numeric hour in 24 notation.
     *   - minute: The numeric minute.
     *   - second: The numeric second.
     *   - faction: The numeric fraction of a second.
     *   - meridiem: AM/PM
     *   - format24: The formatted time using 24 hour notation.
     *   - format12: The formatted time using 12 hour notation without the meridiem.
     *   - unixtime: The unix timestamp as created by mktime.
     *   - datetime: A DateTime class containing the time.
     *   - isutc: Boolean indicating whether the time is UTC(Zulu) time or not.
     */
    public static function time($input){
        if( !isset($input) ) return false;
        
        $in = trim($input);
        $hour = self::getNextPart($in, ':', 2);
        if( strlen($in) < 2 || strlen($hour) < 1 || !ctype_digit($hour) || (int)$hour < 0 || (int)$hour > 23 )
            return false;
        
        $min = self::getNextPart($in, ':', 2);
        if( strlen($min) < 1 || !ctype_digit($min) || (int)$min < 0 || (int)$min > 59 )
            return false;
        
        $dt = new DateTime();
        $offset = (int)$dt->format('Z');
        $sec = 0; $fraction = 0;
        if( strlen($in) >= 2 ){
            $sec = self::getNextPart($in, '.', 2, true);
            if( strlen($sec) < 1 || !ctype_digit($sec) || (int)$sec < 0 || (int)$sec > 59 )
                return false;
        }
        
        if( strlen($in) == 1 && substr($in, 0, 1) == '.' ) $in = '';
        if( strlen($in) > 1 && substr($in, 0, 1) == '.' ){
            $in = substr($in, 1);
            $fraction = self::getNextPartUntil($in, '0123456789');
            if( strlen($fraction) < 1 ) return false;
        }
        
        if( strlen($in) == 1 && substr($in, 0, 1) == 'Z' ) $offset = 0;
        elseif( strlen($in) == 6 && strpos('+-', substr($in, 0, 1)) !== false ){
            $mod = substr($in, 0, 1); $in = substr($in, 1);
            $ohour = self::getNextPart($in, ':', 2);
            if( strlen($in) != 2 || strlen($ohour) < 2 || !ctype_digit($ohour) || (int)$ohour < 0 || (int)$ohour > 23 || !ctype_digit($in) || (int)$in < 0 || (int)$in > 59 ) return false;
            $offset += ($ohour * 3600);
            $offset += ($in * 60);
        }elseif( strlen($in) > 0 ) return false;
        
        $hour = (int)$hour; $min = (int)$min; $sec = (int)$sec; $fraction = (int)$fraction;
        if( $offset != (int)$dt->format('Z') ){
            if( $offset == 0 ) $dt->setTimezone(new DateTimeZone('UTC'));
            else{
                $tz = timezone_name_from_abbr(null, $offset, true);
                if( $tz === false ) $tz = timezone_name_from_abbr(null, $offset, false);
                if( $tz !== false )
                    $dt->setTimezone(new DateTimeZone($tz));
            }
        }
        
        $dt->setTime($hour, $min, $sec);
        return new cDateTime($dt, cDateTime::TIME_KIND);
    }
    
    /**
     * Checks to see if the given input is a valid date/time according to the HTML5
     * specifications used for datetime fields.
     * 
     * See http://dev.w3.org/html5/spec/common-microsyntaxes.html#dates
     *  for the rules on parsing dates
     * 
     * @param string $input
     *   The value to check.
     * 
     * @return mixed
     *   Returns a boolean false if the input is not a time, otherwise it
     *   returns an array with the following keys.
     *   - year: The numeric year.
     *   - month: The numeric month.
     *   - day: The numeric day.
     *   - formatted: US formatted date string m/d/y.
     *   - hour: The numeric hour in 24 notation.
     *   - minute: The numeric minute.
     *   - second: The numeric second.
     *   - faction: The numeric fraction of a second.
     *   - meridiem: AM/PM
     *   - format24: The formatted time using 24 hour notation.
     *   - format12: The formatted time using 12 hour notation without the meridiem.
     *   - unixdatetime: The unix datetimestamp as created by mktime.
     *   - datetime: A DateTime class containing the date/time.
     *   - isutc: Boolean indicating whether the time is UTC(Zulu) time or not.
     */
    public static function datetime($input, $local = false){
        if( !isset($input) ) return false;
        
        $in = trim($input);
        if( substr_count($in, 'T') != 1 ) return false;
        list($datepart, $timepart) = explode('T', $in);
        
        $dateValid = is::date($datepart);
        if( $dateValid === false ) return false;
        
        $timeValid = is::time($timepart);
        if( $timeValid === false ) return false;
        
        $timeValid->setDate($dateValid->format('Y'), $dateValid->format('m'), $dateValid->format('d'));
        return new cDateTime($timeValid, ($local === true ? cDateTime::DATETIMELOCAL_KIND : cDateTime::DATETIME_KIND));
    }
    
    /**
     * Gets the next section of the string, with section being defined as a char
     * or number of characters.  The section is removed from the input string and
     * returned.  Stops at whichever comes first, the stop character or the section
     * length.
     * 
     * @param string $input
     *   The string to get a section of.
     * 
     * @param char $stopChar
     *   The character to stop at.  May be null if you just want to stop after a number of characters.
     * 
     * @param int $numChars
     *   The maximum number of characters for the section. Defaults to -1 for unlimited (must have $stopChar).
     * 
     * @param boolean $doNoTrimStopChar
     *   Defaults to false.  Whether the stop character should be removed from the input string or not.
     *   True prevents trimming, false allows it.
     * 
     * @return string
     *   The next section of the string, or an empty string.
     */
    private static function getNextPart(&$input, $stopChar = null, $numChars = -1, $doNotTrimStopChar = false){
        if( !isset($input) || strlen($input) < 1 || $numChars === 0 || (!isset($stopChar) && $numChars < 1) )
            return '';
        
        $position = 0; $part = '';
        while( $position < strlen($input) && ($numChars < 0 || ($numChars > 0 && strlen($part) < $numChars)) && ($stopChar === null || substr($input, $position, 1) != $stopChar) ) //(($numChars > 0 && strlen($part) < $numChars) ||
               //($stopChar !== null && substr($input, $position, 1) != $stopChar)) )
            $part .= substr($input, $position++, 1);
        
        if( $position >= strlen($input) )
            $input = '';
        else{
            if( !$doNotTrimStopChar && $stopChar !== null && substr($input, $position, 1) == $stopChar )
                $position++;
            $input = substr($input, $position);
        }
        return $part;
    }
    
    /**
     * Gets the next section of the string, with section being defined as a char
     * or number of allowed characters.  The section is removed from the input string and
     * returned.
     * 
     * @param string $input
     *   The string to get a section of.
     * 
     * @param string $allowedChars
     *   A string of characters to allow.
     * 
     * @return string
     *   The next section of the string, or an empty string.
     */
    private static function getNextPartUntil(&$input, $allowedChars = null){
        if( !isset($input) || strlen($input) < 1 || !isset($allowedChars) || strlen($allowedChars) < 1 )
            return '';
        
        $position = 0; $part = '';
        while( $position < strlen($input) && strpos($allowedChars, substr($input, $position, 1)) !== false )
            $part .= substr($input, $position++, 1);
        
        if( $position >= strlen($input) ) $input = '';
        else $input = substr($input, $position);
        
        return $part;
    }
    
    /**
     * Checks to see if the given input is a valid float according to the HTML5
     * specifications used for number fields.
     * 
     * See http://dev.w3.org/html5/spec/common-microsyntaxes.html#rules-for-parsing-floating-point-number-values
     *  for the rules on parsing floats
     * 
     * @param string $input
     *   The value to check.
     * 
     * @return mixed
     *   Returns a boolean false if input is not a valid float, otherwise it
     *   returns the number as a numeric value (as opposed to the string input).
     */
    public static function float($input){
        if( !isset($input) || strlen(trim($input)) < 1 ) return false;
        
        $input = trim($input);
        $position = 0;
        $value = 1; $divisor = 1; $exponent = 1;
        if( substr($input, $position, 1) == '-' ){
            $value = -1; $divisor = -1;
            if( ++$position >= strlen($input) )
                return false;
        }elseif( substr($input, $position, 1) == '+' ){
            if( ++$position >= strlen($input) )
                return false;
        }
        
        if( !ctype_digit(substr($input, $position, 1)) )
            return false;
        
        $seq = '';
        while( $position < strlen($input) && ctype_digit(substr($input, $position, 1)) ){
            $seq .= substr($input, $position++, 1);
        }
        $value *= (int)$seq;
        if( $position < strlen($input) ){
            $char = substr($input, $position, 1);
            if( $char == '.' ){
                $position++;
                while( $position < strlen($input) && ctype_digit(substr($input, $position, 1)) ){
                    $divisor *= 10;
                    $value += ((int)substr($input, $position++, 1) / $divisor);
                }
            }elseif( $char != 'e' && $char != 'E' )
                return false;
            
            if( $position < strlen($input) ){
                if( substr($input, $position, 1) == 'e' || substr($input, $position, 1) == 'E' ){
                    $position++;
                    if( $position < strlen($input) ){
                        if( substr($input, $position, 1) == '-' ){
                            $position++;
                            $exponent = -1;
                        }elseif( substr($input, $position, 1) == '+' ){
                            $position++;
                        }
                        if( $position < strlen($input) && ctype_digit(substr($input, $position, 1)) ){
                            $seq = '';
                            while( $position < strlen($input) && ctype_digit(substr($input, $position, 1)) ){
                                $seq .= substr($input, $position++, 1);
                            }
                            $exponent *= ((int)$seq);
                            $value *= pow(10, $exponent);
                        }
                    }
                }else
                    return false;
            }
        }
        //conversion
        return $value;
    }
    
    /**
     * Checks whether a given string is a valid color string.
     * Where H is a hexadecimal number it may be in the following formats:
     * - HHH
     * - HHHHHH
     * - #HHH
     * - #HHHHHH
     * 
     * @param string $color
     *   The value to check.
     * 
     * @return string|bool
     *   Boolean false when it is not a valid color, or a string in the
     *   format #HHHHHH to represent the color.
     */
    public static function color($color){
        if( !isset($color) ) return false;
        
        $value = strtolower(trim($color));
        if( array_key_exists($color, self::$css3colors) ) return self::$css3colors[$color];
        
        $validLengths = array(3, 4, 6, 7);
        $len = strlen($value);
        $return = false;
        if( in_array($len, $validLengths) ){
            $firstChar = substr($value, 0, 1);
            if( ($len == 4 || $len == 7) && $firstChar != '#' )
                return false;
            
            if( $firstChar == '#' )
                $value = substr($value, 1);
            
            $validDigits = '0123456789abcdef';
            $len = strlen($value);
            $realValue = '#'; $badColor = false;
            for($i = 0; $i < $len; $i++){
                $checkDigit = substr($value, $i, 1);
                if( strpos($validDigits, $checkDigit) === false ){
                    //found an invalid digit
                    $badColor = true;
                    break;
                }else
                    $realValue .= str_repeat($checkDigit, ($len == 3 ? 2 : 1));
            }
            $return = ($badColor) ? false : $realValue;
        }
        return $return;
    }
    private static $css3colors = array(
        'aliceblue' => '#f0f8ff', 'antiquewhite' => '#faebd7', 'aqua' => '#00ffff', 'aquamarine' => '#7fffd4', 'azure' => '#f0ffff', 'beige' => '#f5f5dc',
        'bisque' => '#ffe4c4', 'black' => '#000000', 'blanchedalmond' => '#ffebcd', 'blue' => '#0000ff', 'blueviolet' => '#8a2be2', 'brown' => '#a52a2a',
        'burlywood' => '#deb887', 'cadetblue' => '#5f9ea0', 'chartreuse' => '#7fff00', 'chocolate' => '#d2691e', 'coral' => '#ff7f50', 'cornflowerblue' => '#6495ed',
        'cornsilk' => '#fff8dc', 'crimson' => '#dc143c', 'cyan' => '#00ffff', 'darkblue' => '#00008b', 'darkcyan' => '#008b8b', 'darkgoldenrod' => '#b8860b',
        'darkgray' => '#a9a9a9', 'darkgreen' => '#006400', 'darkgrey' => '#a9a9a9', 'darkkhaki' => '#bdb76b', 'darkmagenta' => '#8b008b', 'darkolivegreen' => '#556b2f',
        'darkorange' => '#ff8c00', 'darkorchid' => '#9932cc', 'darkred' => '#8b0000', 'darksalmon' => '#e9967a', 'darkseagreen' => '#8fbc8f', 'darkslateblue' => '#483d8b',
        'darkslategray' => '#2f4f4f', 'darkslategrey' => '#2f4f4f', 'darkturquoise' => '#00ced1', 'darkviolet' => '#9400d3', 'deeppink' => '#ff1493', 'deepskyblue' => '#00bfff',
        'dimgray' => '#696969', 'dimgrey' => '#696969', 'dodgerblue' => '#1e90ff', 'firebrick' => '#b22222', 'floralwhite' => '#fffaf0', 'forestgreen' => '#228b22',
        'fuchsia' => '#ff00ff', 'gainsboro' => '#dcdcdc', 'ghostwhite' => '#f8f8ff', 'gold' => '#ffd700', 'goldenrod' => '#daa520', 'gray' => '#808080', 'green' => '#008000',
        'greenyellow' => '#adff2f', 'grey' => '#808080', 'honeydew' => '#f0fff0', 'hotpink' => '#ff69b4', 'indianred' => '#cd5c5c', 'indigo' => '#4b0082', 'ivory' => '#fffff0',
        'khaki' => '#f0e68c', 'lavender' => '#e6e6fa', 'lavenderblush' => '#fff0f5', 'lawngreen' => '#7cfc00', 'lemonchiffon' => '#fffacd', 'lightblue' => '#add8e6',
        'lightcoral' => '#f08080', 'lightcyan' => '#e0ffff', 'lightgoldenrodyellow' => '#fafad2', 'lightgray' => '#d3d3d3', 'lightgreen' => '#90ee90', 'lightgrey' => '#d3d3d3',
        'lightpink' => '#ffb6c1', 'lightsalmon' => '#ffa07a', 'lightseagreen' => '#20b2aa', 'lightskyblue' => '#87cefa', 'lightslategray' => '#778899', 'lightslategrey' => '#778899',
        'lightsteelblue' => '#b0c4de', 'lightyellow' => '#ffffe0', 'lime' => '#00ff00', 'limegreen' => '#32cd32', 'linen' => '#faf0e6', 'magenta' => '#ff00ff',
        'maroon' => '#800000', 'mediumaquamarine' => '#66cdaa', 'mediumblue' => '#0000cd', 'mediumorchid' => '#ba55d3', 'mediumpurple' => '#9370db', 'mediumseagreen' => '#3cb371',
        'mediumslateblue' => '#7b68ee', 'mediumspringgreen' => '#00fa9a', 'mediumturquoise' => '#48d1cc', 'mediumvioletred' => '#c71585', 'midnightblue' => '#191970',
        'mintcream' => '#f5fffa', 'mistyrose' => '#ffe4e1', 'moccasin' => '#ffe4b5', 'navajowhite' => '#ffdead', 'navy' => '#000080', 'oldlace' => '#fdf5e6', 'olive' => '#808000',
        'olivedrab' => '#6b8e23', 'orange' => '#ffa500', 'orangered' => '#ff4500', 'orchid' => '#da70d6', 'palegoldenrod' => '#eee8aa', 'palegreen' => '#98fb98', 'paleturquoise' => '#afeeee',
        'palevioletred' => '#db7093', 'papayawhip' => '#ffefd5', 'peachpuff' => '#ffdab9', 'peru' => '#cd853f', 'pink' => '#ffc0cb', 'plum' => '#dda0dd', 'powderblue' => '#b0e0e6',
        'purple' => '#800080', 'red' => '#ff0000', 'rosybrown' => '#bc8f8f', 'royalblue' => '#4169e1', 'saddlebrown' => '#8b4513', 'salmon' => '#fa8072', 'sandybrown' => '#f4a460',
        'seagreen' => '#2e8b57', 'seashell' => '#fff5ee', 'sienna' => '#a0522d', 'silver' => '#c0c0c0', 'skyblue' => '#87ceeb', 'slateblue' => '#6a5acd', 'slategray' => '#708090',
        'slategrey' => '#708090', 'snow' => '#fffafa', 'springgreen' => '#00ff7f', 'steelblue' => '#4682b4', 'tan' => '#d2b48c', 'teal' => '#008080', 'thistle' => '#d8bfd8',
        'tomato' => '#ff6347', 'turquoise' => '#40e0d0', 'violet' => '#ee82ee', 'wheat' => '#f5deb3', 'white' => '#ffffff', 'whitesmoke' => '#f5f5f5', 'yellow' => '#ffff00', 'yellowgreen' => '#9acd32');
}