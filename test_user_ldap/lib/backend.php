<?php
namespace OCA\test_user_ldap\lib;

use OCP\ILogger;

/**
 * Backend to create and delete a user in LDAP, and to set the password of a user in LDAP.
 *
 * @category Apps
 * @package  TestUserLDAP
 * @author   Tester
 */
 class Backend extends \OC_User_Backend{
	private $ldap;
	private $logger;
	 
	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		$this->logger = \OC::$server->getLogger();
		$this->ldap = new LDAP($this->logger);
	}
	
	/**
	 * Create a new user in LDAP
	 * @param string $uid The username of the user to create
	 * @param string $password The password of the new user
	 * @return bool
	 */
	public function createUser($uid, $password) {
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			throw new \Exception('Session user not found in createUser.', ['app' => 'test_user_ldap']);
		}
		
		$cr = null;
		$exception = null;
		try {
			$ldapProvider = \OC::$server->getLDAPProvider();
			$ldapBaseUsers = $ldapProvider->getLDAPBaseUsers($sessionUser->getUID());
			$userDN = "uid={$uid},{$ldapBaseUsers}";
			$this->logger->debug('createUser user DN: '.$userDN, ['app' => 'test_user_ldap']);
		} catch (\Exception $e) {
			$exception = $e;
			$this->logger->logException($e);
		}
		if($ldapProvider->dnExists($userDN)) {
			throw new \Exception('A user with that name already exists.');
		}
		try {
			$cr = $ldapProvider->getLDAPConnection($sessionUser->getUID());
			if(!$this->ldap->isResource($cr)) {
				//LDAP not available
				throw new \Exception('LDAP resource not available.');
			}
			$this->ldap->createUser($cr, $userDN, $uid, $password);
			//create user mapping in oc_ldap_user_mapping
			$ldapProvider->getUserName($userDN);
			//clear cache
			$ldapProvider->clearCache($sessionUser->getUID());
			return true;
		} catch (\Exception $e) {
			$exception = $e;
			$this->logger->logException($e);
		}
		if(!is_null($cr)) {
			try {$this->ldap->unbind($cr);}catch (\Exception $e) {/*ignored*/}
		}
		if(!is_null($exception)) {
			throw $exception;
		}
		return false;
	}
	
	/**
	 * check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 * @throws \Exception when connection could not be established
	 *
	public function userExists($uid) {
		$this->logger->debug('userExists: '.$uid, ['app' => 'test_user_ldap']);
		return true;
	}*/

	/**
	 * Delete a user in LDAP
	 *
	 * @param string $uid The username of the user to delete
	 *
	 * @return bool
	 *
	cannot use this method as it doesn't get called: deleteUser() is triggered on the user object
	and the user object has its backend as member, i.e. it only asks user_ldap
	public function deleteUser($uid) {
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			throw new \Exception('Session user not found in deleteUser.', ['app' => 'test_user_ldap']);
		}

		try {
			$cr = \OC::$server->getLDAPProvider()->getLDAPConnection($sessionUser->getUID());
			if(!$this->ldap->isResource($cr)) {
				//LDAP not available
				throw new \Exception('LDAP resource not available.');
			}
			$ldapBaseUsers = \OC::$server->getLDAPProvider()->getLDAPBaseUsers($sessionUser->getUID());
			$userDN = "uid={$uid},{$ldapBaseUsers}";
			$this->logger->debug('deleteUser user DN: '.$userDN, ['app' => 'test_user_ldap']);
			$this->ldap->deleteUser($cr, $userDN);
			return true;
		} catch (\Exception $e) {
			$this->logger->logException($e);
			throw $e;
		}
		return false;
	}*/
	
	/**
	 * Set password in LDAP
	 * @param string $uid The username
	 * @param string $password The new password
	 * @return bool
	 *
	 */
	public function setPassword($uid, $password) {
		$sessionUser = \OC::$server->getUserSession()->getUser();
		if (!$sessionUser) {
			throw new \Exception('Session user not found in setPassword.', ['app' => 'test_user_ldap']);
		}

		try {
			$ldapProvider = \OC::$server->getLDAPProvider();
			$userDN = $ldapProvider->getUserDN($uid);
			$cr = $ldapProvider->getLDAPConnection($uid);
			if(!$this->ldap->isResource($cr)) {
				//LDAP not available
				throw new \Exception('LDAP resource not available.');
			}
			$this->ldap->setPassword($cr, $userDN, $password);
		} catch (\Exception $e) {
			$this->logger->logException($e);
			throw $e;
		}
	}

	/**
	 * Backend name to be shown in user management
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName(){
		return 'TEST_USER_LDAP';
	}
}
