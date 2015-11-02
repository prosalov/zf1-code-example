<?php

class Backend_View_Helper_ContentHeader extends Zend_View_Helper_Abstract
{
    public function contentHeader($title, $buttons = array())
    {
        return $this->view->partial('_partials/content-header.phtml', array(
            'title'     => $title,
            'buttons'   => $buttons
        ));
    }
}