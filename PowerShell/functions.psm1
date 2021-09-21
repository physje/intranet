# Lading functies die ik nodig kan hebben

function addUpdateMember {
	param([string]$DisplayName, [string]$FirstName, [string]$LastName, [string]$UserPrincipalName)
	
	$license = "koningskerkdeventer:O365_BUSINESS_ESSENTIALS"
	$bestaat = Get-MsolUser -UserPrincipalName $UserPrincipalName
	
	if(-Not $bestaat) {
		#Write-Host "Bestaat niet"
		New-MsolUser -DisplayName $DisplayName -FirstName $FirstName -LastName $LastName -UserPrincipalName $UserPrincipalName -UsageLocation NL -LicenseAssignment $license
	}
}


