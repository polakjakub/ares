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
</head>
<body>

<form>
	<fieldset>
		<legend>Zadej IČ:</legend>
		<input type="text" name="ico" value="<?php echo htmlspecialchars($ico) ?>">
		<input type="submit">
	</fieldset>
</form>

<?php	if ($ico): ?>
<?php
	try {
		$firma = getData($ico);

		echo '<dl>';
		echo '<dt>IČO</dt><dd>' . $firma->getICO() . '</dd>';
		echo '<dt>Název</dt><dd>' . $firma->getNazev() . '</dd>';
		echo '<dt>Adresa</dt><dd>' . $firma->getAdresa() . '</dd>';
		echo '<dt>Obec</dt><dd>' . $firma->getObec() . '</dd>';
		echo '<dt>PSČ</dt><dd>' . $firma->getPSC() . '</dd>';
		echo '</dl>';
		
	} catch (Exception $e) {
		echo '<p style="color:red">' . $e->getMessage() . '</p>';
	}
?>
<?php endif; ?>

</body>
</html>