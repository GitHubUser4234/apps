<?php

/**
 * User DP authentication
 *
 * @category Apps
 * @package  UserDP
 * @author   MARS
 */
class OC_USER_MARS_USER_DP extends \OCA\user_dp\Base {

	private $uid;
	
	/**
	 * Create new DP authentication provider
	 *
	 */
	public function __construct() {
		parent::__construct("OC_USER_MARS_USER_DP");
	}

	/**
	 * In case the user has been authenticated by Apache true is returned.
	 *
	 * @return boolean whether Apache reports a user as currently logged in.
	 */
	public function isSessionActive() {
		//get request headers
		if (function_exists("apache_request_headers")) {
			$headers = apache_request_headers();
		} elseif (isset($_SERVER)) {
			$headers = $_SERVER;
		} else {
			$headers = array();
		}
		
		//check whether DP UID and DEPTID are present
		$headerKeys = array_keys($headers);

		$keyPositionInArray = array_search("uid", array_map('strtolower', $headerKeys));
		if (FALSE === $keyPositionInArray) {
			OCP\Util::writeLog('user_dp', 'DEBUG: DP UID not present', OCP\Util::DEBUG);
			return false;
		}
		$dpuid = $headers[$headerKeys[$keyPositionInArray]];
		OCP\Util::writeLog('user_dp', 'DEBUG: DP UID: ' . $dpuid, OCP\Util::DEBUG);
		
		$keyPositionInArray = array_search("dpdeptid", array_map('strtolower', $headerKeys));
		if (FALSE === $keyPositionInArray) {
			OCP\Util::writeLog('user_dp', 'DEBUG: DP DEPTID not present', OCP\Util::DEBUG);
			return false;
		}			
		$dpdeptid = $headers[$headerKeys[$keyPositionInArray]];
		OCP\Util::writeLog('user_dp', 'DEBUG: DP DEPTID: ' . $dpdeptid, OCP\Util::DEBUG);
		
		$uid = "{$dpdeptid}_{$dpuid}";
		if (\OCP\User::userExists($uid)) {
			$this->uid = $uid;
			$this->storeUser($this->uid);
			return true;
		}
		OCP\Util::writeLog('user_dp', 'DEBUG: uid does not exist: ' . $uid, OCP\Util::DEBUG);
		return false;
	}
	
	/**
	 * Return the id of the current user
	 * @return string
	 */
	public function getCurrentUserId(){
		OCP\Util::writeLog('user_dp', 'getCurrentUserId: ' . $this->uid, OCP\Util::DEBUG);
		return $this->uid;
	}
	
	/**
	 * Creates an attribute which is added to the logout hyperlink. It can
	 * supply any attribute(s) which are valid for <a>.
	 *
	 * @return string with one or more HTML attributes.
	 */
	public function getLogoutAttribute(){
		return 'href="' . link_to('', 'index.php') . '?logout=true&amp;requesttoken=' . urlencode(\OCP\Util::callRegister()) . '"';
	}
}
