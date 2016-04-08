<?php
namespace OCA\User_Dp;
/**
 * User DP authentication
 *
 * @category Apps
 * @package  UserDP
 * @author   MARS
 */
class Dp extends \OC_User_Backend implements \OCP\Authentication\IApacheBackend{




	/**
	 * In case the user has been authenticated by Apache true is returned.
	 *
	 * @return boolean whether Apache reports a user as currently logged in.
	 */
	public function isSessionActive() {
		return true;
	}
	
	/**
	 * Return the id of the current user
	 * @return string
	 */
	public function getCurrentUserId(){
		return 'dep_tester123';
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
