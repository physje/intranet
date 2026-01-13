<?php

/**
 * [Description Rooster]
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
class Rooster {
    /**
     * @var int ID van het rooster
     */
    public int $id;

    /**
     * @var string Naam van het rooster
     */
    public string $naam;

    /**
     * @var int ID van het team dat voor dit rooster ingedeeld moet worden
     */
    public int $groep;

    /**
     * @var int ID van het team dat dit rooster beheert
     */
    public int $beheerder;

    /**
     * @var int ID van het team dat dit rooster mag plannen/vullen
     */
    public int $planner;

    /**
     * @var int Aantal velden in het rooster
     */
    public int $velden;

    /**
     * @var bool Moet er een reminder gestuurd worden of niet
     */
    public bool $reminder;

    /**
     * @var int Waarde van het gelijke diensten veld (0 = alle, 1 = per dag, 2 = ochtend + avond, 3 = ochtend, 4 = middag + avond, 5 = middag, 6 = avond)
     */
    public int $gelijk;

    /**
     * @var bool Moet de voorganger tijdens het maken van het rooster getoond worden of niet
     */
    public bool $voorganger;

    /**
     * @var bool Moet er een interne opmerking gemaakt kunnen worden of niet
     */
    public bool $opmerking;

    /**
     * @var bool Moet de ouder in CC worden meegenomen bij de reminder-mail
     */
    public bool $ouder;

    /**
     * @var bool Moet de partner in CC worden meegenomen bij de reminder-mail
     */
    public bool $partner;

    /**
     * @var bool Is dit een tekst-only rooster of niet
     */
    public bool $tekst;

    /**
     * @var int Hoeveek week van te voren moet er een alert gestuurd worden bij bijna aflopen van het rooster
     */
    public int $alert;

    /**
     * @var string Mailtekst voor de remindermail
     */
    public string $mail;

    /**
     * @var string Onderwerp van de remindermail
     */
    public string $onderwerp;

    /**
     * @var string Afzenderadres van de remindermail
     */
    public string $van;

    /**
     * @var string Naam van de afzender van de remindermail
     */
    public string $vanNaam;

    /**
     * @var string DateTime van de laatste wijzizing
     */
    public string $lastChange;

    function __construct($rooster = 0) {
        $this->id = 0;
        $this->naam = '';
        $this->groep = 0;
        $this->beheerder = 0;
        $this->planner = 0;
        $this->velden = 1;
        $this->reminder = true;
        $this->gelijk = 1;
        $this->voorganger = false;
        $this->opmerking = false;
        $this->ouder = false;
        $this->partner = false;
        $this->tekst = false;
        $this->alert = 0;
        $this->mail = '';
        $this->onderwerp = '';
        $this->van = '';
        $this->vanNaam = '';
        $this->lastChange = '';

        $data = array();

        if($rooster > 0) {
			$db = new Mysql();
			$data = $db->select("SELECT * FROM `roosters` WHERE `id` = ". $rooster);

            if(count($data) > 0) {
                $this->id = $rooster;
                $this->naam = urldecode($data['naam']);
                $this->groep = $data['groep'];
                $this->beheerder = $data['beheerder'];
                $this->planner = $data['planner'];
                $this->velden = $data['aantal'];
                $this->reminder = ($data['reminder'] == 1 ? true : false);
                $this->gelijk = $data['gelijke_diensten'];
                $this->voorganger = ($data['voorganger'] == 1 ? true : false);
                $this->opmerking = ($data['opmerking'] == 1 ? true : false);
                $this->ouder = ($data['ouder'] == 1 ? true : false);
                $this->partner = ($data['partner'] == 1 ? true : false);
                $this->tekst = ($data['text_only'] == 1 ? true : false);
                $this->alert = $data['alert'];
                $this->mail = urldecode($data['mail']);
                $this->onderwerp = urldecode($data['onderwerp']);
                $this->van = urldecode($data['mail_afzender']);
                $this->vanNaam = urldecode($data['naam_afzender']);
                $this->lastChange = $data['last_change'];
            }
        }
    }


    /**
     * @return array Geeft een array terug met ID's van alle roosters in de database
     */
    public static function getAllRoosters() {
        $db = new Mysql;
        $data = $db->select("SELECT `id` FROM `roosters` ORDER BY `naam`");

        return array_column($data, 'id');
    }


    /**
     * @param int $team ID van het team waarbij het rooster gezocht moet worden
     *
     * @return int ID van het bijbehorende rooster
     */
    public static function findRoosterByTeam($team) {
        $db = new Mysql;

        $data = $db->select("SELECT `id` FROM `roosters` WHERE `groep` = ". $team, true);
        if(count($data) > 0) {
            return $data['id'];
        } else {
            return 0;
        }
    }


    /**
     * Sla het rooster op in de MySQL-database
     * @return bool True indien gelukt, False indien mislukt
     */
    function save() {
        $db = new Mysql;
        
        $data['naam'] = urlencode($this->naam);
        $data['groep'] = $this->groep;
        $data['beheerder'] = $this->beheerder;
        $data['planner'] = $this->planner;
        $data['aantal'] = $this->velden;
        $data['reminder'] = ($this->reminder ? '1' : '0');
        $data['gelijke_diensten'] = $this->gelijk;
        $data['voorganger'] = ($this->voorganger ? '1' : '0');
        $data['opmerking'] = ($this->opmerking ? '1' : '0');
        $data['ouder'] = ($this->ouder ? '1' : '0');
        $data['partner'] = ($this->partner ? '1' : '0');
        $data['text_only'] = ($this->tekst ? '1' : '0');
        $data['alert'] = $this->alert;
        $data['mail'] = urlencode($this->mail);
        $data['onderwerp'] = urlencode($this->onderwerp);
        $data['mail_afzender'] = urlencode($this->van);
        $data['naam_afzender'] = urlencode($this->vanNaam);
        $data['last_change'] = time();

        if($this->id > 0) {
			foreach($data as $key => $value) {
				$set[] = "`$key` = '$value'";
			}
			$query = "UPDATE `roosters` SET ". implode(', ', $set) ." WHERE `id` = ". $this->id;			
        } else {
			$query = "INSERT INTO `roosters` (`". implode('`, `', array_keys($data)) ."`) VALUES ('". implode("', '", array_values($data)) ."')";
        }

        return $db->query($query);
    }

    /**
     * Verwijder het rooster
     * @return bool True indien gelukt, False indien mislukt
     */
    function delete() {
        $db = new Mysql();
        $query = "DELETE FROM `roosters` WHERE `id` = ". $this->id;

        return $db->query($query);
    }
}

?>