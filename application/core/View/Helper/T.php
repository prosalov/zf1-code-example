<?php

/**
 * Short proxy for translate helper
 */
class Ext_View_Helper_T extends Zend_View_Helper_Translate
{
    public function t($messageId)
    {
        $args = func_get_args();
        if (isset($args[1]) && is_string($args[1]) && strlen($args[1]) == 2) {
            $args[1] .= ' '; //this is hack because translate doesn't work with short strings
        }
        return call_user_func_array(array($this, 'translate'), $args);
    }

}