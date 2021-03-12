<?php
/*****
 * Author: Ethan Gruber
 * Date: March 2021
 * Function: Process the normalized British Museum Attic vase data into CIDOC CRM RDF/XML that conforms to the Linked Art profile + several
 * Kerameikos.org properties
 */

$data = generate_json('https://docs.google.com/spreadsheets/d/e/2PACX-1vRfJLFmk2LVER7T7xJPtYgLGbywE7kL0Bv5_WWChP6EQhOq_h7c20kloORsnfpAoe0kmTCeAIeBI0Hd/pub?output=csv');

$records = array();

foreach($data as $row){
    //only include objects with a shape URI (baseline requirement)
    if (strlen($row['Shape URI']) > 0 && $row['Object type uncertain'] != 'TRUE'){
        $record = array();
        
        $title = $row['Reg number'];
        
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
        
        if (is_numeric($row['Production Start']) && is_numeric($row['Production End'])){
                        
            $record['production']['begin'] = number_pad($row['Production Start'], 4);
            $record['production']['end'] = number_pad($row['Production End'], 4);
        }
        
        $records[] = $record;
    }
}


//output RDF from parsed data object
object_to_rdf($records);




/***** FUNCTIONS *****/
function object_to_rdf ($records){
    $writer = new XMLWriter();
    //$writer->openURI("{$collection}-{$project}.rdf");
    $writer->openURI('php://output');
    $writer->startDocument('1.0','UTF-8');
    $writer->setIndent(true);
    //now we need to define our Indent string,which is basically how many blank spaces we want to have for the indent
    $writer->setIndentString("    ");
    
    $writer->startElement('rdf:RDF');
    $writer->writeAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema#');
    $writer->writeAttribute('xmlns:crm', "http://www.cidoc-crm.org/cidoc-crm/");
    $writer->writeAttribute('xmlns:crmgeo', "http://www.ics.forth.gr/isl/CRMgeo/");
    $writer->writeAttribute('xmlns:crmsci', "http://www.ics.forth.gr/isl/CRMsci");
    $writer->writeAttribute('xmlns:kon', "http://kerameikos.org/ontology#");
    $writer->writeAttribute('xmlns:dcterms', "http://purl.org/dc/terms/");
    $writer->writeAttribute('xmlns:foaf', "http://xmlns.com/foaf/0.1/");
    $writer->writeAttribute('xmlns:geo', "http://www.w3.org/2003/01/geo/wgs84_pos#");
    $writer->writeAttribute('xmlns:rdf', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
    $writer->writeAttribute('xmlns:void', "http://rdfs.org/ns/void#");
    
    //iterate through records    
    foreach ($records as $record){
        $writer->startElement('crm:E22_Man-Made_Object');
            $writer->writeAttribute('rdf:about', $record['uri']);
        
            //title
            $writer->startElement('rm:P1_is_identified_by');
                $writer->startElement('crm:E33_E41_Linguistic_Appellation');
                    $writer->writeElement('crm:P190_has_symbolic_content', $record['title']);
                    $writer->startElement('crm:P2_has_type');
                        $writer->writeAttribute('rdf:resource', 'http://vocab.getty.edu/aat/300404670');
                    $writer->endElement();
                $writer->endElement();
            $writer->endElement();
            
            //accession
            $writer->startElement('rm:P1_is_identified_by');
                $writer->startElement('crm:E33_E41_Linguistic_Appellation');
                    $writer->writeElement('crm:P190_has_symbolic_content', $record['accession']);
                    $writer->startElement('crm:P2_has_type');
                        $writer->writeAttribute('rdf:resource', 'http://vocab.getty.edu/aat/300312355');
                    $writer->endElement();
                $writer->endElement();
            $writer->endElement();
            
            $writer->startElement('crm:P50_has_current_keeper');
                $writer->writeAttribute('rdf:resource', $record['collection']);
            $writer->endElement();
            
            //typological attributes
            $writer->startElement('kon:hasShape');
                $writer->writeAttribute('rdf:resource', $record['shape']);
            $writer->endElement();
            
            if (array_key_exists('materials', $record)){
                foreach($record['materials'] as $uri){
                    $writer->startElement('crm:P45_consists_ofcrm:P45_consists_of');
                        $writer->writeAttribute('rdf:resource', $uri);
                    $writer->endElement();
                }
            }
            
            if (array_key_exists('techniques', $record)){
                foreach($record['techniques'] as $uri){
                    $writer->startElement('crm:P32_used_general_technique');
                        $writer->writeAttribute('rdf:resource', $uri);
                    $writer->endElement();
                }
            }
            
            //production
            if (array_key_exists('production', $record)){
                $writer->startElement('crm:P108i_was_produced_by');
                    $writer->startElement('crm:E12_Production');
                    
                    //date range
                    if (array_key_exists('begin', $record['production']) && array_key_exists('end', $record['production'])){
                        $writer->startElement('crm:P4_has_time-span');
                            $writer->startElement('crm:E52_Time-Span');
                                $writer->startElement('crm:P82a_begin_of_the_begin');
                                    $writer->writeAttribute('rdf:datatype', 'http://www.w3.org/2001/XMLSchema#gYear');
                                    $writer->text($record['production']['begin']);
                                $writer->endElement();
                                $writer->startElement('crm:P82b_end_of_the_end');
                                    $writer->writeAttribute('rdf:datatype', 'http://www.w3.org/2001/XMLSchema#gYear');
                                    $writer->text($record['production']['end']);
                                $writer->endElement();
                            $writer->endElement();
                        $writer->endElement();
                    }
                    
                    //period
                    
                    //artists
                                        
                    $writer->endElement();
                $writer->endElement();
            }
        
        //end HMO
        $writer->endElement();
    }
    
    //end RDF file
    $writer->endElement();
    $writer->flush();
    
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