<?php
set_time_limit(0); 

/* Base Url for Crawling */
$mainUrl="https://www.magicbricks.com/property-for-sale/residential-real-estate?proptype=Multistorey-Apartment,Builder-Floor-Apartment,Penthouse,Studio-Apartment,Residential-House,Villa&Locality=".$_POST['locality']."&cityName=Goa";

/* Get list of property Urls */
function crawlProperties($pageNo, $mainUrl) {
	global $crawledProprtyUrl;
	$content = file_get_contents($mainUrl.'&page='.$pageNo);
	$doc = new DOMDocument; 
	@$doc->loadHTML($content); 
	$list = $doc->getElementsByTagName('a');

	foreach($list as $value) 
	{
		$attrs = $value->attributes;
		if(preg_match("/https:\/\/www.magicbricks.com\/propertyDetails\/(.*)/i",$attrs->getNamedItem('href')->nodeValue))
			$crawledProprtyUrl[]=$attrs->getNamedItem('href')->nodeValue;
	}		
}

/* Get Property Details and write in file */
function getDataFromUrl($crawledProprtyUrl,$fileName) {	
	/* Create file for writing */
	$handle = fopen($fileName, 'w') or die('Cannot open file:  '.$fileName); 
	$data = 'Property;Address;Price;Area;Bedrooms;Bathrooms'.PHP_EOL;
	
	foreach($crawledProprtyUrl as $urlId => $url)
	{	
		$content = file_get_contents($url);
		$doc = new DOMDocument; 
		@$doc->loadHTML($content); 
		
		$priceNode = $doc->getElementById('priceSv'); 
		$price = preg_replace("/[\r\n]+/","",$priceNode->nodeValue);
		$propertyNode = $doc->getElementById('propUnitDesc');
		$property = preg_replace("/[\r\n]+/","",$propertyNode->nodeValue);
		$addres1 = $doc->getElementById('psmName');
		$addres2 = $doc->getElementById('locality');
		$addres3 = $doc->getElementById('city');
		$address = $addres1->getAttribute('value').",".$addres2->getAttribute('value').",".$addres3->getAttribute('value');
		$areaNode1 = $doc->getElementById('coveredArea');
		$areaNode2 = $doc->getElementById('coveredAreaUnit');		
		$area = preg_replace("/[\r\n]+/","",is_null($areaNode1)?"":$areaNode1->nodeValue)." ".preg_replace("/[\r\n]+/","",is_null($areaNode2)?"":$areaNode2->nodeValue);		
		$roomNode = $doc->getElementById('firstFoldDisplay');
		$roomValue = $roomNode->nodeValue;
		$rooms = preg_replace('/\s+/',',',$roomValue);
		$rooms = explode(",",$rooms);
		
		$data .= $property.";".$address.";".$price.";".$area.";".$rooms[2].";".$rooms[4].PHP_EOL;
		
	}
	fwrite($handle, $data);
	fclose($handle);
	
	/* Force Download */
	header ("Content-Type: application/download");
	header ("Content-Disposition: attachment; filename=".$fileName);
	header("Content-Length: " . filesize($fileName));
	$fp = fopen($fileName, "r");
	fpassthru($fp);
	fclose($fp);
}
	/* Default crawl first page data */
	$pageNo = 1;
	crawlProperties($pageNo, $mainUrl);
	
	//function crawlPages($mainUrl) {
	$content = file_get_contents($mainUrl);
	$doc = new DOMDocument; 
	@$doc->loadHTML($content); 
	$xpath = new DOMXpath($doc);
	$pages = $xpath->query("//a[contains(@href, '/property-for-sale/residential-real-estate/Page-')]");
	/* Crawl pages from page 2 */  
	foreach($pages as $page) {
		if(is_numeric($page->nodeValue)) {
			$pageNo = trim($page->nodeValue);
		crawlProperties($pageNo, $mainUrl);
		}
	}
	
	/* Get File Name */
	$fname = 'Property_Details_'.$_POST['locality'].'.txt';
	/* Get Data from all crawled property urls */
	getDataFromUrl($crawledProprtyUrl, $fname);	
?>