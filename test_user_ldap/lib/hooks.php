<?php
namespace OCA\test_user_ldap\lib;

use OCP\ILogger;

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
	
	public function connectHooks() {
		\OCP\Util::connectHook('OC_User', 'pre_setPassword', $this, 'setPasswordHook');
		\OCP\Util::connectHook('OC_User', 'pre_createUser', $this, 'createUserHook');
		\OCP\Util::connectHook('OC_User', 'pre_deleteUser', $this, 'deleteUserHook');
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
	 * Set user password in LDAP
	 *
	 * @param array $params
	 */
	public function setPasswordHook($params) {
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			$this->logger->debug('Session user not found.', ['app' => 'test_user_ldap']);
			return false;
		}

		$uid = $params['uid'];
		$userDN = \OC::$server->getLDAPProvider()->getUserDN($sessionUser->getUID(), $uid);
		$cr = \OC::$server->getLDAPProvider()->getLDAPConnection($sessionUser->getUID());
		if(!$this->ldap->isResource($cr)) {
			//LDAP not available
			$this->logger->debug('LDAP resource not available.', ['app' => 'test_user_ldap']);
		}
		return $this->ldap->setPassword($cr, $userDN, $params['password']);
	}
	
	/**
	 * Create a user in LDAP
	 *
	 * @param array $params
	 */
	public function createUserHook($params) {
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			$this->logger->debug('Session user not found.', ['app' => 'test_user_ldap']);
			return false;
		}
		
		$cr = \OC::$server->getLDAPProvider()->getLDAPConnection($sessionUser->getUID());
		if(!$this->ldap->isResource($cr)) {
			//LDAP not available
			$this->logger->debug('LDAP resource not available.', ['app' => 'test_user_ldap']);
		}
		$uid = $params['uid'];
		$userDN = "uid={$uid},{$connection->ldapBaseUsers}";
		$this->ldap->createUser($cr, $userDN, $params['password']);
	}
	
	/**
	 * Delete a user in LDAP
	 *
	 * @param array $params
	 */
	public function deleteUserHook($params) {
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			$this->logger->debug('Session user not found.', ['app' => 'test_user_ldap']);
			return false;
		}
		$this->logger->debug('$sessionUser->getUID().'.$sessionUser->getUID(), ['app' => 'test_user_ldap']);
		$cr = \OC::$server->getLDAPProvider()->getLDAPConnection($sessionUser->getUID());
		if(!$this->ldap->isResource($cr)) {
			//LDAP not available
			$this->logger->debug('LDAP resource not available.', ['app' => 'test_user_ldap']);
		}
		$uid = $params['uid'];
		$userDN = "uid={$uid},{$connection->ldapBaseUsers}";
		$this->ldap->createUser($cr, $userDN, $params['password']);
	}
}
