<?php

	define('CACHE_DIR', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cache'));
	
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
function getData($ico, $cacheTTL = 86400)
{
	validateICO(validateICO($ico));
	if (!validateICO($ico)) {
		throw new Exception("`" . htmlspecialchars($ico) . "` není správné IČO.");
	}
	
	$cache_filename = CACHE_DIR . DIRECTORY_SEPARATOR . $ico . '.xml';
	
	if (is_file($cache_filename) && filemtime($cache_filename) + $cacheTTL > time()) {
		$xml_string = file_get_contents($cache_filename);
	} else {
		$xml_string = file_get_contents('https://wwwinfo.mfcr.cz/cgi-bin/ares/darv_std.cgi?ico=' . $ico);
		file_put_contents($cache_filename, $xml_string);
	}
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
	
// zde se posila JSON pro autoloader
if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
	header('Content-Type: application/json');
	
	try {
		$firma = getData($ico);
		die(json_encode([
			'status' => 'success',
			'data' => [
				'ICO' => $firma->getICO(),
				'nazev' => $firma->getNazev(),
				'adresa' => $firma->getAdresa(),
				'obec' => $firma->getObec(),
				'PSC' => $firma->getPSC(),
			]
		]));
	} catch (Exception $e) {
		die(json_encode([
			'status' => 'failed',
			'message' => $e->getMessage(),
		]));
	}
	die(json_encode([]));
}
?><!DOCTYPE html>
<html lang="cs">
<head>
	<meta charset="UTF-8">
	<title>Příklad na ARES</title>
	
	<script
		src="https://code.jquery.com/jquery-3.4.1.min.js"
		integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
		crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
	
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
	
	
	<script>
		var aresCalls = (function($){
			var
				aresLoad,
				printData,
				printError,
				init;
			
			printData = function (ICO, nazev, adresa, obec, PSC) {
				$('#aresResults').html('<hr class="col-xs-12"><dl class="row">' +
					'<dt class="col-sm-3">IČO</dt><dd class="col-sm-9">' + ICO + '</dd>' +
					'<dt class="col-sm-3">Název</dt><dd class="col-sm-9">' + nazev + '</dd>' +
					'<dt class="col-sm-3">Adresa</dt><dd class="col-sm-9">' + adresa + '</dd>' +
					'<dt class="col-sm-3">Obec</dt><dd class="col-sm-9">' + obec + '</dd>' +
					'<dt class="col-sm-3">PSČ</dt><dd class="col-sm-9">' + PSC + '</dd>' +
					'</dl>');
			}
			
			printError = function (message) {
				$('#aresResults').html('<hr class="col-xs-12"><p class="alert alert-danger" role="alert">' + message + '</p>');
				
			}
			
			aresLoad = function () {
				if ($('#ico').val().length == 8) {
					$.ajax({
						url: "<?php echo $_SERVER['SCRIPT_URI'] ?>",
						data: "ico=" + $('#ico').val(),
						cache: true,
						success: function (data) {
							if (data.status == 'success') {
								printData(data.data.ICO, data.data.nazev, data.data.adresa, data.data.obec, data.data.PSC)
							} else if (data.status == 'failed') {
								printError(data.message);
							} else {
								$('#aresResults').html('');
							}
						},
					});
				} else if ($('#ico').val().length > 6) {
					printError('IČO musí mít 8 číslic.');
				} else {
					$('#aresResults').html('');
				}
			};
			
			init = function(){
				$(document).on('keyup', '#ico', aresLoad);
			};
			
			$(document).ready(init);
			
			return {
				aresLoad: aresLoad,
				printData: printData,
				printError: printError,
			};
			
		}($));
	
	</script>

</head>
<body>
<div class="container center_div">

<form>
	<fieldset class="form-group">
		<legend>Zadej IČ:</legend>
		<input type="text" name="ico" id="ico" value="<?php echo htmlspecialchars($ico) ?>" class="form-control">
	</fieldset>
	<input type="submit" class="btn btn-primary">
</form>

	<div id="aresResults">

<?php	if ($ico):
	try {
		$firma = getData($ico);
		printf('<script> aresCalls.printData("%d", "%s", "%s", "%s", "%d"); </script>', $firma->getICO(), addslashes($firma->getNazev()), addslashes($firma->getAdresa()), addslashes($firma->getObec()), $firma->getPSC());
	} catch (Exception $e) {
		printf('<script> aresCalls.printError("%s"); </script>', $e->getMessage());
	}
endif; ?>
	</div>
</div>

</body>
</html>