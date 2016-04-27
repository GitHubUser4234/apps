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
		\OCP\Util::connectHook('OC_User', 'pre_setPassword', $this, 'pre_setPasswordHook');
		\OCP\Util::connectHook('OC_User', 'pre_createUser', $this, 'pre_createUserHook');
		\OCP\Util::connectHook('OC_User', 'post_createUser', $this, 'post_createUserHook');
		\OCP\Util::connectHook('OC_User', 'pre_deleteUser', $this, 'pre_deleteUserHook');
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
	public function pre_setPasswordHook($params) {
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			$this->logger->debug('Session user not found.', ['app' => 'test_user_ldap']);
			return false;
		}

		$uid = $params['uid'];
		$userDN = \OC::$server->getLDAPProvider()->getUserDN($uid);
		$cr = \OC::$server->getLDAPProvider()->getLDAPConnection($uid);
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
	public function pre_createUserHook($params) {
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
		$ldapBaseUsers = \OC::$server->getLDAPProvider()->getLDAPBaseUsers($sessionUser->getUID());
		$uid = $params['uid'];
		$userDN = "uid={$uid},{$ldapBaseUsers[0]}";
		$this->logger->debug('pre_createUserHook user DN: '.$userDN, ['app' => 'test_user_ldap']);
		$this->ldap->createUser($cr, $userDN, $uid, $params['password']);
	}
	
	/**
	 * Create owncloud user<->LDAP user mapping for a newly created LDAP user
	 *
	 * @param array $params
	 */
	public function post_createUserHook($params) {
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			$this->logger->debug('Session user not found.', ['app' => 'test_user_ldap']);
			return false;
		}
		
		$ldapBaseUsers = \OC::$server->getLDAPProvider()->getLDAPBaseUsers($sessionUser->getUID());
		$uid = $params['uid'];
		$userDN = "uid={$uid},{$ldapBaseUsers[0]}";
		$this->logger->debug('post_createUserHook user DN: '.$userDN, ['app' => 'test_user_ldap']);
		\OC::$server->getLDAPProvider()->getUserName($sessionUser->getUID(), $userDN);
	}
	
	/**
	 * Delete a user in LDAP
	 *
	 * @param array $params
	 */
	public function pre_deleteUserHook($params) {
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
		$ldapBaseUsers = \OC::$server->getLDAPProvider()->getLDAPBaseUsers($sessionUser->getUID());
		$uid = $params['uid'];
		$userDN = "uid={$uid},{$ldapBaseUsers[0]}";
		$this->logger->debug('pre_deleteUserHook user DN: '.$userDN, ['app' => 'test_user_ldap']);
		$this->ldap->deleteUser($cr, $userDN);
	}
}
