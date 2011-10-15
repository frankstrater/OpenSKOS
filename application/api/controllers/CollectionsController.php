<?php

class Api_CollectionsController extends OpenSKOS_Rest_Controller
{
	public function init()
	{
		parent::init();
		$this->_helper->contextSwitch()
			->initContext($this->getRequest()->getParam('format', 'rdf'));
		
		if('html' == $this->_helper->contextSwitch()->getCurrentContext()) {
			//enable layout:
			$this->getHelper('layout')->enableLayout();
		}
	}
	
	public function indexAction()
	{
		$model = new OpenSKOS_Db_Table_Collections();
		$context = $this->_helper->contextSwitch()->getCurrentContext();
		if ($context == 'json') {
			$this->view->assign('collections', $model->fetchAll()->toArray());
		} else {
			$this->view->collections = $model->fetchAll();
		}
	}
	
	public function getAction()
	{
		$modelTenant = new OpenSKOS_Db_Table_Tenants();
		$id = $this->getRequest()->getParam('id');
		list($tenantCode, $collectionCode) = explode(':', $id);
		$tenant = $modelTenant->find($tenantCode)->current();
		if (null===$tenant) {
			throw new Zend_Controller_Action_Exception('Insitution `'.$tenantCode.'` not found', 404);
		}
		
		$modelCollections = new OpenSKOS_Db_Table_Collections();
		$collection = $tenant->findDependentRowset(
			'OpenSKOS_Db_Table_Collections', null, 
			$modelCollections->select()->where('code=?', $collectionCode)
		)->current();
		if (null===$collection) {
			throw new Zend_Controller_Action_Exception('Collection `'.$id.'` not found', 404);
		}
		
		$context = $this->_helper->contextSwitch()->getCurrentContext();
		if ($context == 'json') {
			foreach ($collection as $key => $val) {
				$this->view->assign($key, $val);
			}
		} else {
			$this->view->assign('tenant', $tenant);
			$this->view->assign('collection', $collection);
		}
	}
	
	public function postAction()
	{
		$this->_501('post');
	}
	
	public function putAction()
	{
		$this->_501('put');
	}
	
	public function deleteAction()
	{
		$this->_501('delete');
	}
	
}