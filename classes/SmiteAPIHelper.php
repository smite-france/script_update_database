<?php

require_once(__DIR__."/SmiteAPISession.php");

/*
 * SmiteAPIHelper
 * by Chorizorro
 * v1.0.1
 * 2013-06-15
 *
 * Set of useful methods and attributes to manage with Smite API requests
 */
abstract class SmiteAPIHelper {


	//
	// CONSTANTS
	//


	// Defining const strings for requests preferred format
	const SMITE_API_FORMAT_XML = "xml";
	const SMITE_API_FORMAT_JSON = "json";


	//
	// MEMBERS
	//


	// Response format preference
	public static $_format = SmiteAPIHelper::SMITE_API_FORMAT_XML;
	// Smite API session
	private static $_session = null;
	// Smite API DevId provided by Hi-Rez
	private static $_devId = 0;
	// Smite API AuthKey provided by Hi-Rez
	private static $_authKey = "";


	//
	// METHODS
	//


	/*
	 * Sets the credentials to use the API
	 *
	 * $devId: integer or string containing an integer, corresponding to your devId
	 * $authKey: string containing hexadecimal figures only, corresopnding to your authKey
	 *
	 * Returns a boolean indicating if the credentials were successfully set
	 */
	public static function setCredentials($devId, $authKey) {
		// Checking parameters
		$errors = 0;
		$devIdType = gettype($devId);
		$authKeyType = gettype($authKey);
		// Checking devId
		if($devIdType !== "integer") {
			if($devIdType !== "string") {
				trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$devId must be a strict integer or an integer in a string ($devIdType given)", E_USER_ERROR);
				$errors++;
			}
			else if(($devId = intval($devId)) === 0) {
				trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Invalid \$devId string given \"".htmlspecialchars($devId)."\"", E_USER_ERROR);
				$errors++;
			}
		}
		// Checking authkey
		if($authKeyType !== "string") {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$authKey must be a string ($authKeyType given)", E_USER_ERROR);
			$errors++;
		}
		// FIXME Not sure if authKey is always supposed to be 32 chars long
//		else if (!preg_match("/^[A-Z0-9]{32}$/i", $authKey)) {
//			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$authKey must contain 32 hexadecimal figures (\"".htmlspecialchars($authKey)."\" given)", E_USER_ERROR);
//			$errors++;
//		}
		else if (!preg_match("/^[A-Z0-9]+$/i", $authKey)) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$authKey must contain only hexadecimal figures (\"".htmlspecialchars($authKey)."\" given)", E_USER_ERROR);
			$errors++;
		}
		// Exit the function if some parameters are invalid
		if($errors) return false;
		unset($devIdType, $authKeyType, $errors);
		// Set the credentials
		SmiteAPIHelper::$_devId = $devId;
		SmiteAPIHelper::$_authKey = $authKey;
		return true;
	}

	/*
	 * Gets the current session
	 *
	 * Returns the current SmiteAPISession object
	 */
	public static function getSession() {
		return SmiteAPIHelper::$_session;
	}

	/*
	 * Sets a custom session into the helper
	 *
	 * $session: SmiteAPISession object which must be valid
	 *
	 * Returns a boolean indicating if the session was successfully set
	 */
	public static function setSession($session) {
		// Checking parameters
		if(!$session instanceof SmiteAPISession) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$session must be a valid SmiteAPISession object", E_USER_ERROR);
			return false;
		}
		// Checking session state
		switch($session->state) {
			case SmiteAPISession::SESSION_STATE_VALID:
				// Do nothing
				break;
			case SmiteAPISession::SESSION_STATE_UNSET:
				trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") The session is not set", E_USER_WARNING);
				break;
			case SmiteAPISession::SESSION_STATE_TIMEDOUT:
				trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") The session is outdated", E_USER_WARNING);
				break;
			default:
				trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Unknown session state", E_USER_WARNING);
				break;
		}
		// Set the session
		SmiteAPIHelper::$_session = $session;
		return true;
	}

	/*
	 * Send a createsession request
	 *
	 * Returns the web-service response or null if an error occurred
	 */
	public static function createSession() {
		// Checking the credentials
		if(!(SmiteAPIHelper::$_devId && SmiteAPIHelper::$_authKey)) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Smite API Credentials must be set before any request", E_USER_ERROR);
			return null;
		}
		// Checking if a signature could be created
		if(($signature = SmiteAPIHelper::getSignature("createsession")) === null) return false;
		// Sending request
		$url = "createsession".SmiteAPIHelper::$_format."/".SmiteAPIHelper::$_devId."/$signature/".SmiteAPIHelper::getNowTimestamp();
		return SmiteAPIHelper::executeRequest($url);
	}

	/*
	 * Send a getitems request
	 *
	 * $lang (optional): integer (1 for English, 3 for French)
	 *
	 * Returns the web-service response or null if an error occurred
	 */
	public static function getItems($lang = 1) {
		// Checking parameter
		if(!in_array($lang, Array(1, 3))) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$lang must be an integer (1 for English or 3 for French) (\"".htmlspecialchars($lang)."\" given). Assuming 1 by default", E_USER_WARNING);
			$lang = 1;
		}
		// Creating or retrieving session
		if(!SmiteAPIHelper::createSessionIfNecessary()) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Session creation failed.", E_USER_ERROR);
			return null;
		}
		// Checking if a signature could be created
		if(($signature = SmiteAPIHelper::getSignature("getitems")) === null) return false;
		// Sending request
		$url = "getitems".SmiteAPIHelper::$_format."/".SmiteAPIHelper::$_devId."/$signature/".SmiteAPIHelper::$_session->id."/".SmiteAPIHelper::getNowTimestamp()."/".$lang;
		return SmiteAPIHelper::executeRequest($url);
	}

	/*
	 * Send a getplayer request
	 *
	 * $playerName: string containing a player name
	 *
	 * Returns the web-service response or null if an error occurred
	 */
	public static function getPlayer($playerName) {
		// Checking parameter
		$playerNameType = gettype($playerName);
		if($playerNameType !== "string" || empty($playerName)) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$playerName must be a non-empty string (\"$playerNameType\" given)", E_USER_ERROR);
			return null;
		}
		unset($playerNameType);
		// Creating or retrieving session
		if(!SmiteAPIHelper::createSessionIfNecessary()) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Session creation failed.", E_USER_ERROR);
			return null;
		}
		// Checking if a signature could be created
		if(($signature = SmiteAPIHelper::getSignature("getplayer")) === null) return false;
		// Sending request
		$url = "getplayer".SmiteAPIHelper::$_format."/".SmiteAPIHelper::$_devId."/$signature/".SmiteAPIHelper::$_session->id."/".SmiteAPIHelper::getNowTimestamp()."/".$playerName;
		return SmiteAPIHelper::executeRequest($url);
	}

	/*
	 * Send a getmatchdetails request
	 *
	 * $mapId: integer or string formatted integer corresponding to the match id (retrieved via getmatchhistory)
	 *
	 * Returns the web-service response or null if an error occurred
	 */
	public static function getMatchDetails($mapId) {
		// Checking parameter
		$mapIdType = gettype($mapId);
		if($mapIdType !== "integer") {
			if($mapIdType !== "string") {
				trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$mapId must be a strict integer or an integer in a string ($mapIdType given)", E_USER_ERROR);
				return null;
			}
			if(($mapId = intval($mapId)) === 0) {
				trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Invalid \$mapId string given \"".htmlspecialchars($mapId)."\"", E_USER_ERROR);
				return null;
			}
		}
		unset($mapIdType);
		// Creating or retrieving session
		if(!SmiteAPIHelper::createSessionIfNecessary()) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Session creation failed.", E_USER_ERROR);
			return null;
		}
		// Checking if a signature could be created
		if(($signature = SmiteAPIHelper::getSignature("getmatchdetails")) === null) return false;
		// Sending request
		$url = "getmatchdetails".SmiteAPIHelper::$_format."/".SmiteAPIHelper::$_devId."/$signature/".SmiteAPIHelper::$_session->id."/".SmiteAPIHelper::getNowTimestamp()."/".$mapId;
		return SmiteAPIHelper::executeRequest($url);
	}

	/*
	 * Send a getmatchhistory request
	 *
	 * $playerName: string containing a player name
	 *
	 * Returns the web-service response or null if an error occurred
	 */
	public static function getMatchHistory($playerName) {
		// Checking parameter
		$playerNameType = gettype($playerName);
		if($playerNameType !== "string" || empty($playerName)) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$playerName must be a non-empty string (\"$playerNameType\" given)", E_USER_ERROR);
			return null;
		}
		unset($playerNameType);
		// Creating or retrieving session
		if(!SmiteAPIHelper::createSessionIfNecessary()) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Session creation failed.", E_USER_ERROR);
			return null;
		}
		// Checking if a signature could be created
		if(($signature = SmiteAPIHelper::getSignature("getmatchhistory")) === null) return false;
		// Sending request
		$url = "getmatchhistory".SmiteAPIHelper::$_format."/".SmiteAPIHelper::$_devId."/$signature/".SmiteAPIHelper::$_session->id."/".SmiteAPIHelper::getNowTimestamp()."/".$playerName;
		return SmiteAPIHelper::executeRequest($url);
	}

	/*
	 * Send a getqueuestats request
	 *
	 * $playerName: string containing a player name
	 * $queue: integer containing a valid queue Id
	 *
	 * Returns the web-service response or null if an error occurred
	 */
	public static function getQueueStats($playerName, $queue = 426) {
		// Checking parameter
		$errors = 0;
		$playerNameType = gettype($playerName);
		// Checking playerName
		if($playerNameType !== "string" || empty($playerName)) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$playerName must be a non-empty string (\"$playerNameType\" given)", E_USER_ERROR);
			$errors++;
		}
		// Checking queue
		if(!in_array($queue, Array(423, 424, 426, 427, 429, 430, 431, 433, 435, 438, 439))) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$queue must be a valid queueId (423, 424, 426, 427, 429, 430, 431, 433, 435, 438, 439) (\"".htmlspecialchars($queue)."\" given). Read Official Smite API Developer Guide for more information", E_USER_ERROR);
			$errors++;
		}
		// Exit the function if some parameters are invalid
		if($errors) return false;
		unset($playerNameType);
		// Creating or retrieving session
		if(!SmiteAPIHelper::createSessionIfNecessary()) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Session creation failed.", E_USER_ERROR);
			return null;
		}
		// Checking if a signature could be created
		if(($signature = SmiteAPIHelper::getSignature("getqueuestats")) === null) return false;
		// Sending request
		$url = "getqueuestats".SmiteAPIHelper::$_format."/".SmiteAPIHelper::$_devId."/$signature/".SmiteAPIHelper::$_session->id."/".SmiteAPIHelper::getNowTimestamp()."/".$playerName."/".$queue;
		return SmiteAPIHelper::executeRequest($url);
	}

	/*
	 * Send a gettopranked request
	 *
	 * Returns the web-service response or null if an error occurred
	 */
	public static function getTopRanked() {
		// Creating or retrieving session
		if(!SmiteAPIHelper::createSessionIfNecessary()) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Session creation failed.", E_USER_ERROR);
			return null;
		}
		// Checking if a signature could be created
		if(($signature = SmiteAPIHelper::getSignature("gettopranked")) === null) return false;
		// Sending request
		$url = "gettopranked".SmiteAPIHelper::$_format."/".SmiteAPIHelper::$_devId."/$signature/".SmiteAPIHelper::$_session->id."/".SmiteAPIHelper::getNowTimestamp();
		return SmiteAPIHelper::executeRequest($url);
	}

	/*
	 * Send a getdataused request
	 *
	 * Returns the web-service response or null if an error occurred
	 */
	public static function getDataUsed() {
		// Creating or retrieving session
		if(!SmiteAPIHelper::createSessionIfNecessary()) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Session creation failed.", E_USER_ERROR);
			return null;
		}
		// Checking if a signature could be created
		if(($signature = SmiteAPIHelper::getSignature("getdataused")) === null) return false;
		// Sending request
		$url = "getdataused".SmiteAPIHelper::$_format."/".SmiteAPIHelper::$_devId."/$signature/".SmiteAPIHelper::$_session->id."/".SmiteAPIHelper::getNowTimestamp();
		return SmiteAPIHelper::executeRequest($url);
	}

	public static function getGodRecommendedItems($god = 0,$lang = 1) {
		// Checking parameter
		if(!in_array($lang, Array(1, 3))) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$lang must be an integer (1 for English or 3 for French) (\"".htmlspecialchars($lang)."\" given). Assuming 1 by default", E_USER_WARNING);
			$lang = 1;
		}
		// Creating or retrieving session
		if(!SmiteAPIHelper::createSessionIfNecessary()) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Session creation failed.", E_USER_ERROR);
			return null;
		}

		//try if id god is not null
		if(empty($god) || $god == 0){
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") GOD id missing.", E_USER_ERROR);
			return null;
		}

		// Checking if a signature could be created
		if(($signature = SmiteAPIHelper::getSignature("getgodrecommendeditems")) === null) return false;
		// Sending request
		$url = "getgodrecommendeditems".SmiteAPIHelper::$_format."/".SmiteAPIHelper::$_devId."/$signature/".SmiteAPIHelper::$_session->id."/".SmiteAPIHelper::getNowTimestamp()."/".$god."/".$lang;
		return SmiteAPIHelper::executeRequest($url);
	}


	public static function getGods($lang = 1) {
		// Checking parameter
		if(!in_array($lang, Array(1, 3))) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$lang must be an integer (1 for English or 3 for French) (\"".htmlspecialchars($lang)."\" given). Assuming 1 by default", E_USER_WARNING);
			$lang = 1;
		}
		// Creating or retrieving session
		if(!SmiteAPIHelper::createSessionIfNecessary()) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Session creation failed.", E_USER_ERROR);
			return null;
		}
		// Checking if a signature could be created
		if(($signature = SmiteAPIHelper::getSignature("getgods")) === null) return false;
		// Sending request
		$url = "getgods".SmiteAPIHelper::$_format."/".SmiteAPIHelper::$_devId."/$signature/".SmiteAPIHelper::$_session->id."/".SmiteAPIHelper::getNowTimestamp()."/".$lang;
		return SmiteAPIHelper::executeRequest($url);
	}

	/*
	 * Send a ping request
	 *
	 * Returns the web-service response or null if an error occurred
	 */
	public static function ping() {
		return SmiteAPIHelper::executeRequest("ping".SmiteAPIHelper::$_format);
	}

	/*
	 * Executes a request using cUrl library
	 *
	 * $url: the Smite API URL to call (without http://api.smitegame.com/smiteapi.svc/)
	 *
	 * Returns the result of the cUrl execution (JSON or XML string)
	 */
	private static function executeRequest($url) {
		$url = rawurlencode($url);
		$ch = curl_init("http://api.smitegame.com/smiteapi.svc/".$url);
		curl_setopt_array($ch, Array(
			CURLOPT_TIMEOUT => 10,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_HEADER => 0,
			CURLOPT_HTTPHEADER => array('Content-type: '.(SmiteAPIHelper::$_format === SmiteAPIHelper::SMITE_API_FORMAT_JSON ? 'application/json' : 'application/xml'))
		));
		ob_start();
		return curl_exec ($ch); // execute the curl command
		ob_end_clean();
		curl_close ($ch);
		unset($ch);
	}

	/*
	 * Creates a signature for a query
	 *
	 * $methodName: string containing the name of the method that will be called for the query
	 *
	 * Returns the signature as a string, or null if the signature couldn't be computed
	 */
	private static function getSignature($methodName) {
		// Checking devId, authKey and methodName
		if(!(SmiteAPIHelper::$_devId && SmiteAPIHelper::$_authKey && is_string($methodName)))
			return null;
		// Returning the signature
		$toSign = SmiteAPIHelper::$_devId.$methodName.SmiteAPIHelper::$_authKey.SmiteAPIHelper::getNowTimestamp();
		return md5($toSign);
	}

	/*
	 * Creates a session using the devId and the authKey set
	 * and sets the sessionId retrieved if the session was successfully created
	 *
	 * $force (optional): Force the regeneration of the session even if a valid session already exists
	 *
	 * Returns a boolean indicating whether a valid session is now active
	 */
	private static function createSessionIfNecessary($force = false) {
		// Checking if a valid session already exists
		if(!$force && isset(SmiteAPIHelper::$_session) && SmiteAPIHelper::$_session->state === SmiteAPISession::SESSION_STATE_VALID) return true;
		// Checking the credentials
		if(!(SmiteAPIHelper::$_devId && SmiteAPIHelper::$_authKey)) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Smite API Credentials must be set before any request", E_USER_ERROR);
			return null;
		}
		// Checking if a signature could be created
		if(($signature = SmiteAPIHelper::getSignature("createsession")) === null) return false;
		try {
			// Forcing JSON for internal purpose
			$url = "createsessionjson/".SmiteAPIHelper::$_devId."/$signature/".SmiteAPIHelper::getNowTimestamp();
			$response = SmiteAPIHelper::executeRequest($url);
			$result = json_decode($response, true);
			if(array_key_exists("ret_msg", $result))
			{
				if($result["ret_msg"] === "Approved" && array_key_exists("session_id", $result) && !empty($result["session_id"]) && array_key_exists("timestamp", $result) && !empty($result["timestamp"])) {
					SmiteAPIHelper::$_session = new SmiteAPISession($result["session_id"], SmiteAPIHelper::generateTimeoutFromOddTimestamp($result["timestamp"]));
					$status = true;
				}
			}
			else
				trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") createsession web-service didn't return \"ret_msg\" attribute");
		}
		catch(Exception $e) {
			trigger_error ($e->getFile()." (".$e->getLine().") Call to createsession web-service threw an Exception: (#".$e->getCode().") ".$e->getMessage(), E_USER_ERROR);
		}
		return isset($status) && $status === true;
	}

	/*
	 * Generates a "now" timestamp formatted to be passed in Smite API requests
	 * The valid format is YmdHis (for example 2010112080542) on UTC timezone
	 *
	 * Returns the generated timestamp
	 */
	private static function getNowTimestamp() {
		$d = new DateTime("now", new DateTimeZone("UTC"));
		return $d->format("YmdHis");
	}

	/*
	 * Timestamp converter Helper 'cause the timestamp returned by the web-services
	 * is just... odd
	 *
	 * $str: string containing the timestamp
	 *
	 * Returns a UNIX timestamp
	 */
	private static function generateTimeoutFromOddTimestamp($str) {
		return DateTime::createFromFormat("n/j/Y g:i:s A", $str, new DateTimeZone("UTC"))->modify('+14 minute');
	}
}

?>
