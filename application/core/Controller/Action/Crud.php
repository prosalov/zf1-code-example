<?php

abstract class Core_Controller_Action_Crud extends Zend_Controller_Action
{
    /**
     * Class name of doctrine entity
     *
     * @var string
     */
    protected $_entityName;

    /**
     * Class name of grid to show entities list
     *
     * @var string
     */
    protected $_gridName;
    
    /**
     * Class name of add/update form 
     *
     * @var string
     */
    protected $_formName;

    /**
     * Messages
     *
     * @var array
     */
    protected $_messages = array(
        'entityAdded'   => 'Entity has been added',
        'entityUpdated' => 'Entity has been updated',
        'entityDeleted' => 'Entity has been deleted'
    );

    public function indexAction()
    {
        $this->_executeMassActions();
        
        $this->view->grid = $this->_getGrid();
    }

    public function addAction()
    {
        $this->_form($this->_createEntity());
    }

    public function editAction()
    {
        $entity = $this->_findEntity();

        $this->_form($entity);
    }

    public function deleteAction()
    {
        $entity = $this->_findEntity();

        $entity->delete();

        $this->_helper->messenger->success($this->_messages['entityDeleted']);

        $this->_redirectToIndex();
    }

    protected function _executeMassActions()
    {
        if ($this->getRequest()->isPost() && $this->_getParam('mass_action')) {

            $methodName = '_mass' . ucfirst($this->_getParam('mass_action'));

            if (method_exists($this, $methodName)) {
                $this->$methodName();
            }
        }
    }

    protected function _form(Doctrine_Record $entity)
    {
        $form = $this->_getForm();
        if ($entity->exists()) {
            $form->setDefaults($entity->toArray());
        }

        if ($this->getRequest()->getParam('cancel')) {
            $this->_redirectToIndex();
        }
        if ($this->getRequest()->isPost()
            && $form->isValid($this->getRequest()->getParams())
        ) {
            $messageKey = 'entityAdded';
            if ($entity->exists()) {
                $messageKey = 'entityUpdated';
            }

            $this->_saveEntity($entity, $form);
        
            $this->_helper->messenger->success($this->_messages[$messageKey]);

            $this->_redirectToIndex();
        }

        $this->view->form = $form;
    }

    /**
     * @param string $entityName
     * @return Ext_Controller_Action_Crud
     */
    protected function _setEntityName($entityName)
    {
        $this->_entityName = $entityName;

        return $this;
    }

    /**
     * @return string
     */
    protected function _getEntityName()
    {
        return $this->_entityName;
    }

    /**
     * @param string $formName
     * @return Ext_Controller_Action_Crud
     */
    protected function _setFormName($formName)
    {
        $this->_formName = $formName;

        return $this;
    }

    /**
     * @return string
     */
    protected function _getFormName()
    {
        return $this->_formName;
    }

    /**
     * @param string $gridName
     * @return Ext_Controller_Action_Crud
     */
    protected function _setGridName($gridName)
    {
        $this->_gridName = $gridName;

        return $this;
    }

    /**
     * @return string
     */
    protected function getGridName()
    {
        return $this->_gridName;
    }

    /**
     * Returns grid instance
     *
     * @return Ext_Grid
     */
    protected function _getGrid()
    {
        $gridName = $this->getGridName();
        return new $gridName();
    }

    /**
     * Returns form instance
     *
     * @return Zend_Form
     */
    protected function _getForm()
    {
        $formName = $this->_getFormName();
        return new $formName();
    }

    /**
     * Find entity by primary key
     *
     * @param string $primaryKey
     * 
     * @throws Zend_Controller_Action_Exception
     * @return Doctrine_Record
     */
    protected function _findEntity($primaryKey = null)
    {
        if ($primaryKey === null) {
            $primaryKey = $this->_getParam('id');
        }

        $entity = Doctrine::getTable($this->_getEntityName())->find($primaryKey);

        if (!$entity) {
            $this->_helper->error->notFound();
        }

        return $entity;
    }

    /**
     * Returns new entity instance
     *
     * @return Doctrine_Record
     */
    protected function _createEntity()
    {
        $entityName = $this->_getEntityName();
        return new $entityName();
    }

    /**
     * Method to save entity. May be refined in child controller
     *
     * @param Doctrine_Record $entity
     * @param Zend_Form $form
     * @return void
     */
    protected function _saveEntity(Doctrine_Record $entity, Zend_Form $form)
    {
        $entity->merge($form->getValues());

        $entity->save();
    }
    
    protected function _redirectToIndex()
    {
        $this->_helper->redirector->gotoSimple(
            'index',
            $this->getRequest()->getControllerName(),
            $this->getRequest()->getModuleName()
        );
    }
}
