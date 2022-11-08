<?php

/*
 * SmiteAPISession
 * by Chorizorro
 * v1.0.1
 * 2013-06-15
 * 
 * Class defining a session with the Smite API
 */
//class SmiteAPISession implements JsonSerializable {
class SmiteAPISession {
	
	
	//
	// CONSTANTS
	//
	
	
	const SESSION_STATE_UNSET = 0;
	const SESSION_STATE_VALID = 1;
	const SESSION_STATE_TIMEDOUT = 2;
	
	
	//
	// MEMBERS
	//
	
	
	// Path to cache file used for thie session
	public $_cacheFile = null;
	// Boolean indicating whether the sessions is valid or not
	private $_state = SmiteAPISession::SESSION_STATE_UNSET;
	// Smite API access session ID
	private $_id = null;
	// Timeout for the recorded session ID in a DateTime object
	private $_timeout = null;
	
	
	//
	// METHODS
	//
	
	
	// Default constructor
	public function __construct($id = null, $timeout = null) {
		$sessionIdType = gettype($id);
		// Checking sessionId
		if(isset($id) && $id !== null) {
			if($sessionIdType !== "string") {
				trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$authKey must be a string ($sessionIdType given)", E_USER_ERROR);
				$id = null;
			}
			// FIXME Not sure if sessionId is always supposed to be 32 chars long
//			else if (!preg_match("/^[A-Z0-9]{32}$/i", $sessionId)) {
//				trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$sessionId must contain 32 hexadecimal figures (\"".htmlspecialchars($authKey)."\" given)", E_USER_ERROR);
//				$errors++;
//			}
			else if (!preg_match("/^[A-Z0-9]+$/i", $id)) {
				trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$id must contain only hexadecimal figures (\"".htmlspecialchars($id)."\" given)", E_USER_ERROR);
				$id = null;
			}
		}
		// Checking timeout
		if(isset($timeout) && $timeout !== null) {
			if(!$timeout instanceof DateTime) {
				if((int) $timeout === $timeout && $timeout <= PHP_INT_MAX && $timeout >= ~PHP_INT_MAX) {
					$timestamp = $timeout;
					$timeout = new DateTime("now", new DateTimeZone("UTC"));
					$timeout->setTimestamp($timestamp);
					unset($timestamp);
				}
				else {
					trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") \$timeout must contain a valid unix timestamp or DateTime object (\"".htmlspecialchars($timeout)."\" given)", E_USER_ERROR);
					$timeout = null;
				}
			}
		}
		// Setting the new object
		$this->_id = $id;
		$this->_timeout = $timeout;
		$this->computeState();
	}
	
	// Magic function: getter
	public function __get($name) {
		switch($name) {
			case "id":
				return $this->_id;
			case "timeout":
				return $this->_timeout;
			case "state":
				return $this->computeState();
			default:
				trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Unkown key \"".  htmlspecialchars($name)."\" in getter", E_USER_WARNING);
				return null;
		}
	}
	
	/*
	 * Initializes the sessions using a cache file
	 * 
	 * $filename (optional): string containing the name of the cache file to use
	 * 
	 * Returns false if the loading failed, else one of the SESSION_STATE constants defined in the class
	 */
	public function loadFromCache($filename = null) {
		// Setting filename if necessary
		if($filename !== null && is_string($filename))
			$this->_cacheFile = $filename;
		// Executing the loading
		$state = false;
		try {
			// Opening the cache file
			$file = fopen($this->_cacheFile, "r"); // Read-only
			if(!$file)
				throw new Exception("Couldn't create or retrieve cache file");
			// Read the file's content (using lock to avoid reading while the server is writing in the file)
			if(!flock($file, LOCK_SH))
				throw new Exception("Couldn't create a SHARE lock on the cache file");
			// The session is serialized into a JSON.
			$jsonSession = json_decode(file_get_contents($this->_cacheFile), true);
			flock($file, LOCK_UN);
			// If we have a valid associative array
			if($jsonSession !== null && is_array($jsonSession)) {
				$this->_id = $jsonSession["id"];
				$this->_timeout = new DateTime("now", new DateTimeZone("UTC"));
				$this->_timeout->setTimestamp($jsonSession["timeout"]);
				$state = $this->computeState();
			}
		}
		catch(Exception $e) {
			trigger_error ($e->getFile()." (".$e->getLine().") Cache loading threw an Exception: (#".$e->getCode().") ".$e->getMessage(), E_USER_WARNING);;
			$state = false;
		}
		if($file)
			fclose($file);
		return $state;
	}
	
	public function saveToCache($filename = null) {
		// Checking if the session has been set
		if($this->_state === SmiteAPISession::SESSION_STATE_UNSET) {
			trigger_error (__CLASS__."::".__FUNCTION__." (".__LINE__.") Can't save a session that is unset");
			return false;
		}
		// Setting filename if necessary
		if($filename !== null && is_string($filename))
			$this->_cacheFile = $filename;
		// Executing the save
		try {
			// Opening the cache file
			$file = fopen($this->_cacheFile, "c+"); // Read-only
			if(!flock($file, LOCK_EX))
				throw new Exception("Couldn't create an EXCLUSIVE lock on the cache file");
			if(!ftruncate($file, 0))
				throw new Exception("Couldn't clear the session cache file");
			if(!($wJson = json_encode($this->serialize())))
				throw new Exception("Couldn't serialize session data into JSON");
			if(fwrite($file, $wJson) === 0)
				throw new Exception("Couldn't write in the session cache file");
			flock($file, LOCK_UN);
			$state = true;
		}
		catch(Exception $e) {
			trigger_error ($e->getFile()." (".$e->getLine().") Cache loading threw an Exception: (#".$e->getCode().") ".$e->getMessage(), E_USER_WARNING);;
			$state = false;
		}
		if($file)
			fclose($file);
		return $state;
	}
	
	
	// Serializing the session in JSON
//	public function jsonSerialize() {
//		return Array(
//			"id" => $this->_id,
//			"timeout" => $this->_timeout->getTimestamp(),
//		);
//	}
	private function serialize() {
		return Array(
			"id" => $this->_id,
			"timeout" => $this->_timeout->getTimestamp(),
		);
	}
	
	/*
	 * Checks if the session is valid:
	 * - id and timeout must be set
	 * - timeout must be a value greater than now
	 * 
	 * Returns a boolean
	 */
	private function computeState() {
		if(!isset($this->_id) || $this->_id === null || !isset($this->_timeout) || $this->_timeout === null)
			return ($this->_state = SmiteAPISession::SESSION_STATE_UNSET);
		return ($this->_state = $this->_timeout > new DateTime("now", new DateTimeZone("UTC")) ? SmiteAPISession::SESSION_STATE_VALID : SmiteAPISession::SESSION_STATE_TIMEDOUT);
	}
}

?>
