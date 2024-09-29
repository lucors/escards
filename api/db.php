<?php
	class DBConnection {
		protected $username;
		protected $password;
		protected $hostname;
		protected $dbname;
		public $mysqli = null;
		public $stmt = null;
		public $result = null;

		function __construct($host = null, $user = null, $pass = null, $dbname = null){
			$this->config($host, $user, $pass, $dbname);
            $this->connect();
		}

		static public function isMysqliResult($some){
			if (is_null($some)){
                return false;
			}
            return ($some instanceof mysqli_result) ? true : false;
		}
		static public function isResultIterable($some){
			if (DBConnection::isMysqliResult($some)){
				if ($some->num_rows != 0){
					return true;
				}
			}
			return false;
		}
		static public function isResultValid($some){
			if (!is_null($some)){
				if (DBConnection::isResultIterable($some)){
					return true;
				}
				return ($some === true) ? true : false;
			}
			return false;
		} 

		public function config($host, $user, $pass, $dbname){
			$this->hostname = $host;
			$this->username = $user;
			$this->password = $pass;
			$this->dbname = $dbname;
		}
		public function connect(){
			$this->mysqli = @new mysqli($this->hostname, $this->username, $this->password, $this->dbname);
		}
		public function disconnect($forced = true){
			@$this->mysqli->close();
			$this->mysqli = null;
		}
		public function query($query){
			$this->result = $this->mysqli->query($query);
			return $this->result;
		}
		public function prepare($query){
			$this->stmt = $this->mysqli->prepare($query);
			return $this->stmt;
		}
	}
?>