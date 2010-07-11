<?php

/**
 * workaround for being able to load different usersession object from different standalone applications
 */
class SolrWCF extends WCF {
	protected static $firstUserObj = null;

	public static function startAnonymousStandaloneSession($typeName) {
		if(self::$firstUserObj === null) {
			self::$firstUserObj = self::getSession()->getUser();
		}

		if($typeName == 'post') {
			// TODO: search path to next standalone application, get path to global.php, find core class, get reflection of core class to find user sesssion object
			require_once(WCF_DIR.'../lib/data/user/WBBUserSession.class.php');
			self::$userObj = new WBBUserSession(0);
		} else {
			require_once(WCF_DIR.'lib/system/session/UserSession.class.php');
			self::$userObj = new UserSession(0);
		}
	}

	public static function endAnonymousStandaloneSession() {
		if(self::$firstUserObj !== null) {
			self::$userObj = self::$firstUserObj;
		}
	}
}
?>
