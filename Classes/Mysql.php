<?php
include_once('KKDConfig.php');
/**
 * Class om te communiceren met de MySQL-database
 */
class Mysql implements KKDConfig {
	public $connection;
	public $dataSet;
	
	protected $hostName;
	protected $userName;
	protected $passWord;
	protected $dbName;
	
	
		
	function __construct() {
		$this -> dbName = KKDConfig::dbName;
		$this -> hostName = KKDConfig::serverName;
		$this -> userName = KKDConfig::userName;
		$this -> passWord = KKDConfig::passCode;		
				
		return $this -> connect();		
	}
	
	
	
	/**
	 * @return mysqli Verbindt met de database en geeft de verbinding terug
	 */
	function connect() : mysqli {
		$mysqli = new mysqli($this->hostName , $this->userName , $this->passWord , $this->dbName);		
		$mysqli -> set_charset("utf8mb4");		
		$this -> connection = $mysqli;		
		
		return $this -> connection;
	}
	


	/**
	 * Voer SELECT-query uit
	 * @param string $sqlQuery Select-query die moet worden uitgevoerd
	 * @param bool $allwaysArray Moet het resultaat altijd in een multi-array worden teruggegeven, ook als er maar 1 resultaat is. Default = false
	 *
	 * @return array Resultaten van de query
	 */
	function select(string $sqlQuery, bool $allwaysArray = false) {		
		$results = mysqli_query($this->connection , $sqlQuery);
		$data = array();

        if(!$results) {
            return false;
        } elseif(mysqli_num_rows($results) == 1) {
			if($allwaysArray) {
				$data[] = mysqli_fetch_array($results);
			} else {
				$data = mysqli_fetch_array($results);
			}			
		} else {
            while($row = mysqli_fetch_array($results)) {
                $data[] = $row;
            } 			
		}

		return $data;
	}
		

	/**
	 * Voer query uit. Voor SELECT-query gebruik $this->select
	 * @param string $sqlQuery Query die moet worden uitgevoerd
	 * @return bool True als de query geslaagd is, anders False
	 */
	function query(string $sqlQuery) {
		$result = mysqli_query($this->connection , $sqlQuery);
		
		if(!$result) {
            return false;
		} else {
			return $result;
		}
	}
}
?>