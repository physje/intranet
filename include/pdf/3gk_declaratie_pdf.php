<?php
require('fpdf.php');

class PDF_3GK_Declaratie extends FPDF {
	# Page header
	function Header() {
    global $cfgMarge, $title;
    $breedte = $this->GetPageWidth();
        
		# Stel marges in
    $this->SetMargins($cfgMarge, $cfgMarge);

    # Arial bold 15
    $this->SetY(0);
    $this->SetX(0); 
    $this->SetFont('Arial','B',20);
    $this->SetFillColor(255,255,255);
    $this->SetDrawColor(43,21,59);
    $this->SetLineWidth(0.5);
    $this->Cell(75, 30, "", 'RB', 0, 'C', 1);
    $this->SetTextColor(255,255,255);
    $this->SetFillColor(43,21,59);
    $this->Cell($breedte-75, 30, "DECLARATIE", 'LB', 0, 'C', 1);

    # Logo
    $size = array(70, 10);
    $offsetX = $cfgMarge;
    $offsetY = $cfgMarge-10;
    $this->Image('../images/logoKoningsKerk.png',$offsetX - 20,$offsetY - 9,$size[0]);
       
    # Move to the right + Title
    $this->SetY($offsetY+5);
    $this->SetX($offsetX+$size[0]+10);    
    
    # Line break
    $this->Ln($size[1]+10);
	}

	// Page footer
	function Footer() {		
    global $cfgMarge, $title;
    
    $iconXOffset = 18;
    $iconYOffset = 11;
				
    $breedte = $this->GetPageWidth();
    $hoogte = $this->GetPageHeight();
		
    # 2 cm van de onderkant en lettertype
    $this->SetY(-30);
    $this->SetX(0);
    $this->SetFont('Helvetica','B',8);
    $this->SetTextColor(255,255,255);
    $this->SetFillColor(43,21,59);
    $this->Cell($breedte, 30, "", 0, 0, 'C', 1);

    # Maak contact informatie veld
    $this->SetY(-30);
    $this->SetX(0);
    $this->SetFillColor(140,25,116);
    $this->SetDrawColor(255,255,255);
    $this->SetLineWidth(0.5);
    $this->Cell(75, 30, "", 'R', 0, 'C', 1);
    $this->Image('../images/e-mail.png', ($cfgMarge - $iconXOffset), ($hoogte - ($iconYOffset + 6)), 4, 4);
    $this->Image('../images/adres.png', ($cfgMarge - $iconXOffset), ($hoogte - ($iconYOffset + 0)), 4, 4);

    $this->SetY(-(13+$iconYOffset));
    $this->SetX(($cfgMarge - $iconXOffset) + 5);
   
    $this->Cell(70, 7,'',0,0,'L');
    $this->Cell(120,7,'KONINGSKERK DEVENTER',0,0,'R');
    $this->Ln();
    $this->SetX(($cfgMarge - $iconXOffset) + 5);
    $this->Cell(70, 6,'penningmeester@3gk.nl',0,0,'L');
    $this->Ln();
    $this->SetX(($cfgMarge - $iconXOffset) + 5);
    $this->Cell(70, 6,'Marienburghstraat 4, 7415 BP Deventer',0,0,'L');
    $this->Cell(60, 6,'Pagina '.$this->PageNo().' van {nb}',0,0,'C'); 
    $this->Cell(60, 6,strftime("%A %d %B %Y"),0,0,'R');
	}
}

?>