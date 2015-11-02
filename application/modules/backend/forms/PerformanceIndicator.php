<?php

class Backend_Form_PerformanceIndicator extends Core_Form
{
    public function init()
    {
        $this->setAttrib('id', 'form-performance-indicator');

        $this->addElement('hidden', 'id', array(
            'decorators'   => array('ViewHelper')
        ));

        $this->addElement('DbSelect', 'program_id', array(
            'label'        => 'program',
            'defaultName'  => '-- please select program',
            'defaultValue' => '',
            'query'        => Doctrine_Query::create()->from('Model_Program')->orderBy('name ASC'),
            'valueColumn'  => 'id',
            'nameColumn'   => 'name',
            'required'     => true,
        ));

        $this->addElement('text', 'name', array(
            'label'        => 'name',
            'required'     => true,
            'filters'      => array('StringTrim'),
            'validators'   => array(
                array('callback', true, array(
                    'callback' => array($this, 'isValidName'),
                    'messages' => array(
                        Zend_Validate_Callback::INVALID_VALUE => $this->getTranslator()->translate('name_should_be_unique'),
                    ),
                )),
            ),
        ));

        $this->addElement('submit', 'save', array(
            'label'        => 'save',
            'class'        => 'ui-state-default ui-corner-all'
        ));

        $this->addElement('submit', 'cancel', array(
            'label'        => 'cancel',
            'class'        => 'ui-state-default ui-corner-all'
        ));

        $this->addDisplayGroupButtons(array('save', 'cancel'));
    }

    public function isValidName($value, $context)
    {
        $performanceIndicatorExists = Model_PerformanceIndicatorTable::getInstance()->isExists($context['program_id'], $value);

        return (! $performanceIndicatorExists);
    }
}
