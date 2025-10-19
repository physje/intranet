<?php

include 'Dbconfig.php';

/**
 * Class om te communiceren met de MySQL-database
 */
class Mysql extends Dbconfig {
	public $connection;
	public $dataSet;
	
	protected $hostName;
	protected $userName;
	protected $passWord;
	protected $dbName;
	
	
		
	function __construct() {
		$dbPara = new Dbconfig();
		$this -> dbName = $dbPara -> dbName;
		$this -> hostName = $dbPara -> serverName;
		$this -> userName = $dbPara -> userName;
		$this -> passWord = $dbPara ->passCode;		
		$dbPara = NULL;
		
		#$this -> connection = NULL;
		#$this -> dataSet = NULL;
		
		return $this -> connect();
	}
	
	
	
	/**
	 * @return mysqli Verbindt met de database en geeft de verbinding terug
	 */
	function connect() : mysqli {
		$mysqli = new mysqli($this->hostName , $this->userName , $this->passWord , $this->dbName);		
		$this -> connection = $mysqli;
		
		return $this -> connection;
	}
	

	// function disconnect() {
	// 	#$this -> connection->close();
		
	// 	$this -> connection = NULL;
	// 	$this -> dataSet = NULL;
	// 	$this -> hostName = NULL;
	// 	$this -> userName = NULL;
	// 	$this -> passWord = NULL;
	// 	$this -> dbName = NULL;		
	// }
	
	

	/**
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
		
	
	// function selectAll($tableName)  {
	// 	$sqlQuery = 'SELECT * FROM '.$tableName;				
	// 	return $this -> select($sqlQuery);
	// }



	/**
	 * @param string $sqlQuery Query die moet worden uitgevoerd
	 * 
	 * @return bool True als de query geslaagd is, anders False
	 */
	function query(string $sqlQuery) {
		$result = mysqli_query($this->connection , $sqlQuery);
		
		if(!$result) {
            return false;
		} else {
			return true;
		}
	}
}