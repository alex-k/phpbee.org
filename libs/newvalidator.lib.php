<?php


class gs_validate_dummyValid {
function validate($field,$value,$data=array(),$params=array()) {
    return true;
}
}



class gs_validate_plain_word {
function validate($field,$value,$data=array(),$params=array()) {
	preg_match_all('/\w+/',$value,$ret);
	$ret=implode('',$ret[0]);
	return ($ret);
}
}





class gs_validate_isCCExpDate {
function validate($field,$value,$data=array(),$params=array()) {
    if(strlen($value) == 0)
        return $empty;

    if ( is_numeric($data[$params[fieldYear]]) && is_numeric($data[$params[fieldMonth]])) {
        $_month = (int)$data[$params[fieldMonth]];
        $_year = (int)$data[$params[fieldYear]];
    } else {
        if(!preg_match('!^(\d+)\D+(\d+)$!', $value, $_match))
            return false;
        $_month = $_match[1];
        $_year = $_match[2];
    }

    if(strlen($_year) == 2)
        $_year = substr(date('Y', time()),0,2) . $_year;

    if(!is_int($_month))
        return false;
    if($_month < 1 || $_month > 12)
        return false;
    if(!is_int($_year))
        return false;
    if(date('Y',time()) > $_year)
        return false;
    if(date('Y',time()) == $_year && date('m', time()) >= $_month)
        return false;

    return true;

}
}




 

class gs_validate_isCCNum {
function validate($field,$value,$data=array(),$params=array()) {
	if(strlen($value) == 0)
		return$params['empty']&&TRUE;

	if (substr($value,0,4)=='2222' || substr($value,0,4)=='3333')
		return true;
	
	global $_CONF;
	if (!empty($value) && ($value==$_CONF[auth_testcard_approve] || $value==$_CONF[auth_testcard_decline]))
		return true;

	// strip everything but digits
	$value = preg_replace('!\D+!', '', $value);

	if (empty($value))
		return false;

	$_c_digits = preg_split('//', $value, -1, PREG_SPLIT_NO_EMPTY);

	$_max_digit   = count($_c_digits)-1;
	$_even_odd    = $_max_digit % 2;

	$_sum = 0;
	for ($_count=0; $_count <= $_max_digit; $_count++) {
		$_digit = $_c_digits[$_count];
		if ($_even_odd) {
			if ($_digit > 9) {
				$_digit = substr($_digit, 1, 1) + 1;
			}
		}
		$_even_odd = 1 - $_even_odd;
		$_sum += $_digit;
	}
	$_sum = $_sum % 10;
	if($_sum)
		return false;
	return true;

}
}



class gs_validate_checkField {
function validate($field,$value,$data=array(),$params=array()) {
	$classname=$params['class'];
	$obj=new $classname;
	return $obj->check_field($params['field'],$value,$params);

}
}

 

class gs_validate_isCCType {
function validate($field,$value,$data=array(),$params=array()) {

	$ccNum=$data[$params[CCNumField]];
	$ccDigit=substr($ccNum,0,1);
	$ccType=$value;

	if (substr($ccNum,0,4)=='2222' || substr($ccNum,0,4)=='3333')
		return true;

	if ($ccType=='Visa' && $ccDigit!=4) 
		return false;
	if ($ccType=='Mastercard' && $ccDigit!=5) 
		return false;
	if ($ccType=='Amex' && $ccDigit!=3) 
		return false;
	if ($ccType=='JCB' && $ccDigit!=3) 
		return false;
	if ($ccType=='Diners club' && $ccDigit!=6) 
		return false;

	return true;

}
}




class gs_validate_isDate {
function validate($field,$value,$data=array(),$params=array()) {
    if(strlen($value) == 0)
        return $empty;

    return strtotime($value) != -1;
}
}




class gs_validate_isDateAfter {
function validate($field,$value,$data=array(),$params=array()) {

        if(strlen($value) == 0)
            return $empty;

        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
                return false;
        }
        
        $_date1 = strtotime($value);
        $_date2 = strtotime($data[$params['field2']]);
        
        if($_date1 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field' is not a valid date.");            
                return false;
        }
        if($_date2 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is not a valid date.");            
                return false;
        }
                
        return $_date1 > $_date2;
}
}




class gs_validate_isDateBefore {
function validate($field,$value,$data=array(),$params=array()) {

        if(strlen($value) == 0)
            return $empty;

        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
                return false;
        }
        
        $_date1 = strtotime($value);
        $_date2 = strtotime($data[$params['field2']]);
        
        if($_date1 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field' is not a valid date.");            
                return false;
        }
        if($_date2 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is not a valid date.");            
                return false;
        }
                
        return $_date1 < $_date2;
}
}




class gs_validate_isDateEqual {
function validate($field,$value,$data=array(),$params=array()) {

        if(strlen($value) == 0)
            return $empty;

        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
                return false;
        }
        
        $_date1 = strtotime($value);
        $_date2 = strtotime($data[$params['field2']]);
        
        if($_date1 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field' is not a valid date.");            
                return false;
        }
        if($_date2 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is not a valid date.");            
                return false;
        }
                
        return $_date1 == $_date2;
}
}




class gs_validate_isDateOnOrAfter {
function validate($field,$value,$data=array(),$params=array()) {

        if(strlen($value) == 0)
            return $empty;

        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
                return false;
        }
        
        $_date1 = strtotime($value);
        $_date2 = strtotime($data[$params['field2']]);
        
        if($_date1 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field' is not a valid date.");            
                return false;
        }
        if($_date2 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is not a valid date.");            
                return false;
        }
                
        return $_date1 >= $_date2;
}
}




class gs_validate_isDateOnOrBefore {
function validate($field,$value,$data=array(),$params=array()) {

        if(strlen($value) == 0)
            return $empty;

        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
                return false;
        }
        
        $_date1 = strtotime($value);
        $_date2 = strtotime($data[$params['field2']]);
        
        if($_date1 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field' is not a valid date.");            
                return false;
        }
        if($_date2 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is not a valid date.");            
                return false;
        }
                
        return $_date1 <= $_date2;
}
}




 

class gs_validate_isEmail {
function validate($field,$value,$data=array(),$params=array()) {

    if(strlen($value) == 0)
        return false;

    // regex taken from Jeffrey Freidl e-mail validation example
    // http://public.yahoo.com/~jfriedl/regex/email-opt.pl
    $_regex = '[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|"[^\\\x80-\xff\n\015"]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015"]*)*")[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:\.[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|"[^\\\x80-\xff\n\015"]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015"]*)*")[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*)*@[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:\.[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*)*|(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|"[^\\\x80-\xff\n\015"]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015"]*)*")[^()<>@,;:".\\\[\]\x80-\xff\000-\010\012-\037]*(?:(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)|"[^\\\x80-\xff\n\015"]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015"]*)*")[^()<>@,;:".\\\[\]\x80-\xff\000-\010\012-\037]*)*<[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:@[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:\.[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*)*(?:,[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*@[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:\.[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*)*)*:[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*)?(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|"[^\\\x80-\xff\n\015"]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015"]*)*")[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:\.[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|"[^\\\x80-\xff\n\015"]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015"]*)*")[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*)*@[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:\.[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*)*>)';    
    
    // in case value is several addresses separated by newlines
    $_addresses = preg_split('![\n\r]+!', $value);

    foreach($_addresses as $_address) {
		if(!preg_match("/^$_regex$/", $_address)) {
            return false;
        }
    }
    return true;
}
}




class gs_validate_isEmpty {
function validate($field,$value,$data=array(),$params=array()) {
    return strlen($value) == 0;
}
}


class gs_validate_notEqual {
function validate($field,$value,$data=array(),$params=array()) {
        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isEqual] parameter 'field2' is missing.");            
                return false;
        }
        if(strlen($value) == 0)
            return FALSE;

        return $value != $data[$params['field2']];
}
}


class gs_validate_isEqual {
function validate($field,$value,$data=array(),$params=array()) {
        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isEqual] parameter 'field2' is missing.");            
                return false;
        }
        if(strlen($value) == 0)
            return FALSE;

        return $value == $data[$params['field2']];
}
}









class gs_validate_isFloat {
function validate($field,$value,$data=array(),$params=array()) {
    if(strlen($value) == 0)
        return $empty;

    return preg_match('!^\d+\.\d+?$!', $value)==1;
}
}




class gs_validate_isInt {
function validate($field,$value,$data=array(),$params=array()) {
        if(strlen($value) == 0)
            return false;        
        
        return preg_match('!^\d+$!', $value)==1;
}
}




class gs_validate_isLength {
function validate($field,$value,$data=array(),$params=array()) {

        if(!isset($params['min'])) {
                trigger_error("SmartyValidate: [isLength] parameter 'min' is missing.");            
                return false;
        }
        if(!isset($params['max'])) {
                trigger_error("SmartyValidate: [isLength] parameter 'max' is missing.");            
                return false;
        }

        $_length = strlen($value);
                
        if($_length >=$params['min'] && $_length <=$params['max'])
            return true;
        elseif($_length == 0)
            return null;
        else
            return false;
}
}



class gs_validate_isChecked {
function validate($field,$value,$data=array(),$params=array()) {
    return 1 && $value;
}
}

class gs_validate_isNumber {
function validate($field,$value,$data=array(),$params=array()) {
    if(strlen($value) == 0)
        return isset($params['empty']) &&$params['empty'];        

    return preg_match('!^\d+(\.\d+)?$!', $value)==1;
}
}




class gs_validate_isOnly {
function validate($field,$value,$data=array(),$params=array()) {
	
        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isEqual] parameter 'field2' is missing.");            
                return false;
        }
        return (!$value>0 OR !$data[$params['field2']]>0);
}
}




class gs_validate_isPrice {
function validate($field,$value,$data=array(),$params=array()) {
    if(strlen($value) == 0)
        return $empty;

    return preg_match('/^\d+(\.\d{1,2})?$/', $value)==1;
}
}




class gs_validate_isRange {
function validate($field,$value,$data=array(),$params=array()) {
        if(!isset($params['low'])) {
                trigger_error("SmartyValidate: [isRange] parameter 'low' is missing.");            
                return false;
        }
        if(!isset($params['high'])) {
                trigger_error("SmartyValidate: [isRange] parameter 'high' is missing.");            
                return false;
        }
        if(strlen($value) == 0)
            return $empty;
        
        return ($value >=$params['low'] && $value <=$params['high']);
}
}




 

class gs_validate_isRegExp {
function validate($field,$value,$data=array(),$params=array()) {
        if(!isset($params['validate_regexp'])) {
                trigger_error("SmartyValidate: [isRegExp] parameter 'expression' is missing.");            
                return false;
        }
        if(strlen($value) == 0)
            return $empty;
        setlocale(LC_ALL, 'de_DE.ISO8859-1');
        $ret = (preg_match($params['validate_regexp'], $value));
        setlocale(LC_ALL, 'C');
	if (isset($params['validate_regexp_inverse'])) $ret=!$ret;
	return $ret;
}
}




class gs_validate_isURL {
function validate($field,$value,$data=array(),$params=array()) {
    if(strlen($value) == 0)
        return$params['empty'];        

    return preg_match('!^http(s)?://[\w-]+\.[\w-]+(\S+)?$!i', $value)==1;
}
}




class gs_validate_notEmpty {
function validate($field,$value,$data=array(),$params=array()) {
    return is_array($value) || strlen(trim($value)) > 0;
}
}

?>
