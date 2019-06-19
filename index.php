<?php
error_reporting(E_ALL); ini_set('display_errors', 1);

/**
 * Class AresXMLElement
 *
 * Třída pro objekt reprezentující výsledek z ARES s připravenými metodami
 */
class AresXMLElement extends SimpleXMLElement
{
	public function getICO()
	{
		return $this->getXpathValue('/are:Ares_odpovedi/are:Odpoved/are:Zaznam/are:ICO');
	}
	public function getNazev()
	{
		return $this->getXpathValue('/are:Ares_odpovedi/are:Odpoved/are:Zaznam/are:Obchodni_firma');
	}
	public function getUlice()
	{
		return $this->getXpathValue('/are:Ares_odpovedi/are:Odpoved/are:Zaznam/are:Identifikace/are:Adresa_ARES/dtt:Nazev_ulice');
	}
	public function getCisloPopisne()
	{
		return $this->getXpathValue('/are:Ares_odpovedi/are:Odpoved/are:Zaznam/are:Identifikace/are:Adresa_ARES/dtt:Cislo_domovni');
	}
	public function getCisloOrientacni()
	{
		return $this->getXpathValue('/are:Ares_odpovedi/are:Odpoved/are:Zaznam/are:Identifikace/are:Adresa_ARES/dtt:Cislo_orientacni');
	}
	public function getPSC()
	{
		return $this->getXpathValue('/are:Ares_odpovedi/are:Odpoved/are:Zaznam/are:Identifikace/are:Adresa_ARES/dtt:PSC');
	}
	public function getObec()
	{
		return $this->getXpathValue('/are:Ares_odpovedi/are:Odpoved/are:Zaznam/are:Identifikace/are:Adresa_ARES/dtt:Nazev_obce');
	}
	public function getAdresa()
	{
		return $this->getUlice() . ' ' . $this->getCisloPopisne() . '/' . $this->getCisloOrientacni();
	}
	
	public function getXpathValue($xpath)
	{
		$result = $this->xpath($xpath);
		list(, $node) = each($result);
		
		return strval($node);
	}
}

/**
 * Stáhne data z ARES a vrátí objekt AresXMLElement
 * více zde http://wwwinfo.mfcr.cz/ares/ares_xml_standard.html.cz
 *
 * @param $ico
 * @return AresXMLElement
 *
 * @throws Exception
 */
function getData($ico)
{
	validateICO(validateICO($ico));
	if (!validateICO($ico)) {
		throw new Exception("`" . htmlspecialchars($ico) . "` není správné IČO.");
	}
	
	$xml_string = file_get_contents('https://wwwinfo.mfcr.cz/cgi-bin/ares/darv_std.cgi?ico=' . $ico);
	
	$xml = simplexml_load_string($xml_string, 'AresXMLElement');
	
	if (!$xml) {
		throw new Exception("Chyba čtení z databáze ARES.");
	}
	
	if ($xml->getICO() != $ico ) {
		throw new Exception("Záznam pro `" . htmlspecialchars($ico) . "` nebyl nalezen.");
	}
	
	return $xml;
}

/**
 * Validace IČO (je možné validovat i kontrolním výpočtem)
 * @param $ico
 * @return bool
 */
function validateICO($ico)
{
	return !!preg_match('/^[0-9]{8}$/', $ico);
}
	
$ico = isset($_REQUEST['ico']) ? $_REQUEST['ico'] : false;

?><!DOCTYPE html>
<html lang="cs">
<head>
	<meta charset="UTF-8">
	<title>Příklad na ARES</title>
	
	<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
	
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</head>
<body>
<div class="container center_div">

<form>
	<fieldset class="form-group">
		<legend>Zadej IČ:</legend>
		<input type="text" name="ico" value="<?php echo htmlspecialchars($ico) ?>" class="form-control">
	</fieldset>
	<input type="submit" class="btn btn-primary">
</form>

<?php	if ($ico): ?>
	<hr class="col-xs-12">
<?php
	try {
		$firma = getData($ico);

		
		echo '<dl class="row">';
		echo '<dt class="col-sm-3">IČO</dt><dd class="col-sm-9">' . $firma->getICO() . '</dd>';
		echo '<dt class="col-sm-3">Název</dt><dd class="col-sm-9">' . $firma->getNazev() . '</dd>';
		echo '<dt class="col-sm-3">Adresa</dt><dd class="col-sm-9">' . $firma->getAdresa() . '</dd>';
		echo '<dt class="col-sm-3">Obec</dt><dd class="col-sm-9">' . $firma->getObec() . '</dd>';
		echo '<dt class="col-sm-3">PSČ</dt><dd class="col-sm-9">' . $firma->getPSC() . '</dd>';
		echo '</dl>';
		
	} catch (Exception $e) {
		echo '<p class="alert alert-danger" role="alert">' . $e->getMessage() . '</p>';
	}
?>
<?php endif; ?>
</div>

</body>
</html>