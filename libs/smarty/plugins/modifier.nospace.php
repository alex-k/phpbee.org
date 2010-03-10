<?php
function smarty_modifier_nospace($string)
{
    return preg_replace('/\s+/','&nbsp;',$string);
}

?>
