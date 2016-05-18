<?php
/**
 * @category Apps
 * @package  TestUserLDAP
 * @author   Tester
 *
 */

namespace OCA\test_user_ldap\lib;

use OC\ServerNotAvailableException;

use OCP\ILogger;

class LDAP {
	protected $curFunc = '';
	protected $curArgs = array();
	
	/**
	 * Constructor
	 *
	 */
	public function __construct(ILogger $logger) {
		$this->logger = $logger;
	}

	/**
	 * @param LDAP $link
	 * @param LDAP $userDN
	 * @param LDAP $newPassword
	 * @return mixed
	 */
	public function setPassword($link, $userDN, $newPassword) {
		//$hashedPassword = "{SSHA}" . sha1( $newPassword, TRUE );
		return $this->invokeLDAPMethod('mod_replace', $link, $userDN, array('userPassword' => $newPassword));
	}
	
	/**
	 * @param LDAP $link
	 * @param LDAP $userDN
	 * @param LDAP $newPassword
	 * @return mixed
	 */
	public function createUser($link, $userDN, $uid, $newPassword) {
		$info["cn"] = $uid;
		$info["sn"] = $uid;
		$info["uid"] = $uid;
		$info["displayName"] = $uid;
		//$info["homeDirectory"] = $uid;
		//$info["gidNumber"]="0";
		//$info["uidNumber"]="53722";
		/*$info["objectclass"]="posixAccount";
		$info["objectclass"]="top";
		$info["objectclass"]="inetOrgPerson";*/
		$info["objectclass"][0]= "top";
		$info["objectclass"][1] = "person";
		$info["objectclass"][2] = "inetOrgPerson";
		$info["objectclass"][3] = "organizationalPerson";
		$info["userPassword"] = $newPassword;
		
		return $this->invokeLDAPMethod('add', $link, $userDN, $info);
	}
	
	/**
	 * @param LDAP $link
	 * @param LDAP $userDN
	 * @return mixed
	 */
	public function deleteUser($link, $userDN) {
		return $this->invokeLDAPMethod('delete', $link, $userDN);
	}

	/**
	 * @param LDAP $link
	 * @return mixed|string
	 */
	public function errno($link) {
		return $this->invokeLDAPMethod('errno', $link);
	}

	/**
	 * @param LDAP $link
	 * @return int|mixed
	 */
	public function error($link) {
		return $this->invokeLDAPMethod('error', $link);
	}
	
	/**
	 * @param resource $link
	 * @return bool|mixed
	 */
	public function unbind($link) {
		return $this->invokeLDAPMethod('unbind', $link);
	}

	/**
	 * @param LDAP $link
	 * @param string $option
	 * @param int $value
	 * @return bool|mixed
	 */
	public function setOption($link, $option, $value) {
		return $this->invokeLDAPMethod('set_option', $link, $option, $value);
	}
	
	/**
	 * Checks whether the submitted parameter is a resource
	 * @param Resource $resource the resource variable to check
	 * @return bool true if it is a resource, false otherwise
	 */
	public function isResource($resource) {
		ob_start();
		var_dump($resource);
		$result = ob_get_clean();
		$this->logger->debug('$result '.$result, ['app' => 'test_user_ldap']);
		return is_resource($resource);
	}

	/**
	 * @return mixed
	 */
	private function invokeLDAPMethod() {
		$arguments = func_get_args();
		$func = 'ldap_' . array_shift($arguments);
		if(function_exists($func)) {
			$this->preFunctionCall($func, $arguments);
			$result = call_user_func_array($func, $arguments);
			if ($result === FALSE) {
				$this->postFunctionCall();
			}
			return $result;
		}
	}

	/**
	 * @param string $functionName
	 * @param array $args
	 */
	private function preFunctionCall($functionName, $args) {
		$this->curFunc = $functionName;
		$this->curArgs = $args;
	}

	private function postFunctionCall() {
		if($this->isResource($this->curArgs[0])) {
			$errorCode = ldap_errno($this->curArgs[0]);
			$errorMsg  = ldap_error($this->curArgs[0]);
			if($errorCode !== 0) {
				if($this->curFunc === 'ldap_get_entries'
						  && $errorCode === -4) {
				} else if ($errorCode === 32) {
					//for now
				} else if ($errorCode === 10) {
					//referrals, we switch them off, but then there is AD :)
				} else if ($errorCode === -1) {
					throw new ServerNotAvailableException('Lost connection to LDAP server.');
				} else if ($errorCode === 48) {
					throw new \Exception('LDAP authentication method rejected', $errorCode);
				} else if ($errorCode === 1) {
					throw new \Exception('LDAP Operations error', $errorCode);
				} else {
					$this->logger->debug('LDAP error '.$errorMsg.' (' .
											$errorCode.') after calling '.
											$this->curFunc,
										['app' => 'test_user_ldap']);
				}
			}
		}

		$this->curFunc = '';
		$this->curArgs = array();
	}
}
