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
			$this->logger->error('Session user not found in pre_setPasswordHook.', ['app' => 'test_user_ldap']);
			die();
		}

		try {
			$uid = $params['uid'];
			$userDN = \OC::$server->getLDAPProvider()->getUserDN($uid);
			$cr = \OC::$server->getLDAPProvider()->getLDAPConnection($uid);
			if(!$this->ldap->isResource($cr)) {
				//LDAP not available
				throw new \Exception('LDAP resource not available.');
			}
			$this->ldap->setPassword($cr, $userDN, $params['password']);
		} catch (\Exception $e) {
			$this->logger->logException($e);
			die();
		}
	}
	
	/**
	 * Create a user in LDAP
	 *
	 * @param array $params
	 */
	public function pre_createUserHook($params) {
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			$this->logger->error('Session user not found in pre_createUserHook.', ['app' => 'test_user_ldap']);
			die();
		}
		
		try {
			$cr = \OC::$server->getLDAPProvider()->getLDAPConnection($sessionUser->getUID());
			if(!$this->ldap->isResource($cr)) {
				//LDAP not available
				throw new \Exception('LDAP resource not available.');
			}
			$ldapBaseUsers = \OC::$server->getLDAPProvider()->getLDAPBaseUsers($sessionUser->getUID());
			$uid = $params['uid'];
			$userDN = "uid={$uid},{$ldapBaseUsers[0]}";
			$this->logger->debug('pre_createUserHook user DN: '.$userDN, ['app' => 'test_user_ldap']);
			$this->ldap->createUser($cr, $userDN, $uid, $params['password']);
		} catch (\Exception $e) {
			$this->logger->logException($e);
			die();
		}
	}
	
	/**
	 * Create owncloud user<->LDAP user mapping for a newly created LDAP user
	 *
	 * @param array $params
	 */
	public function post_createUserHook($params) {
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			$this->logger->error('Session user not found in post_createUserHook.', ['app' => 'test_user_ldap']);
			die();
		}
		
		try {
			$ldapBaseUsers = \OC::$server->getLDAPProvider()->getLDAPBaseUsers($sessionUser->getUID());
			$uid = $params['uid'];
			$userDN = "uid={$uid},{$ldapBaseUsers[0]}";
			$this->logger->debug('post_createUserHook user DN: '.$userDN, ['app' => 'test_user_ldap']);
			\OC::$server->getLDAPProvider()->getUserName($sessionUser->getUID(), $userDN);
		} catch (\Exception $e) {
			$this->logger->logException($e);
			die();
		}
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

		try {
			$cr = \OC::$server->getLDAPProvider()->getLDAPConnection($sessionUser->getUID());
			if(!$this->ldap->isResource($cr)) {
				//LDAP not available
				throw new \Exception('LDAP resource not available.');
			}
			$ldapBaseUsers = \OC::$server->getLDAPProvider()->getLDAPBaseUsers($sessionUser->getUID());
			$uid = $params['uid'];
			$userDN = "uid={$uid},{$ldapBaseUsers[0]}";
			$this->logger->debug('pre_deleteUserHook user DN: '.$userDN, ['app' => 'test_user_ldap']);
			$this->ldap->deleteUser($cr, $userDN);
		} catch (\Exception $e) {
			$this->logger->logException($e);
			die();
		}
	}
}
