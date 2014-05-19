<?php
class db {
	private $db_host = 'localhost';
	private $db_username = 'root';
	private $db_password = '';
	private $db_name = 'sniffroo89294';
	private $conn = '';
	private $num = 0;
	private $res = '';
	private $my_query = '';
	private $operation = '';
	private $last_id = 0;
	private $result = array();
	private $limit;

	function __construct() {

		try {

			$this -> conn = new PDO('mysql:host=' . $this -> db_host . ';dbname=' . $this -> db_name, $this -> db_username, $this -> db_password);
			//echo 'mysql:host=' . $this -> db_host . ';dbname=' . $this -> db_name, $this -> db_name.' - '.$this -> db_password;
			$this -> conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			$log = new KLogger("logs/db.txt", KLogger::INFO);
			$log -> LogInfo($e -> getMessage());
			echo $e -> getMessage();
			//header('Location:/extras/extra.html');
		}

	}

	function startTransaction() {
		$this -> conn -> beginTransaction();
	}

	function commit() {
		$this -> conn -> commit();
	}

	function getLastId() {
		return $this -> conn -> lastInsertId();
	}

	function getNumRows() {
		return $this -> res -> rowCount();
	}
    
	function doQuery($query, $limit = 0) {
		$this -> limit = $limit;
		try {
			$this -> my_query = ltrim($query);
			$result = false;
			if ($limit != 0) {
				$this -> my_query = $this -> my_query . " LIMIT 0," . $limit . "";
			}
			$this -> res = $this -> conn -> query($this -> my_query);
			//evita l'azzeramento della risorsa
			if ($this -> res -> rowCount() == 0)
			{
				return false;
			}
			$this -> operation = strtoupper(substr($this -> my_query, 0, 6));
		} catch (PDOException $e) {
			$log = new KLogger("logs/db.txt", KLogger::INFO);
			$log -> LogInfo($e -> getMessage() . " - Query Error:" . $query . " - Script:" . $_SERVER['PHP_SELF']);
			//header('Location:/extras/extra.html');
		}
		return true;
	}

	public function getResult() {
		if ($this -> getNumRows() == 0)
			throw new Exception("Empty result");
		$result = array();
		if ($this -> limit == 1) {
			$result = $this -> res -> fetch(PDO::FETCH_ASSOC);
		} else {
			$this -> limit = $this -> getNumRows();
			for ($ct = 0; $ct < $this-> limit; $ct++) {
				$temp = $this -> res -> fetch(PDO::FETCH_ASSOC);
				array_push($result, $temp);
			}
		}
		return $result;
	}

}
?>
