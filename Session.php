<?php
	function sessionLog($var) {
		file_put_contents(__DIR__.'/session.log', $var." - ".time()."\n", FILE_APPEND);
	}
	// http://php.net/manual/en/session.customhandler.php
	// http://www.php.net/manual/en/function.session-set-save-handler.php
	class Session {
		private $db = NULL;
		private $id = 0;
		private $data = NULL;
		function __construct($db = NULL) {
			if (!empty($db)) {
				$this->db = $db;
				session_set_save_handler(
					array($this, 'open'),
					array($this, 'close'),
					array($this, 'read'),
					array($this, 'write'),
					array($this, 'destroy'),
					array($this, 'gc')
				);
				register_shutdown_function('session_write_close');
				// Set cookie attributes
				session_set_cookie_params(
					0, 							// lifetime
					'/', 						// path
					$_SERVER['SERVER_NAME'], 	// domain
					false 						// secure
				);
				// session_start() creates a session or resumes the current one based
				// on a session identifier passed via a GET or POST request, or passed via a cookie.
				session_start();
			} else trigger_error('Class Session needs a valid database object.');
		}

		// The open callback works like a constructor in classes and is executed when the session
		// is being opened. It is the first callback function executed when the session is started
		// automatically or manually with session_start(). Return value is TRUE for success, FALSE for failure.
		function open($savePath, $sessName) { 
			sessionLog('Open');
			return true; 
		}

		// The read callback must always return a session encoded (serialized) string, or an empty string
		// if there is no data to read. This callback is called internally by PHP when the session starts
		// or when session_start() is called. Before this callback is invoked PHP will invoke the open callback. 
		// The value this callback returns must be in exactly the same serialized format that was originally passed
		// for storage to the write callback. The value returned will be unserialized automatically by PHP and used
		// to populate the $_SESSION superglobal. While the data looks similar to serialize() please note it is a
		// different format which is speficied in the session.serialize_handler ini setting.
		function read($sessId) {
			announce('Read');
			sessionLog('Read');
			$this->db->select(
				'*',
				'php_session',
				'session_id = ?',
				array($sessId), true
			);
			$sessions = $this->db->fetch_assoc_all();
			debug($sessions);
			if (count($sessions) === 1) { // Found a session
				$this->id = $sessions[0]['session_id'];
				$this->data = $sessions[0]['session_data'];
				return $this->data;
			} elseif (count($sessions) > 1) // Multiple session found
				announce('Multiple Sessions Encountered.');
			return  ''; // No session found
		}

		// The write callback is called when the session needs to be saved and closed. This callback receives the
		// current session ID a serialized version the $_SESSION superglobal. The serialization method used internally
		// by PHP is specified in the session.serialize_handler ini setting. The serialized session data passed to this
		// callback should be stored against the passed session ID. When retrieving this data, the read callback must
		// return the exact value that was originally passed to the write callback. This callback is invoked when PHP
		// shuts down or explicitly when session_write_close() is called. Note that after executing this function PHP
		// will internally execute the close callback.
		function write($sessId, $encSessArray) {
			announce('Write');
			sessionLog('Write');
			if ($this->id === 0) { // User does not have a session we know about
				announce('Creating session');
				$this->db->insert(
					'php_session',
					array(
						'session_id' => $sessId,
						'user_id' => NULL,
						'date_created' => date('Y-m-d H:i:s'),
						'last_updated' => date('Y-m-d H:i:s'),
						'session_data' => $encSessArray
					), false, true
				);
			} else { // Updating a user's session
				announce('Updated session');
				$this->db->update(
					'php_session',
					array(
						'last_updated' => date('Y-m-d H:i:s'),
						'session_data' => $encSessArray
					),
					'session_id = ?',
					array($sessId), true
				);
			}
			return true;
		}

		// The close callback works like a destructor in classes and is executed after the session write
		// callback has been called. It is also invoked when session_write_close() is called. Return value
		// should be TRUE for success, FALSE for failure.
		function close() { 
			sessionLog('Close');
			return true; 
		}

		// This callback is executed when a session is destroyed with session_destroy() or with 
		// session_regenerate_id() with the destroy parameter set to TRUE. Return value should be TRUE for 
		// success, FALSE for failure.
		function destroy($sessId) {
			sessionLog('Destroy');
			return $this->db->delete("php_session", "session_id = ?", array($sessId), true);
		}

		// The garbage collector callback is invoked internally by PHP periodically in order to purge old session
		// data. The frequency is controlled by session.gc_probability and session.gc_divisor. The value of lifetime
		// which is passed to this callback can be set in session.gc_maxlifetime. Return value should be TRUE for
		// success, FALSE for failure.
		function gc($maxLifetime) {
			sessionLog('GC');
			announce('Garbage');
			$real_now = date('Y-m-d H:i:s');
	        $dt1 = strtotime("$real_now -$maxLifetime seconds");
	        $dt2 = date('Y-m-d H:i:s', $dt1);
	        return $this->db->delete("php_session", "last_updated < ?", array($dt2), true);
		}

		function __destruct() { @session_write_close(); }
	}
?>