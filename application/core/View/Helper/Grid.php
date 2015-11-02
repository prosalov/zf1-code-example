<?php

/**
 * View helper for grid output
 */
class Core_View_Helper_Grid extends Zend_View_Helper_HtmlElement
{

    protected $_options = array(
        'emptyGridMessage' => 'grid_no_data_message',
        'wrapperHtmlAttribs'   => array(
            'class' => 'grid'
        ),
        'tableHtmlAttribs'   => array(
            'class' => 'grid-table'
        ),
        'rowHtmlAttribs'    => array(
            'class'     => 'grid-row'
        ),
        'rowEvenHtmlAttribs'    => array(
            'class'     => 'grid-row even'
        ),
        'cellHtmlAttribs'   => array(
        )
    );
    /**
     *
     * @var Ext_Grid
     */
    protected $_grid = null;

    /**
     *
     * @return Ext_Grid
     */
    public function getGrid()
    {
        if (!$this->_grid) {
            throw new Ext_View_Exception('Ext_View_Helper_Grid');
        }
        return $this->_grid;
    }

    /**
     *
     * @return Ext_View_Helper_Grid
     */
    public function setGrid(Ext_Grid $grid)
    {
        $this->_grid = $grid;
        return $this;
    }

    /**
     *
     * @param Ext_Grid $grid
     * @return string
     */
    public function grid(Ext_Grid $grid, $options = array())
    {
        $this->_grid = $grid;

        $this->_options = array_merge($this->_options, $options);

        // Default grid templates
        $this->view->addBasePath(dirname(__FILE__) . '/files');

        $result = '';

        $result = $this->_renderHeader();;
        if ($this->getGrid()->isFiltersEnabled()) {
            $result .= $this->_renderFilters();
        }
        $result .= $this->_renderBody();


        $table = $this->_table($result);

        return $table;
    }

    /**
     * Render grid header
     *
     * @return string
     */
    protected function _renderHeader()
    {
        $headerCells = '';

        if ($this->getGrid()->isMassActionEnabled()) {
            $headerCells .= $this->_renderHeaderCell(array(
                'header' => ''
            ));
        }

        foreach ($this->getGrid()->getColumns() as $column) {
            $headerCells .= $this->_renderHeaderCell($column);
        }

        return $this->_row($headerCells);
    }

    /**
     * Render grig body
     *
     * @return string
     */
    protected function _renderBody()
    {
        $result = '';

        if ($this->getGrid()->getPaginator()->count()) {
            foreach ($this->getGrid()->getPaginator() as $key => $entity) {
                $cells = '';

                if (!is_object($entity) && !is_array($entity)) {
                    throw new Zend_View_Exception('Data row expected to be an object or an array');
                }

                if ($this->getGrid()->isMassActionEnabled()) {
                    $column = array('field' => $this->getGrid()->getMassActionsId());
                    $id = $this->_getFieldValue($column, $entity);

                    $cells .= $this->_cell($this->_checkBox($id));
                }

                foreach ($this->getGrid()->getColumns() as $column) {
                    $cells .= $this->_renderBodyCell($column, $entity);
                }

                $result .= $this->_row($cells, ($key % 2));
            }
        } else {
            $result .= $this->_emptyRow();
        }

        return $result;
    }

    /**
     * Render filters
     *
     * @return string
     */
    protected function _renderFilters()
    {
        $filterCells = '';

        if ($this->getGrid()->isMassActionEnabled()) {
            $filterCells .= $this->_cell('');
        }

        foreach ($this->getGrid()->getColumns() as $column) {
            $filterCells .= $this->_renderFilterCell($column);
        }

        return $this->_row($filterCells);
    }

    protected function _renderHeaderCell($column)
    {
        $dir        = null;
        $arrow      = null;
        $sortField  = null;
        $sortable   = false;

        if (isset($column['sortable']) && $column['sortable']) {
            $sortField = $column['field'];
            if (isset($column['relation_alias']) && !empty($column['relation_alias'])) {
                $sortField = $column['relation_alias'] . '.' . end(explode('.', $sortField));
            }

            $sortable = true;

            $sortColumn = $this->getGrid()->getSortColumn();

            if ($sortColumn && ($sortField == $sortColumn)) {
                if ($this->getGrid()->getSortDirection() == 'asc') {
                    $dir   = 'desc';
                    $arrow = 'asc';
                } else {
                    $dir   = 'asc';
                    $arrow = 'desc';
                }
            } else {
                $dir   = 'asc';
                $arrow = null;
            }
        }

        $partial = $this->getGrid()->getPartials();

        $attribs = array();
        if (isset($column['htmlAttribs'])) {
            $attribs = $column['htmlAttribs'];
        }
        if (!isset($attribs['class'])) {
            $attribs['class'] = '';
        }
        if ($column['header']) {
            $attribs['class'] = ('th_' . $column['header']) . ' ' . $attribs['class'];
        }

        return $this->view->partial($partial['header'], array(
            'value'       => $this->view->translate($column['header']),
            'sortable'    => $sortable,
            'sort'        => $sortField,
            'dir'         => $dir,
            'arrow'       => $arrow,
            'header'      => $column['header'],
            'htmlAttribs' => $this->_htmlAttribs($attribs)
        ));
    }

    protected function _renderFilterCell($column)
    {
        $value = '';

        $partials = $this->getGrid()->getPartials();

        if (isset($column['filter'])) {
            if ($column['filter'] == 'date_range') {
                $column['filter'] = 'range';
                $this->view
                    ->jQuery()
                    ->addOnLoad("$('#filter_{$column['field']}_from, #filter_{$column['field']}_to').datepicker({ dateFormat: 'yy-mm-dd' });")
                ;
            }
            $data = array(
                'name'  => $column['field'],
                'type'  => $column['filter'],
                'value' => $this->getGrid()->getFilterValue($column['field'])
            );

            if ('select' == $column['filter']) {

                if (!isset ($column['filter_options'])) {
                    $column['filter_options'] = array();
                }

                $data['filter_options'] = $column['filter_options'];
            }

            $value = $this->view->partial($partials['filter'], $data);
        }

        $attribs = array();
        if (isset($column['htmlAttribs'])) {
            $attribs = $column['htmlAttribs'];
        }

        return $this->_cell($value, $attribs);
    }

    protected function _renderBodyCell($column, $data)
    {
        $value = '';

        if (isset($column['actions'])) {

            $value = $this->_renderActions($column['actions'], $data);

        } elseif (isset($column['callback'])) {

            $callback = "_{$column['callback']}Callback";
            $value = $this->getGrid()->$callback($data);

        } else {

            $value = $this->_getFieldValue($column, $data);
        }

        if (!empty($column['helpers'])) {
            $value = $this->_applyHelpers($column['helpers'], $value);
        }

        $attribs = array();
        if (isset($column['htmlAttribs'])) {
            $attribs = $column['htmlAttribs'];
        }

        return $this->_cell($value, $attribs);
    }

    protected function _cell($value, $attribs = array())
    {
        $partial = $this->getGrid()->getPartials();

        return $this->view->partial($partial['cell'], array(
            'value'         => $value,
            'htmlAttribs'   => $this->_htmlAttribs(array_merge($this->_options['cellHtmlAttribs'], $attribs))
        ));
    }

    protected function _row($cells, $evenRow = false)
    {
        $partial = $this->getGrid()->getPartials();

        $htmlAttribs = $this->_options['rowHtmlAttribs'];

        if (isset($this->_options['rowEvenHtmlAttribs']) && $evenRow) {
            $htmlAttribs = array_merge($htmlAttribs, $this->_options['rowEvenHtmlAttribs']);
        }

        return $this->view->partial($partial['row'], array(
            'cells'        => $cells,
            'htmlAttribs'  => $this->_htmlAttribs($htmlAttribs)
        ));
    }

    protected function _emptyRow()
    {
        $partial = $this->getGrid()->getPartials();

        $cellCount = count($this->getGrid()->getColumns());

        if ($this->getGrid()->isMassActionEnabled()) {
            $cellCount ++;
        }

        return $this->view->partial($partial['row'], array(
            'cells' => $this->view->partial($partial['cell'], array(
                'value'         => $this->view->translate(
                    $this->_options['emptyGridMessage']
                ),
                'htmlAttribs'   => $this->_htmlAttribs(array(
                    'colspan'   => $cellCount,
                    'class'     => 'no-data'
                ))
            ))
        ));
    }

    protected function _renderMassActions()
    {
        $partial = $this->getGrid()->getPartials();

        return $this->view->partial($partial['actions'], array(
            'massActions' => $this->getGrid()->getMassActions()
        ));
    }

    protected function _table($rows)
    {
        $partial = $this->getGrid()->getPartials();

        $massActions = '';

        if ($this->getGrid()->isMassActionEnabled()) {
            $massActions = $this->_renderMassActions();
        }

        return $this->view->partial($partial['table'], array(
            'rows'                  => $rows,
            'massActions'           => $massActions,
            'paginator'             => $this->getGrid()->getPaginator(),
            'paginationEnabled'     => $this->getGrid()->isPaginationEnabled(),
            'filtersEnabled'        => $this->getGrid()->isFiltersEnabled(),
            'massActionEnabled'     => $this->getGrid()->isMassActionEnabled(),
            'perPageOptions'        => $this->getGrid()->getPerPageOptions(),
            'htmlAttribs'           => $this->_htmlAttribs($this->_options['tableHtmlAttribs']),
            'wrapperHtmlAttribs'    => $this->_htmlAttribs($this->_options['wrapperHtmlAttribs'])
        ));
    }

    protected function _checkBox($value)
    {
        $partial = $this->getGrid()->getPartials();

        return $this->view->partial($partial['checkBox'], array(
            'value' => $value
        ));
    }

    protected function _getFieldValue($column, $entity)
    {
        $field = $column['field'];
        if (is_object($entity)) {
            $fieldAsArray = explode('.', $field);
            while(count($fieldAsArray) > 1) {
                $subField = array_shift($fieldAsArray);
                if ($entity->relatedExists($subField)) {
                    $entity = $entity->$subField;
                } else {
                    return '';
                }
            }
            $field = $fieldAsArray[0];
            return $entity->$field;
        }
        return isset($entity[$field]) ? $entity[$field] : '';
    }

    /**
     * Apply view helpers for grid's cell content
     *
     * @param array|string $column
     * @param mixed $value
     * @return string
     */
    protected function _applyHelpers($column, $value)
    {
        if (!is_array($column)) {
            $column = array($column);
        }
        foreach ($column as $helper => $options) {
            if (is_numeric($helper)) {
                $helper = $options;
                $options = array('%value%');
            }

            $key = array_search('%value%', $options);

            $options[$key] = $value;

            $value = call_user_func_array(array($this->view, $helper), $options);
        }
        return $value;
    }

    /**
     * Render action links
     *
     * @param array $actions
     * @param array $data
     * @return string
     */
    protected function _renderActions(array $actions, $data)
    {
        $request = $this->getGrid()->getRequest();

        $routeParams = array(
            $request->getModuleKey(),
            $request->getControllerKey(),
            $request->getActionKey()
        );

        $results = array();
        foreach ($actions as $action) {
            if (isset($action['callback'])) {
                $callback = $action['callback'];
                if (! is_callable($callback)) {
                    $callback = array($this->getGrid(), "_{$action['callback']}Callback");
                }

                $action = call_user_func($callback, $data);
                if (! empty($action)) {
                    $results[] = $action;
                }

                continue;
            }

            $attribs = array();
            if (isset($action['attribs'])) {
                $attribs = $action['attribs'];
            }

            if (!isset($attribs['class']) && isset($action['url']['action'])) {
                if (in_array($action['url']['action'], array('delete', 'remove'))) {
                    $attribs['class'] = 'delete';
                }
            }

            $this->_htmlAttribs($attribs);

            if (!isset($action['route'])) {
                $action['route'] = 'default';
            }

            if (!isset($action['reset'])) {
                $action['reset'] = true;
            }

            foreach ($action['url'] as &$value) {
                if (isset($data[$value]) && !in_array($value, $routeParams)) {
                    $value = $data[$value];
                }
            }

            $results[] = '<a ' . $this->_htmlAttribs($attribs) . ' href="'
                . $this->view->url($action['url'], $action['route'], $action['reset']) . '">'
                . $this->view->translate($action['label']) . '</a>';
        }

        return implode($this->getGrid()->getActionsDelimiter(), $results);
    }
}