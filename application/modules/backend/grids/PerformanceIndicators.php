<?php

class Backend_Grid_PerformanceIndicators extends Core_Grid
{
    protected $_gridName = 'performance-indicators';

    protected $_filtersEnabled = true;

    protected $_columns = array(
        'id' => array(
            'header'   => 'id',
            'sortable' => true,
        ),
        'name' => array(
            'header'   => 'name',
            'helpers'  => array('escape'),
            'sortable' => true
        ),
        'Program.name' => array(
            'header'          => 'program',
            'field'           => 'Program.name',
            'relation_alias'  => 'p',
            'filter'          => 'select',
            'filter_callback' => 'setProgram',
            'helpers'         => array('escape'),
            'sortable'        => true
        ),
        'actions' => array(
            'header'  => 'Actions',
            'actions' => array(
                array(
                    'label' => 'edit',
                    'url'   => array(
                        'module'     => 'backend',
                        'controller' => 'performance-indicators',
                        'action'     => 'edit',
                        'id'         => 'id'
                    )
                ),
                array(
                    'label' => 'delete',
                    'url'   => array(
                        'module'     => 'backend',
                        'controller' => 'performance-indicators',
                        'action'     => 'delete',
                        'id'         => 'id'
                    )
                ),
            )
        )
    );

    public function _setProgramFilterCallback(Doctrine_Query $query, $value)
    {
        if (! empty($value)) {
            $query->andWhere('pI.program_id = ?', $value);
        }

        return $query;
    }
    
    public function init()
    {
        $this->_columns['Program.name']['filter_options'] = array('' => '')
            + Model_ProgramTable::getInstance()->getList()->toKeyValueArray('id', 'name');

        $query = Model_PerformanceIndicatorTable::getInstance()
            ->createQuery('pI')
            ->leftJoin('pI.Program as p');

        $this->setAdapter(new Ext_Grid_Adapter_DoctrineQuery($query));
        parent::init();
    }
}
