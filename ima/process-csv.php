<?php 
/*****
 * Author: Ethan Gruber
 * Date August 2019
 * Function: Process Indianapolis Museum of Art spreadsheet into simple Kerameikos.org-compliant RDF
 *****/


//generate RDF
$writer = new XMLWriter();
$writer->openURI('ima.rdf');
//$writer->openURI('php://output');
$writer->startDocument('1.0','UTF-8');
$writer->setIndent(true);
//now we need to define our Indent string,which is basically how many blank spaces we want to have for the indent
$writer->setIndentString("    ");

$writer->startElement('rdf:RDF');
$writer->writeAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema#');
$writer->writeAttribute('xmlns:kid', "http://kerameikos.org/id/");
$writer->writeAttribute('xmlns:kon', "http://kerameikos.org/ontology#");
$writer->writeAttribute('xmlns:dcterms', "http://purl.org/dc/terms/");
$writer->writeAttribute('xmlns:foaf', "http://xmlns.com/foaf/0.1/");
$writer->writeAttribute('xmlns:geo', "http://www.w3.org/2003/01/geo/wgs84_pos#");
$writer->writeAttribute('xmlns:rdf', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
$writer->writeAttribute('xmlns:void', "http://rdfs.org/ns/void#");
$writer->writeAttribute('xmlns:crm', "http://www.cidoc-crm.org/cidoc-crm/");

$data = generate_json('ima.csv');

foreach ($data as $row){
	$writer->startElement('crm:E22_Man-Made_Object');
		$writer->writeAttribute('rdf:about', $row['URI']);
		$writer->startElement('dcterms:title');
			$writer->writeAttribute('xml:lang', 'en');
			$writer->text($row['TitMainTitle']);
		$writer->endElement();
		$writer->writeElement('dcterms:identifier', $row['TitAccessionNo']);
		
		if (strlen($row['Material URI']) > 0){
			$writer->startElement('crm:P45_consists_of');
				$writer->writeAttribute('rdf:resource', $row['Material URI']);
			$writer->endElement();
		}
		
		if (strlen($row['Technique URI']) > 0){
			$writer->startElement('crm:P32_used_general_technique');
				$writer->writeAttribute('rdf:resource', $row['Technique URI']);
			$writer->endElement();
		}
		
		if (strlen($row['Shape URI']) > 0){
			$writer->startElement('kon:hasShape');
				$writer->writeAttribute('rdf:resource', $row['Shape URI']);
			$writer->endElement();
		}
		
		//production
		if (strlen($row['Artist URI']) > 0 || strlen($row['Production Place URI']) > 0 || (strlen($row['CreEarliestDate']) > 0 && strlen($row['CreLatestDate']) > 0)){
			$writer->startElement('crm:P108i_was_produced_by');
				$writer->startElement('crm:E12_Production');
				if (strlen($row['Production Place URI']) > 0){
					$writer->startElement('crm:P7_took_place_at');
						$writer->writeAttribute('rdf:resource', $row['Production Place URI']);
					$writer->endElement();
				}
				if (strlen($row['Artist URI']) > 0){
					$writer->startElement('crm:P14_carried_out_by');
						$writer->writeAttribute('rdf:resource', $row['Artist URI']);
					$writer->endElement();
				}
				if (strlen($row['CreEarliestDate']) > 0 && strlen($row['CreLatestDate']) > 0){
					$writer->startElement('crm:P4_has_time-span');
						$writer->startElement('crm:E52_Time-Span');
							$writer->startElement('crm:P82a_begin_of_the_begin');
								$writer->writeAttribute('rdf:datatype', 'http://www.w3.org/2001/XMLSchema#gYear');
								$writer->text(number_pad($row['CreEarliestDate'], 4));
							$writer->endElement();
							$writer->startElement('crm:P82b_end_of_the_end');
								$writer->writeAttribute('rdf:datatype', 'http://www.w3.org/2001/XMLSchema#gYear');
								$writer->text(number_pad($row['CreLatestDate'], 4));
							$writer->endElement();
						$writer->endElement();
					$writer->endElement();
				}
				$writer->endElement();
			$writer->endElement();
		}
		
		//initiate DOMDocument call to harvest image URLs
		$html = file_get_contents($row['URI']);
		
		$dom = new DOMDocument();
		@$dom->loadHTML($html);
		$xpath = new DOMXpath($dom);
		
		$images = $xpath->query("//div[contains(@class, 'object-thumbnail')]/img");
		
		if ($images->length > 0){
			$imageURL = $images->item(0)->getAttribute('src');
			
			/*$writer->startElement('foaf:thumbnail');
				$writer->writeAttribute('rdf:resource', $imageURL);
			$writer->endElement();*/
			$writer->startElement('foaf:depiction');
				$writer->writeAttribute('rdf:resource', str_replace('_thumb', '_full', $imageURL));
			$writer->endElement();
		}
		
		//collection
		$writer->startElement('crm:P50_has_current_keeper');
			$writer->writeAttribute('rdf:resource', 'http://kerameikos.org/id/ima_newfields');
		$writer->endElement();
		
		//dataset
		$writer->startElement('void:inDataset');
			$writer->writeAttribute('rdf:resource', 'https://discovernewfields.org/');
		$writer->endElement();
	
	$writer->endElement();
	
	//echo "{$row['URI']}\n";
}

$writer->endElement();
$writer->flush();


/***** FUNCTIONS *****/
//pad integer value from Filemaker to create a year that meets the xs:gYear specification
function number_pad($number,$n) {
	if ($number > 0){
		$gYear = str_pad((int) $number,$n,"0",STR_PAD_LEFT);
	} elseif ($number < 0) {
		$bcNum = (int)abs($number);
		$gYear = '-' . str_pad($bcNum,$n,"0",STR_PAD_LEFT);
	}
	return $gYear;
}

function generate_json($doc){
	$keys = array();
	$geoData = array();
	
	$data = csvToArray($doc, ',');
	
	// Set number of elements (minus 1 because we shift off the first row)
	$count = count($data) - 1;
	
	//Use first row for names
	$labels = array_shift($data);
	
	foreach ($labels as $label) {
		$keys[] = $label;
	}
	
	// Bring it all together
	for ($j = 0; $j < $count; $j++) {
		$d = array_combine($keys, $data[$j]);
		$geoData[$j] = $d;
	}
	return $geoData;
}

// Function to convert CSV into associative array
function csvToArray($file, $delimiter) {
	if (($handle = fopen($file, 'r')) !== FALSE) {
		$i = 0;
		while (($lineArray = fgetcsv($handle, 4000, $delimiter, '"')) !== FALSE) {
			for ($j = 0; $j < count($lineArray); $j++) {
				$arr[$i][$j] = $lineArray[$j];
			}
			$i++;
		}
		fclose($handle);
	}
	return $arr;
}

?>