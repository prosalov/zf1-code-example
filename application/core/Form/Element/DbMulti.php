<?php

/**
 * Base element for all form elements using entries from database
 *
 * @example
 * Example usage:
 * <code>
 * $categories = new Ext_Form_Element_DbSelect('category_id',
 *       array(
 *           'label'    => 'Category',
 *           'query'    => Doctrine_Query::create()->from('Category'),
 *           //'defaultValue' => false  // or
 *           'defaultValue' => '1',
 *           'defaultName' => '-- select category'
 *           'valueColumn' => 'id'
 *           'nameColumn' => 'name'
 *           )
 *       );
 * </code>
 */
class Core_Form_Element_DbMulti extends Zend_Form_Element_Multi
{
    /**
     *
     * @var Doctrine_Query
     */
    protected $_query;

    /**
     *
     * @var string
     */
    protected $_valueColumn = 'name';

    /**
     *
     * @var string Column name displayed on the list
     */
    protected $_nameColumn = 'name';

    /**
     * Default element inserted at the beginning of the array
     * with attribute selected
     *
     * @var string
     */
    protected $_defaultName = '-- please select';

    /**
     * Value of default element
     *
     * @var string
     */
    protected $_defaultValue = '';
    
    /**
     * Seporator for option names 
     * 
     * @var string
     */
    protected $_nameSeparator = ' ';

    /**
     * Default name
     * @see     $_defaultName
     * @param   string $name
     */
    public function setDefaultName($name)
    {
        $this->_defaultName = $name;
    }

    /**
     *
     * @return string|null
     */
    public function getDefaultName()
    {
        return $this->_defaultName;
    }

    /**
     *
     * @return string|null
     */
    public function getDefaultValue()
    {
        return $this->_defaultValue;
    }

    /**
     * @see     $_defaultValue
     * @param   string $value
     */
    public function setDefaultValue($value)
    {
        $this->_defaultValue = $value;
    }

    /**
     *
     * @param Doctrine_Query $query
     */
    public function setQuery(Doctrine_Query $query)
    {
        $this->_query = $query;
    }

    /**
     *
     * @return Doctrine_Query
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     *
     * @param string $name
     */
    public function setValueColumn($name)
    {
        $this->_valueColumn = $name;
    }

    /**
     *
     * @return string
     */
    public function getValueColumn()
    {
        return $this->_valueColumn;
    }

    /**
     *
     * @param string $name
     */
    public function setNameColumn($name)
    {
        if (empty($name)) {
            throw new Ext_Exception('Name column name is an empty string');
        }
        $this->_nameColumn = $name;
    }

    /**
     *
     * @return string
     */
    public function getNameColumn()
    {
        return $this->_nameColumn;
    }
    
    /**
     *
     * @param string $nameSeparator
     */
    public function setNameSeparator($nameSeparator)
    {
        $this->_nameSeparator = $nameSeparator;
    }

    /**
     *
     * @return string
     */
    public function getNameSeparator()
    {
        return $this->_nameSeparator;
    }    

    /**
     * Get values from query results by given name. If $nameColumn is an array
     * join value with specified separator.
     *
     * @param Doctrine_Collection $data
     * @param string|array $nameColumn
     * @param string $separator
     * @return string
     */
    public function getNameValue($data, $nameColumn, $separator = '')
    {
        if (is_array($nameColumn)) {
            $values = array();

            foreach ($nameColumn as $n) {
                $values[] = $data[$n];
            }

            if (is_array($separator)) {
                return vsprintf(implode('%s', $values), array_pad($separator, (count($values) - 1), ' '));
            } else {
                return implode($separator, $values);
            }
        }

        return $data[$nameColumn];
    }

    /**
     * Verifies the existence name column in the query results
     *
     * @param string $name
     * @param Doctrine_Collection $data
     * @return bool
     */
    public function hasColumn($name, $data)
    {
        if (is_array($name)) {
            foreach ($name as $n) {
                $this->hasColumn($n, $data);
            }

            return true;
        }

        if (!array_key_exists($name, $data)) {
            throw new Ext_Exception('Name column "' . $name . '" is not present in the query results');
        }

        return true;
    }

    /**
     * Generate list of options from the query
     *
     * @return array
     */
    public function getOptionsFromQuery()
    {
        $query = $this->getQuery();

        if (null === $query) {
            throw new Ext_Exception('Query not set yet. Use setQuery()');
        }

        $results = $query->execute();

        $nameColumn     = $this->getNameColumn();
        $valueColumn    = $this->getValueColumn();

        $options = array();

        foreach ($results as $result) {

            $result = $result->toArray();
            
            $this->hasColumn($nameColumn, $result);
            $this->hasColumn($valueColumn, $result);

            $options[$result[$valueColumn]] = $this->getNameValue($result, $nameColumn, $this->getNameSeparator());
        }

        return $options;
    }

    /**
     * Read and add options from database
     *
     * @return void
     */
    public function init()
    {
        $defaultValue   = $this->getDefaultValue();
        $defaultName    = $this->getDefaultName();

        $options = array();
        
        if ((null !== $defaultValue && false !== $defaultValue) &&
                (null !== $defaultValue && false !== $defaultName))
        {
            $options[$defaultValue] = $defaultName;

            $this->setValue($defaultValue);
        }

        $options = $options + $this->getOptionsFromQuery();

        $this->setMultiOptions($options);
    }
}
