<?php
/*****
 * Author: Ethan Gruber
 * Date: March 2021
 * Function: Process the normalized Fitzwilliam Attic vase data into CIDOC CRM RDF/XML that conforms to the Linked Art profile + several
 * Kerameikos.org properties
 */

include 'object-to-rdf.php';

$data = generate_json('https://docs.google.com/spreadsheets/d/e/2PACX-1vStJLFPg4mCXCDjBwKvP7dn8iU1S4EdBMtYKGkJf0vIipuxaquGgJBckTO1D56ybo9-PKBxolKI1psy/pub?output=csv');

$records = array();
$places = array();

foreach($data as $row){
    //only include objects with a shape URI (baseline requirement)
    if (strlen($row['Shape URI']) > 0 && $row['Shape uncertain'] != 'TRUE'){
        $record = array();
        
        $title = $row['desc'];
        $title .= (strlen($row['artist']) > 0 ? ' by ' . $row['artist'] : '');
        $title .= ' (' . $row['accession'] . ')';
        
        $record['uri'] = $row['uri'];
        $record['title'] = $title;
        $record['accession'] = $row['accession'];
        $record['collection'] = 'http://kerameikos.org/id/fitzwilliam_museum';
        
        $record['shape'] = $row['Shape URI'];
        
        //materials
        if (strlen($row['Material URI']) > 0){
            $record['materials'] = array();
            
            if (strlen($row['Material URI']) > 0 && !in_array($row['Material URI'], $record['materials'])){
                $record['materials'][] = $row['Material URI'];
            }
        }
        
        //techniques
        if (strlen($row['Technique URI']) > 0){
            $record['techniques'] = array();
            
            if (strlen($row['Technique URI']) > 0 && !in_array($row['Technique URI'], $record['techniques'])){
                $record['techniques'][] = $row['Technique URI'];
            }
        }
        
        //production
        $record['production'] = array();
        
        //time span
        if (is_numeric($row['fromDate']) && is_numeric($row['toDate'])){                        
            $record['production']['begin'] = number_pad($row['fromDate'], 4);
            $record['production']['end'] = number_pad($row['toDate'], 4);
        }
        
        //places
        if (strlen($row['Place URI']) > 0){
            $record['production']['place'][] = $row['Place URI'];
        }
        
        //people
        if (strlen($row['Artist URI']) > 0){
            $uri = $row['Artist URI'];
            $artist = array();
            $artist['uri'] = $uri;
            
            $record['production']['carried_out_by'][$uri] = $artist;
        }
        
        //periods
        if (strlen($row['Period URI']) > 0){
            $record['production']['period'][] = $row['Period URI'];
        }      
        //end production
        
        //findspot
        if ($row['findspot uncertain'] != 'TRUE' && strlen($row['Findspot URI']) > 0){
            $record['findspot']['label'] = $row['findspot'];
            $record['findspot']['uri'] = $row['Findspot URI'];
            
            //perform iterative place lookup on the Wikidata SPARQL endpoint
            if (!array_key_exists($row['Findspot URI'], $places)){
                echo "Processing {$row['Findspot URI']}\n";
                $place = query_wikidata($row['Findspot URI']);
                
                if (isset($place['uri'])){
                    $places[$place['uri']] = $place;                    
                }
            }
        }
        
        //measurements
        if (strlen($row['depth']) > 0 || strlen($row['diameter']) > 0 || strlen($row['height']) > 0 || strlen($row['width']) > 0){
            $record['measurements'] = array();
            
            if (strlen($row['depth']) > 0){
                $record['measurements']['depth'] = array('value'=>$row['depth'], 'units'=>'cm');
            }
            if (strlen($row['diameter']) > 0){
                $record['measurements']['diameter'] = array('value'=>$row['diameter'], 'units'=>'cm');
            }
            if (strlen($row['height']) > 0){
                $record['measurements']['height'] = array('value'=>$row['height'], 'units'=>'cm');
            }
            if (strlen($row['width']) > 0){
                $record['measurements']['width'] = array('value'=>$row['width'], 'units'=>'cm');
            }
        }
        
        //images
        if (strlen($row['images']) > 0){
            $images = explode('|', $row['images']);
            
            foreach($images as $image){
                $record['images'][] = array("uri"=>$image, "type"=>"jpeg");
            }
            
            
        }
        
        //manifest
        if (strlen($row['IIIF Manifest']) > 0){
            $record['manifest'] = $row['IIIF Manifest'];
        }
        
        $record['dataset'] = 'https://www.fitzmuseum.cam.ac.uk/';
        
        $records[] = $record;
    }
}

//var_dump($records);


//output RDF from parsed data object
object_to_rdf('fitzwilliam', $records, $places);

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