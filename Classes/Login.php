<?php

/**
 * Voor het loggen van login-locaties van een gebruiker.
 * 
 * Gebruikt voor het al dan niet vragen van MFA-code
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */

class Login {
    public int $id;
    public int $lid;
    public string $ip;
    public string $agent;
    public string $tijdstip;

    function __construct($id = 0) {
        $this->id = $id;

        if($id != 0) {
            $db = new Mysql();
			$data = $db->select("SELECT * FROM `logins` WHERE `id` = ". $this->id);
            
            $this->lid = $data['lid'];
            $this->ip = $data['ip'];
            $this->agent = $data['agent'];
            $this->tijdstip = $data['tijd'];
        } else {            
            $this->lid = 0;
            $this->ip = '';
            $this->agent = '';
            $this->tijdstip = date('Y-m-d H:i:s');
        }
    }

    /**
	 * Sla het Login-object op in de database
	 * @return bool Succesvol of niet
	 */
	function save() {
		$db = new Mysql;
		$data = $set = array();

		$data['lid'] = $this->lid;
        $data['ip'] = $this->ip;
        $data['agent'] = $this->agent;
        $data['tijd'] = $this->tijdstip;
		
		foreach($data as $key => $value) {
			$set[] = "`$key`='$value'";
		}

		if($this->id > 0) {
            foreach($data as $key => $value) {
                $set[] = "`$key` = '$value'";
            }
            $sql = "UPDATE `logins` SET ". implode(', ', $set) ." WHERE `id` = ". $this->id;			
        } else {
            $sql = "INSERT INTO `logins` (`". implode('`, `', array_keys($data)) ."`) VALUES ('". implode("', '", array_values($data)) ."')";			
        }

		return $db -> query($sql);
	}

    static function getLogins($lid, $ip, $start, $eind) {
        $db = new Mysql;

        $where = array();

        if($start > 0 && $eind > 0) {
            $where[] = "`tijd` BETWEEN '$start' AND '$eind'";
        }

        if($lid != '') {
            $where[] = "`lid` = $lid";
        }
        
        if($ip != '') {
            $where[] = "(`ip` like '%$ip%' OR `ip` like '$ip%' OR `ip` like '%$ip')";
        }

        $sql		= "SELECT `id` FROM `logins` WHERE ". implode(' AND ', $where);

        $data = $db -> select($sql, true);

        return array_column($data, 'id');
    }



}
?>