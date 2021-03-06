<?php

use Phalcon\Events\Event,
	Phalcon\Mvc\User\Plugin,
	Phalcon\Mvc\Dispatcher,
	Phalcon\Acl;

/**
 * Security
 *
 * This is the security plugin which controls that users only have access to the modules they're assigned to
 */
class Security extends Plugin
{

	public function __construct($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;
	}

	public function getAcl()
	{
		if (!isset($this->persistent->acl)) {

			$acl = new Phalcon\Acl\Adapter\Memory();

			$acl->setDefaultAction(Phalcon\Acl::DENY);

			//Register roles
			$roles = array(
				'users' => new Phalcon\Acl\Role('Users'),
				'etsyusers' => new Phalcon\Acl\Role('EtsyUsers'),
				'guests' => new Phalcon\Acl\Role('Guests')
			);
			foreach ($roles as $role) {
				$acl->addRole($role);
			}

			//Private area resources
			$privateResources = array(
				'watchlists' => array('index', 'search', 'new', 'edit', 'save', 'create', 'delete'),
				'parameters' => array('index', 'search', 'new', 'edit', 'save', 'create', 'delete'),
				'watchlistsparameters' => array('index', 'search', 'new', 'edit', 'save', 'create', 'delete'),
			);
			foreach ($privateResources as $resource => $actions) {
				$acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
			}

			//Etsy user area resources
			$etsyUserResources = array(
				'mywatchlists' => array('index', 'watchlist', 'search', 'setlistingsasviewed', 'categories', 'save'),
			);
			foreach ($etsyUserResources as $resource => $actions) {
				$acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
			}

			//Public area resources
			$publicResources = array(
				'index' => array('index'),
				'oauth' => array('index', 'access'),
				'login' => array('index', 'start', 'end'),
				'cronjob' => array('index', 'email'),
			);
			foreach ($publicResources as $resource => $actions) {
				$acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
			}

			//Grant access to public areas to both users and guests
			foreach ($roles as $role) {
				foreach ($publicResources as $resource => $actions) {
					$acl->allow($role->getName(), $resource, '*');
				}
			}

			//Grant acess to private area to role EtsyUsers
			foreach ($etsyUserResources as $resource => $actions) {
				foreach ($actions as $action){
					$acl->allow('EtsyUsers', $resource, $action);
				}
			}

			//Grant acess to private area to role Users
			foreach ($privateResources as $resource => $actions) {
				foreach ($actions as $action){
					$acl->allow('Users', $resource, $action);
				}
			}

			//The acl is stored in session, APC would be useful here too
			$this->persistent->acl = $acl;
		}

		return $this->persistent->acl;
	}

	/**
	 * This action is executed before execute any action in the application
	 */
	public function beforeDispatch(Event $event, Dispatcher $dispatcher)
	{

		$auth = $this->session->get('auth');

		if ($auth && isset($auth['id'])) {
			$role = 'Users';
		} else if ($auth && isset($auth['etsyuser_id'])) {
			$role = 'EtsyUsers';
		} else {
			$role = 'Guests';
		}

		$controller = $dispatcher->getControllerName();
		$action = $dispatcher->getActionName();

		$acl = $this->getAcl();

		$allowed = $acl->isAllowed($role, $controller, $action);

		if ($allowed != Acl::ALLOW) {
			$dispatcher->forward(
				array(
					'controller' => 'index',
					'action' => 'denied'
				)
			);
			return false;
		}

	}

}
