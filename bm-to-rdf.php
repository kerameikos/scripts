<?php
/*****
 * Author: Ethan Gruber
 * Date: March 2021
 * Function: Process the normalized British Museum Attic vase data into CIDOC CRM RDF/XML that conforms to the Linked Art profile + several
 * Kerameikos.org properties
 */

include 'object-to-rdf.php';

$data = generate_json('https://docs.google.com/spreadsheets/d/e/2PACX-1vRfJLFmk2LVER7T7xJPtYgLGbywE7kL0Bv5_WWChP6EQhOq_h7c20kloORsnfpAoe0kmTCeAIeBI0Hd/pub?output=csv');
$roles = generate_json('https://docs.google.com/spreadsheets/d/e/2PACX-1vRSbflL8axsyjaq4MTR6VoLsOleJOjaw1cbUbSYV7mmmVc7a2JLQnoHFuIfCi_t8c5M2SU1hFvmzI0b/pub?output=csv');

$records = array();
$places = array();

foreach($data as $row){
    //only include objects with a shape URI (baseline requirement)
    if (strlen($row['Shape URI']) > 0 && $row['Object type uncertain'] != 'TRUE'){
        $record = array();
        
        $title = $row['Object type'];
        $title .= (strlen($row['Person 1']) > 0 ? ' ' . $row['Person1 attr'] . ' ' . $row['Person 1'] : '');
        $title .= (strlen($row['Person 2']) > 0 ? ' and ' . $row['Person2 attr'] . ' ' . $row['Person 2'] : '');
        $title .= ' (' . $row['Reg number'] . ')';
        
        $record['uri'] = $row['ID'];
        $record['title'] = $title;
        $record['accession'] = $row['Reg number'];
        $record['collection'] = 'http://kerameikos.org/id/british_museum';
        
        $record['shape'] = $row['Shape URI'];
        
        //materials
        if (strlen($row['Material1 URI']) > 0 || strlen($row['Material2 URI']) > 0 || strlen($row['Material3 URI']) > 0){
            $record['materials'] = array();
            
            if (strlen($row['Material1 URI']) > 0 && !in_array($row['Material1 URI'], $record['materials'])){
                $record['materials'][] = $row['Material1 URI'];
            }
            if (strlen($row['Material2 URI']) > 0 && !in_array($row['Material2 URI'], $record['materials'])){
                $record['materials'][] = $row['Material2 URI'];
            }
            if (strlen($row['Material3 URI']) > 0 && !in_array($row['Material3 URI'], $record['materials'])){
                $record['materials'][] = $row['Material3 URI'];
            }
        }
        
        //techniques
        if (strlen($row['Technique1 URI']) > 0 || strlen($row['Technique2 URI']) > 0 || strlen($row['Technique3 URI']) > 0){
            $record['techniques'] = array();
            
            if (strlen($row['Technique1 URI']) > 0 && !in_array($row['Technique1 URI'], $record['techniques'])){
                $record['techniques'][] = $row['Technique1 URI'];
            }
            if (strlen($row['Technique2 URI']) > 0 && !in_array($row['Technique2 URI'], $record['techniques'])){
                $record['techniques'][] = $row['Technique2 URI'];
            }
            if (strlen($row['Technique3 URI']) > 0 && !in_array($row['Technique3 URI'], $record['techniques'])){
                $record['techniques'][] = $row['Technique3 URI'];
            }
        }
        
        //production
        $record['production'] = array();
        
        //time span
        if (is_numeric($row['Production Start']) && is_numeric($row['Production End'])){                        
            $record['production']['begin'] = number_pad($row['Production Start'], 4);
            $record['production']['end'] = number_pad($row['Production End'], 4);
        }
        
        //places
        if ($row['Production place uncertain'] != 'TRUE'){
            if (strlen($row['Place1 URI']) > 0){
                $record['production']['place'][] = $row['Place1 URI'];
            }
            if (strlen($row['Place2 URI']) > 0){
                $record['production']['place'][] = $row['Place2 URI'];
            }
        }
        
        //people
        if ($row['Person1 uncertain'] != 'TRUE'){
            $uri = $row['Person1 URI'];
            if (strlen($uri) > 0){
                $attr = $row['Person1 attr'];
                $artist = array();
                
                foreach ($roles as $role){
                    if ($role['label'] == $attr){
                        //evaluate a direct or influential link
                        if ($role['certainty note'] == 'influenced_by'){
                            $artist['type'] = 'crm:E21_Person';
                            $artist['label'] = $attr . ' ' . $row['Person 1'];
                            $artist['influenced_by'] = $uri;
                        } elseif ($role['certainty note'] == 'group influenced_by') {
                            $artist['type'] = 'crm:E74_Group';
                            $artist['label'] = $attr . ' ' . $row['Person 1'];
                            $artist['influenced_by'] = $uri;
                        } else {
                            $artist['uri'] = $uri;
                        }
                        
                        break;
                    }
                }
                $record['production']['carried_out_by'][$uri] = $artist;                
            }
        }
        
        if ($row['Person2 uncertain'] != 'TRUE'){
            $uri = $row['Person2 URI'];
            if (strlen($row['Person2 URI']) > 0){
                $attr = $row['Person2 attr'];
                $artist = array();
                
                foreach ($roles as $role){
                    if ($role['label'] == $attr){
                        //evaluate a direct or influential link
                        if ($role['certainty note'] == 'influenced_by'){
                            $artist['type'] = 'crm:E21_Person';
                            $artist['label'] = $attr . ' ' . $row['Person 2'];
                            $artist['influenced_by'] = $uri;
                        } elseif ($role['certainty note'] == 'group influenced_by') {
                            $artist['type'] = 'crm:E74_Group';
                            $artist['label'] = $attr . ' ' . $row['Person 2'];
                            $artist['influenced_by'] = $uri;
                        } else {
                            $artist['uri'] = $uri;
                        }
                        
                        break;
                    }
                }
                $record['production']['carried_out_by'][$uri] = $artist;
            }
        }
        if ($row['Person3 uncertain'] != 'TRUE'){
            $uri = $row['Person3 URI'];
            if (strlen($uri) > 0){
                $attr = $row['Person3 attr'];
                $artist = array();
                
                foreach ($roles as $role){
                    if ($role['label'] == $attr){
                        //evaluate a direct or influential link
                        if ($role['certainty note'] == 'influenced_by'){
                            $artist['type'] = 'crm:E21_Person';
                            $artist['label'] = $attr . ' ' . $row['Person 3'];
                            $artist['influenced_by'] = $uri;
                        } elseif ($role['certainty note'] == 'group influenced_by') {
                            $artist['type'] = 'crm:E74_Group';
                            $artist['label'] = $attr . ' ' . $row['Person 3'];
                            $artist['influenced_by'] = $uri;
                        } else {
                            $artist['uri'] = $uri;
                        }
                        
                        break;
                    }
                }
                $record['production']['carried_out_by'][$uri] = $artist;                
            }
        }
        
        //periods
        if (strlen($row['Period1 URI']) > 0){
            $record['production']['period'][] = $row['Period1 URI'];
        }
        
        if (strlen($row['Period2 URI']) > 0){
            $record['production']['period'][] = $row['Period2 URI'];
        }
        
        if (strlen($row['Period3 URI']) > 0){
            $record['production']['period'][] = $row['Period3 URI'];
        }        
        //end production
        
        //findspot
        if ($row['Findspot uncertain'] != 'TRUE' && strlen($row['Gazetteer URI']) > 0){
            $record['findspot']['label'] = $row['Find spot 1'];
            $record['findspot']['uri'] = $row['Gazetteer URI'];
            
            //perform iterative place lookup on the Wikidata SPARQL endpoint
            if (!array_key_exists($row['Gazetteer URI'], $places)){
                echo "Processing {$row['Gazetteer URI']}\n";
                $place = query_wikidata($row['Gazetteer URI']);
                
                if (isset($place['uri'])){
                    $places[$place['uri']] = $place;                    
                }
            }
        }
        
        if (strlen($row['Image']) > 0){
            $pieces = explode('/', $row['Image']);
            $filename = str_replace('.jpg', '.ptif', str_replace('preview_', '', $pieces[9]));
            $record['images'][] = array("uri"=>"https://media.britishmuseum.org/iiif/Repository/Documents/{$pieces[6]}/{$pieces[7]}/{$pieces[8]}/{$filename}", "type"=>"iiif");
        }
        
        $record['dataset'] = 'https://www.britishmuseum.org/';
        
        $records[] = $record;
    }
}


//output RDF from parsed data object
object_to_rdf('bm', $records, $places);

/***** FUNCTIONS *****/
function parse_sparql_response($xml){
    GLOBAL $places;
    
    $place = array('uri'=>null, 'closeMatch'=>array(), 'parent'=>array());
    
    $xmlDoc = new DOMDocument();
    $xmlDoc->loadXML($xml);
    $xpath = new DOMXpath($xmlDoc);
    $xpath->registerNamespace('res', 'http://www.w3.org/2005/sparql-results#');
    
    $results = $xpath->query("//res:result");
    
    foreach ($results as $result){
        foreach ($result->getElementsByTagNameNS('http://www.w3.org/2005/sparql-results#', 'binding') as $binding){
            if ($binding->getAttribute('name') == 'place'){
                $uri = $binding->getElementsByTagNameNS('http://www.w3.org/2005/sparql-results#', 'uri')->item(0)->nodeValue;
                
                $place['uri'] = $uri;
            } elseif ($binding->getAttribute('name') == 'placeLabel'){
                $place['label'] = $binding->getElementsByTagNameNS('http://www.w3.org/2005/sparql-results#', 'literal')->item(0)->nodeValue;
            } elseif ($binding->getAttribute('name') == 'coord'){
                //attach coordinates for the ordnance survey lookups only
                $coord = $binding->getElementsByTagNameNS('http://www.w3.org/2005/sparql-results#', 'literal')->item(0)->nodeValue;
                
                $place['wkt'] = $coord;
                
                //parse WKT into lat/long
                $pieces = explode(' ', str_replace('Point(', '', str_replace(')', '', $coord)));
                
                $place['lat'] = $pieces[1];
                $place['lon'] = $pieces[0];
                
            } elseif ($binding->getAttribute('name') == 'parent'){
                $parentURI = $binding->getElementsByTagNameNS('http://www.w3.org/2005/sparql-results#', 'uri')->item(0)->nodeValue;
                if (!in_array($parentURI, $place['parent'])){
                    $place['parent'][] = $parentURI;
                    
                    //parse the hierarchy and add a new place if it doesn't exist already
                    if (!array_key_exists($parentURI, $places)){
                        $parent = query_wikidata($parentURI);
                        $places[$parentURI] = $parent;
                    }
                }
            } elseif ($binding->getAttribute('name') == 'parentLabel'){
                //ignore this
            } else {
                $match = $binding->getElementsByTagNameNS('http://www.w3.org/2005/sparql-results#', 'uri')->item(0)->nodeValue;
                if (!in_array($match, $place['closeMatch'])){
                    $place['closeMatch'][] = $match;
                }
            }
        }
    }
    
    return $place;
}

function query_wikidata($uri){
    $query = 'PREFIX bd:  <http://www.bigdata.com/rdf#>
PREFIX wd: <http://www.wikidata.org/entity/>
PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wikibase:    <http://wikiba.se/ontology#>
SELECT ?place ?placeLabel ?tgn ?geonames ?pleiades ?parent ?coord WHERE {
  BIND (<%ID%> as ?place)
  OPTIONAL {?place wdt:P1667 ?tgnid .
  	BIND (uri(concat("http://vocab.getty.edu/tgn/", ?tgnid)) as ?tgn)}
  OPTIONAL {?place wdt:P1566 ?geonamesid .
  	BIND (uri(concat("https://sws.geonames.org/", ?geonamesid, "/")) as ?geonames)}
  OPTIONAL {?place wdt:P1584 ?pleiadesid .
  	BIND (uri(concat("https://pleiades.stoa.org/places/", ?pleiadesid)) as ?pleiades)}
  OPTIONAL {?place wdt:P131 ?parent}
  OPTIONAL {?place p:P625/ps:P625 ?coord}
  SERVICE wikibase:label {
	bd:serviceParam wikibase:language "en"
  }
}';
    
    $url = "https://query.wikidata.org/sparql?query=" . urlencode(str_replace('%ID%', $uri, $query));
    
    
    $ch = curl_init($url);
    #curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "PHP/Ethan Gruber" );
    curl_setopt($ch, CURLOPT_HTTPHEADER,array (
        "Accept: application/sparql-results+xml"
    ));
    
    $output = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    return parse_sparql_response($output);
}


function number_pad($number,$n) {
    if ($number > 0){
        $gYear = str_pad((int) $number,$n,"0",STR_PAD_LEFT);
    } elseif ($number < 0) {
        $gYear = '-' . str_pad((int) abs($number),$n,"0",STR_PAD_LEFT);
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