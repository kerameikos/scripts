<?php 
/*****
 * Author: Ethan Gruber
 * Date: March 2021
 * Function: transform a standard data object model into RDF comforming to the Linked/Art Kerameikos profile
 */

function object_to_rdf ($collection, $records, $places){
    $writer = new XMLWriter();
    $writer->openURI("{$collection}.rdf");
    //$writer->openURI('php://output');
    $writer->startDocument('1.0','UTF-8');
    $writer->setIndent(true);
    //now we need to define our Indent string,which is basically how many blank spaces we want to have for the indent
    $writer->setIndentString("    ");
    
    $writer->startElement('rdf:RDF');
    $writer->writeAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema#');
    $writer->writeAttribute('xmlns:crm', "http://www.cidoc-crm.org/cidoc-crm/");
    $writer->writeAttribute('xmlns:crmgeo', "http://www.ics.forth.gr/isl/CRMgeo/");
    $writer->writeAttribute('xmlns:crmsci', "http://www.ics.forth.gr/isl/CRMsci/");
    $writer->writeAttribute('xmlns:kon', "http://kerameikos.org/ontology#");
    $writer->writeAttribute('xmlns:dcterms', "http://purl.org/dc/terms/");
    $writer->writeAttribute('xmlns:foaf', "http://xmlns.com/foaf/0.1/");
    $writer->writeAttribute('xmlns:geo', "http://www.w3.org/2003/01/geo/wgs84_pos#");
    $writer->writeAttribute('xmlns:rdf', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
    $writer->writeAttribute('xmlns:rdfs', "http://www.w3.org/2000/01/rdf-schema#");
    $writer->writeAttribute('xmlns:skos', "http://www.w3.org/2004/02/skos/core#");
    $writer->writeAttribute('xmlns:void', "http://rdfs.org/ns/void#");
    
    //iterate through records    
    foreach ($records as $record){
        $writer->startElement('crm:E22_Man-Made_Object');
            $writer->writeAttribute('rdf:about', $record['uri']);
        
            //title
            $writer->startElement('crm:P1_is_identified_by');
                $writer->startElement('crm:E33_E41_Linguistic_Appellation');
                    $writer->writeElement('crm:P190_has_symbolic_content', $record['title']);
                    $writer->startElement('crm:P2_has_type');
                        $writer->writeAttribute('rdf:resource', 'http://vocab.getty.edu/aat/300404670');
                    $writer->endElement();
                $writer->endElement();
            $writer->endElement();
            
            //accession
            $writer->startElement('crm:P1_is_identified_by');
                $writer->startElement('crm:E42_Identifier');
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
                    $writer->startElement('crm:P45_consists_of');
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
                    
                    //place
                    if (array_key_exists('place', $record['production'])){
                        foreach ($record['production']['place'] as $uri){
                            $writer->startElement('crm:P7_took_place_at');
                                $writer->writeAttribute('rdf:resource', $uri);
                            $writer->endElement();
                        }
                    }
                    
                    //techniques
                    if (array_key_exists('techniques', $record)){
                        foreach($record['techniques'] as $uri){
                            $writer->startElement('crm:P32_used_general_technique');
                                $writer->writeAttribute('rdf:resource', $uri);
                            $writer->endElement();
                        }
                    }
                    
                    //period
                    if (array_key_exists('period', $record['production'])){
                        foreach ($record['production']['period'] as $uri){
                            $writer->startElement('crm:P10_falls_within');
                                $writer->writeAttribute('rdf:resource', $uri);
                            $writer->endElement();
                        }
                    }
                    
                    //artists
                    if (array_key_exists('carried_out_by', $record['production'])){
                        if (count($record['production']['carried_out_by']) > 1){
                            foreach ($record['production']['carried_out_by'] as $artist){
                                $writer->startElement('crm:P9_consists_of');
                                    $writer->startElement('crm:E12_Production');
                                        $writer->startElement('crm:P14_carried_out_by');
                                            structure_artist($writer, $artist);
                                        $writer->endElement();
                                    $writer->endElement();                                    
                                $writer->endElement();
                            }
                        } else {
                            //one artist
                            $writer->startElement('crm:P14_carried_out_by');
                                foreach ($record['production']['carried_out_by'] as $artist){
                                    structure_artist($writer, $artist);
                                }
                            $writer->endElement();
                        }
                    }
                                        
                    $writer->endElement();
                $writer->endElement();
            }
            
            //findspot
            if (array_key_exists('findspot', $record)){                
                $writer->startElement('crmsci:O19i_was_object_found_by');
                    $writer->startElement('crmsci:S19_Encounter_Event');
                        $writer->startElement('crm:P7_took_place_at');
                        //place
                        $writer->startElement('crm:E53_Place');
                            $writer->startElement('rdfs:label');
                                $writer->writeAttribute('xml:lang', 'en');
                                $writer->text($record['findspot']['label']);
                            $writer->endElement();
                        
                            $writer->startElement('crm:P89_falls_within');
                                $writer->writeAttribute('rdf:resource', $record['findspot']['uri']);
                            $writer->endElement();
                        $writer->endElement();
                        
                        //end place
                        $writer->endElement();
                    $writer->endElement();
                $writer->endElement();
            }
            
            //measurements
            if (array_key_exists('measurements', $record)){
                foreach($record['measurements'] as $k=>$array){
                    $writer->startElement('crm:P43_has_dimension');
                        $writer->startElement('crm:E54_Dimension');
                            switch($k){
                                case 'depth': 
                                    $type = 'http://vocab.getty.edu/aat/300072633';
                                    break;
                                case 'diameter':
                                    $type = 'http://vocab.getty.edu/aat/300055624';
                                    break;
                                case 'height': 
                                    $type = 'http://vocab.getty.edu/aat/300055644';
                                    break;
                                case 'width':
                                    $type = 'http://vocab.getty.edu/aat/300055647';                                    
                            }
                        
                            if (isset($type)){
                                $writer->startElement('crm:P2_has_type');
                                    $writer->writeAttribute('rdf:resource', $type);
                                $writer->endElement();
                            }
                            
                            $writer->writeElement('crm:P90_has_value', $array['value']);
                            
                            if ($array['units'] == 'cm'){
                                $writer->startElement('crm:P91_has_unit');
                                    $writer->writeAttribute('rdf:resource', 'http://vocab.getty.edu/aat/300379098');
                                $writer->endElement();
                            }
                        
                        $writer->endElement();
                    $writer->endElement();
                }
            }
            
            //images
            if (array_key_exists('images', $record)){
                foreach ($record['images'] as $image) {
                    $writer->startElement('crm:P138i_has_representation');
                        $writer->startElement('crm:E36_Visual_Item');
                            $writer->writeAttribute('rdf:about', $image['uri']);
                            if ($image['type'] == 'iiif'){
                                $writer->startElement('dcterms:conformsTo');
                                    $writer->writeAttribute('rdf:resource', 'http://iiif.io/api/image');
                                $writer->endElement();
                            } else {
                                $writer->writeElement('dcterms:format', 'image/jpeg');
                            }
                            
                        $writer->endElement();
                    $writer->endElement();
                }    
            }
            
            //IIIF Manifest
            if (array_key_exists('manifest', $record)){
                $writer->startElement('crm:P129i_is_subject_of');
                    $writer->startElement('crm:E73_Information_Objectcrm:E73_Information_Object');
                        $writer->writeAttribute('rdf:about', $record['manifest']);
                        $writer->writeElement('dcterms:format', 'application/ld+json;profile="http://iiif.io/api/presentation/2/context.json"');
                        $writer->startElement('dcterms:conformsTo');
                            $writer->writeAttribute('rdf:resource', 'http://iiif.io/api/presentation');
                        $writer->endElement();
                    $writer->endElement();
                $writer->endElement();
            }
            
            //void:inDataset
            $writer->startElement('void:inDataset');
                $writer->writeAttribute('rdf:resource', $record['dataset']);
            $writer->endElement();
        
        //end HMO
        $writer->endElement();        
    }
    
    //places 
    process_places($writer, $places);
    
    //end RDF file
    $writer->endElement();
    $writer->flush();
    
}

//transform the $places model into CIDOC-CRM place structure
function process_places($writer, $places){
    echo "Processing places.\n";
    
    //iterate through Wikidata places to create SKOS concepts
    foreach ($places as $place){
        $writer->startElement('crm:E53_Place');
            $writer->writeAttribute('rdf:about', $place['uri']);
            $writer->writeElement('rdfs:label', $place['label']);
        
        if (array_key_exists('parent', $place)){
            foreach ($place['parent'] as $parent){
                $writer->startElement('crm:P89_falls_within');
                    $writer->writeAttribute('rdf:resource', $parent);
                $writer->endElement();
            }
        }
        
        if (array_key_exists('closeMatch', $place)){
            foreach ($place['closeMatch'] as $match){
                $writer->startElement('skos:closeMatch');
                    $writer->writeAttribute('rdf:resource', $match);
                $writer->endElement();
            }
        }
        
        
        if (array_key_exists('wkt', $place)) {
            $writer->startElement('geo:location');
                $writer->writeAttribute('rdf:resource', $place['uri'] . '#this');
            $writer->endElement();
            $writer->startElement('crm:P168_place_is_defined_by');
                $writer->writeAttribute('rdf:resource', $place['uri'] . '#this');
            $writer->endElement();
        }
        
        //end nmo:Numismatic Object
        $writer->endElement();
        
        //geo:SpatialThing
        if (array_key_exists('wkt', $place)) {
            $writer->startElement('geo:SpatialThing');
                $writer->writeAttribute('rdf:about', $place['uri'] . '#this');
                $writer->startElement('rdf:type');
                    $writer->writeAttribute('rdf:resource', 'http://www.ics.forth.gr/isl/CRMgeo/SP5_Geometric_Place_Expression');
                $writer->endElement();
                $writer->startElement('crmgeo:Q9_is_expressed_in_terms_of');
                    $writer->writeAttribute('rdf:resource', 'http://www.wikidata.org/entity/Q215848');
                $writer->endElement();
            
                //display coordinates for parishes
                if (array_key_exists('wkt', $place)){
                    $writer->startElement('geo:lat');
                        $writer->writeAttribute('rdf:datatype', 'http://www.w3.org/2001/XMLSchema#decimal');
                        $writer->text($place['lat']);
                    $writer->endElement();
                    $writer->startElement('geo:long');
                        $writer->writeAttribute('rdf:datatype', 'http://www.w3.org/2001/XMLSchema#decimal');
                        $writer->text($place['lon']);
                    $writer->endElement();
                    $writer->startElement('crmgeo:asWKT');
                        $writer->writeAttribute('rdf:datatype', 'http://www.opengis.net/ont/geosparql#wktLiteral');
                        $writer->text($place['wkt']);
                    $writer->endElement();
                }
            
            $writer->endElement();
        }
    }
}

function structure_artist($writer, $artist){
    if (array_key_exists('uri', $artist)){
        $writer->writeAttribute('rdf:resource', $artist['uri']);
    } else {
        $writer->startElement($artist['type']);
            if ($artist['label']){
                $writer->writeElement('rdfs:label', $artist['label']);            
            }
            
            //influenced_by
            $writer->startElement('crm:P15_was_influenced_by');
                $writer->writeAttribute('rdf:resource', $artist['influenced_by']);
            $writer->endElement();        
        
        $writer->endElement();
    }
}

?>
