<?php

/**
 * View helper for building <a />
 */
class Ext_View_Helper_Anchor extends Zend_View_Helper_HtmlElement
{
    public function anchor($href, $label = null, $title = null, $attribs = array())
    {
        $tag = '<a';

        $escape = true;
        if (isset($attribs['escape'])) {
            $escape = $attribs['escape'];
            unset($attribs['escape']);
        }
        
        $tag .= $this->_htmlAttribs(
            $attributes = $this->_assembleAttributes($href, $title, $attribs)
        );

        if (is_null($label)) {
            $label = $attributes['href'];
        }

        if ($escape) {
            $label = $this->view->escape($label);
        }
        
        return $tag .= '>' . $label . '</a>';
    }

    /**
     * @param  string | array $src
     * @param  $title
     * @param  $alt
     * @param array $otherAttribs
     * @return array
     */
    protected function _assembleAttributes($src, $title, array $otherAttribs)
    {
        $src = $this->_getUrl($src);

        $otherAttribs['href'] = $src;

        if ($title) {
            $otherAttribs['title'] = $title;
        }

        return $otherAttribs;
    }

    protected function _getUrl($src, $router = null)
    {
        if (!is_array($src)) {
            return $src;
        }

        if (!isset ($src['urlOptions'])) {
            $src['urlOptions'] = $src;
        }

        $urlOptions = $src['urlOptions'];

        $name   = isset($src['route'])  ? $src['route']  : 'default';
        $reset  = isset($src['reset'])  ? $src['reset']  : false;
        $encode = isset($src['encode']) ? $src['encode'] : true;

        return $this->view->url($urlOptions, $name, $reset, $encode);
    }
}