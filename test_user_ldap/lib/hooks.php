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
		\OCP\Util::connectHook('OC_User', 'pre_setPassword', 'OCA\test_user_ldap\lib\Hooks', 'setPasswordHook');
		\OCP\Util::connectHook('OC_User', 'pre_createUser', 'OCA\test_user_ldap\lib\Hooks', 'createUserHook');
		\OCP\Util::connectHook('OC_User', 'pre_deleteUser', 'OCA\test_user_ldap\lib\Hooks', 'deleteUserHook');
	}
	
	/**
	 * Constructor
	 *
	 */
	public function __construct(ILogger $logger) {
		$this->logger = $logger;
		$this->ldap = new LDAP($logger);
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

		$cr = \OC::$server->getLDAPProvider()->getLDAPAccess($sessionUser->getUID())->connection->getConnectionResource();
		if(!$this->ldap->isResource($cr)) {
			//LDAP not available
			$this->logger->debug('LDAP resource not available.', ['app' => 'test_user_ldap']);
		}
		$uid = $params['uid'];
		$userDN = \OC::$server->getLDAPProvider()->getUserDN($uid);
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
		
		$connection = \OC::$server->getLDAPProvider()->getLDAPAccess($sessionUser->getUID())->connection;
		$cr = $connection->getConnectionResource();
		if(!$this->ldap->isResource($cr)) {
			//LDAP not available
			$this->logger->debug('LDAP resource not available.', ['app' => 'test_user_ldap']);
		}
		$uid = $params['uid'];
		$userDN = "uid={$uid},{$connection->ldapBaseUsers}";
		$this->ldap->createUser($cr, $userDN, $params['password']);
	}
	
	
}
