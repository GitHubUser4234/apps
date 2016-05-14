<?php
namespace OCA\test_user_ldap\lib;

use OCP\ILogger;
use OCP\LDAP\IDeletionFlagSupport;

/**
 * Hooks to create and delete a user in LDAP, and to set the password of a user in LDAP.
 *
 * @category Apps
 * @package  TestUserLDAP
 * @author   Tester
 */
class Hooks {
    
	private $ldap;
	private $logger;
	private $dbBackend = null;
	
	public function connectHooks() {
		\OCP\Util::connectHook('OC_User', 'pre_setPassword', $this, 'pre_setPasswordHook');
		\OCP\Util::connectHook('OC_User', 'post_setPassword', $this, 'post_setPasswordHook');
		\OCP\Util::connectHook('OC_User', 'pre_createUser', $this, 'pre_createUserHook');
		\OCP\Util::connectHook('OC_User', 'post_createUser', $this, 'post_createUserHook');
		\OCP\Util::connectHook('OC_User', 'pre_deleteUser', $this, 'pre_deleteUserHook');
		\OCP\Util::connectHook('OC_User', 'post_deleteUser', $this, 'post_deleteUserHook');
	}
	
	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		$this->logger = \OC::$server->getLogger();
		$this->ldap = new LDAP($this->logger);
		$this->connectHooks();
	}

	/**
	 * Remove DB backend before setting user password in LDAP
	 *
	 * @param array $params
	 */
	public function pre_setPasswordHook($params) {
		/*$array = array('status' => "Failure", 'data' => array('message'=>"testesssas sas"));
		$json = json_encode($array);
		header('Content-Type: application/json');
		echo $json;
		die();*/
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			$this->logger->error('Session user not found in pre_setPasswordHook.', ['app' => 'test_user_ldap']);
			die();
		}

		$this->removeDBBackend();
	}
	
	/**
	 * Readd DB backend after setting a user password in LDAP
	 *
	 * @param array $params
	 */
	public function post_setPasswordHook($params) {
		/*$array = array('status' => "Failure", 'data' => array('message'=>"testesssas sas"));
		$json = json_encode($array);
		header('Content-Type: application/json');
		echo $json;
		die();*/
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			$this->logger->error('Session user not found in post_setPasswordHook.', ['app' => 'test_user_ldap']);
			die();
		}

		$this->readdDBBackend();
	}
	
	/**
	 * Remove DB backend before creating a user in LDAP
	 *
	 * @param array $params
	 */
	public function pre_createUserHook($params) {
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			$this->logger->error('Session user not found in pre_createUserHook.', ['app' => 'test_user_ldap']);
			die();
		}
		
		$this->removeDBBackend();
	}
	
	/**
	 * Readd DB backend after creating a user in LDAP
	 *
	 * @param array $params
	 */
	public function post_createUserHook($params) {
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			$this->logger->error('Session user not found in post_createUserHook.', ['app' => 'test_user_ldap']);
			die();
		}
		
		$this->readdDBBackend();
	}
	
	/**
	 * Delete a user in LDAP
	 *
	 * @param array $params
	 */
	public function pre_deleteUserHook($params) {
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			$this->logger->error('Session user not found in pre_deleteUserHook.', ['app' => 'test_user_ldap']);
			die();
		}

		$cr = null;
		$exception = null;
		try {
			$ldapProvider = \OC::$server->getLDAPProvider();
			$cr = $ldapProvider->getLDAPConnection($sessionUser->getUID());
			if(!$this->ldap->isResource($cr)) {
				//LDAP not available
				throw new \Exception('LDAP resource not available.');
			}
			$uid = $params['uid'];
			$userDN = $ldapProvider->getUserDN($uid);
			$this->logger->debug('deleteUser user DN: '.$userDN, ['app' => 'test_user_ldap']);
			$this->ldap->deleteUser($cr, $userDN);
			if($ldapProvider instanceof IDeletionFlagSupport) {
				$ldapProvider->flagRecord($uid);
				$this->logger->debug('deleteUser flagRecord: '.$uid, ['app' => 'test_user_ldap']);
			}
		} catch (\Exception $e) {
			$exception = $e;
			$this->logger->logException($e);
		}
		if(!is_null($cr)) {
			try {$this->ldap->unbind($cr);}catch (\Exception $e) {/*ignored*/}
		}
		if(!is_null($exception)) {
			die();
		}
		
		//$this->removeDBBackend();
	}
	
	/**
	 * Clear cache after deleting a user in LDAP
	 *
	 * @param array $params
	 */
	public function post_deleteUserHook($params) {
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			$this->logger->error('Session user not found in post_deleteUserHook.', ['app' => 'test_user_ldap']);
			die();
		}
		//clear cache
		\OC::$server->getLDAPProvider()->clearCache($sessionUser->getUID());
		
		//$this->readdDBBackend();
	}
	
	/**
	 * Temporarily remove DB backend for this process
	 *
	 */
	private function removeDBBackend() {
		try {
			$backends = \OC::$server->getUserManager()->getBackends();
			foreach ($backends as $backend) {
				if ($backend instanceof \OC_User_Database) {
					$this->dbBackend = $backend;
					$this->logger->debug('removeDBBackend dbBackend: '.get_class($this->dbBackend), ['app' => 'test_user_ldap']);
					\OC::$server->getUserManager()->removeBackend($this->dbBackend);
					break;
				}
			}
		} catch (\Exception $e) {
			$this->logger->logException($e);
			die();
		}
	}
	
	/**
	 * Readd DB backend for this process
	 *
	 */
	private function readdDBBackend() {
		try {
			$this->logger->debug('readdDBBackend dbBackend: '.get_class($this->dbBackend), ['app' => 'test_user_ldap']);
			\OC::$server->getUserManager()->registerBackend($this->dbBackend);
			$this->dbBackend = null;
		} catch (\Exception $e) {
			$this->logger->logException($e);
			die();
		}
	}
}
