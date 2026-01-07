<?php
require('fpdf.php');

class PDF_3GK extends FPDF {
	# Page header
	function Header() {
    global $cfgMarge, $title;
    global $ScriptURL;

		# Stel marges in
    $this->SetMargins($cfgMarge, $cfgMarge);

    # Logo
    $size = array(70, 10);
    $offsetX = $cfgMarge;
    $offsetY = $cfgMarge-10;
    $this->Image($ScriptURL.'images/logoKoningsKerk.png',$offsetX,$offsetY,$size[0]);

    # Arial bold 15
    $this->SetFont('Arial','B',15);

    # Move to the right + Title
    $this->SetY($offsetY+5);
    $this->SetX($offsetX+$size[0]+10);
    $this->Cell(0,$size[1],$title,0,0,'C');

    # Line break
    $this->Ln($size[1]+10);
	}

	// Page footer
	function Footer() {		
		global $cfgMarge, $title;
				
    $breedte = $this->GetPageWidth();
		
    # 2 cm van de onderkant en lettertype
    $this->SetY(-20);
    $this->SetFont('Arial','',6);

    # Printdatum, titel en paginanummers
    $this->Cell(30,10,date("l d F Y"),0,0,'L');
    $this->Cell(($breedte-(2*(30+$cfgMarge))),10,'KONINGSKERK DEVENTER',0,0,'C');
		$this->Cell(30,10,'Pagina '.$this->PageNo().' van {nb}',0,0,'R');

	}
}

?>