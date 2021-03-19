<?php 
/***** 
 * Author: Ethan Gruber
 * Date: March 2021
 * Function: Harvest JSON from the Fitzwilliam API and generate a CSV file that will be reconciled in OpenRefine
 */

$objects;
$from = 0;
$size = 100;

query_page($from, $size);

$data = array();

$headings = array('uri','desc','shape','accession','fromDate','toDate','artists','technique','period','place','material','findspot','depth','diameter','height','width','images');
//reorder the $objects and fill in missing values for CSV export

foreach ($objects as $object){
    $new = array();
    foreach($headings as $heading){        
        if (array_key_exists($heading, $object)){
            $new[$heading] = $object[$heading];
        } else {
            $new[$heading] = '';
        }
    }
    
    $data[] = $new;
}

$fp = fopen('fitzwilliam.csv', 'w');
fputcsv($fp, $headings);
foreach ($data as $row) {
    fputcsv($fp, $row);
}

fclose($fp);

/***** FUNCTIONS *****/

//an iterative query of the PAS JSON response
function query_page($from, $size) {
    GLOBAL $objects;
    
    $service = "http://api.fitz.ms/es/_search/?q=materials.reference.summary_title:clay%20AND%20department.value:Antiquities%20AND%20Athens&from={$from}&size={$size}";
    $json = file_get_contents($service);
    $data = json_decode($json);
    $total = $data->hits->total->value;
    
    echo "Processing records {$from} to " . ($from + $size) . " of {$total}\n";
    
    foreach ($data->hits->hits as $doc){
        $object = array();
        $object['id'] = $doc->_source->admin->uid;
        $object['uri'] = $doc->_source->admin->uri;
        
        foreach ($doc->_source->description as $node){
            $object['desc'] = $node->value;
        }
        
        if (isset($doc->_source->name)){
            foreach($doc->_source->name as $node){
                if (isset($node->reference->summary_title)){
                    $object['shape'][] = $node->reference->summary_title . ';' . $node->reference->admin->id;
                }
            }
        }        
        
        foreach($doc->_source->identifier as $node){
            if ($node->type = 'accession number'){
                $object['accession'] = $node->value;  
                break;
            }
        }
        
        if (isset($doc->_source->lifecycle->creation)){
            foreach ($doc->_source->lifecycle->creation as $node){
                if (isset($node->date)){
                    foreach ($node->date as $date){
                        if (isset($date->from->value) && isset($date->to->value)){
                            $object['fromDate'] = $date->from->value;
                            $object['toDate'] = $date->to->value;
                        }
                    }
                }
                
                if (isset($node->maker)){
                    foreach($node->maker as $maker){
                        $object['artists'][] = $maker->summary_title . ';' . $maker->admin->id;
                    }
                }
                
                if (isset($node->note)){
                    foreach($node->note as $note){
                        $object['technique'][] = $note->value;
                    }
                }
                
                if (isset($node->periods)){
                    foreach($node->periods as $period){
                        $object['period'] = $period->summary_title . ';' . $period->admin->id;
                    }
                }
                
                if (isset($node->places)){
                    foreach($node->places as $place){
                        $object['place'] = $place->summary_title . ';' . $place->admin->id;
                    }
                }
            }
        }
        
        
        if (isset($doc->_source->materials)){
            foreach($doc->_source->materials as $material){
                $object['material'] = $material->reference->summary_title . ';' . $material->reference->admin->id;
            }
        }
        
        if (isset($doc->_source->measurements)){
            foreach($doc->_source->measurements->dimensions as $node){
                $dimension = strtolower($node->dimension);
                
                if ($node->units == 'm'){
                    $object[$dimension] = $node->value * 100;
                } elseif ($node->units == 'cm'){
                    $object[$dimension] = $node->value * 1;
                }
            }
        }
        
        if (isset($doc->_source->lifecycle->collection)){
            foreach ($doc->_source->lifecycle->collection as $node){
                if (isset($node->places)){
                    foreach($node->places as $place){
                        $object['findspot'] = $place->summary_title . ';' . $place->admin->id;
                    }
                }
            }
        }
        
        if (isset($doc->_source->multimedia)){
            foreach($doc->_source->multimedia as $media){
                if ($media->type->type == 'image'){
                    $object['images'][] = "https://collection.beta.fitz.ms/imagestore/" . $media->processed->large->location;
                }
            }
        }
        
        //flatten shape and technique
        if (array_key_exists('shape', $object)){
            $new = implode('|', $object['shape']);
            $object['shape'] = $new;
        }
        if (array_key_exists('technique', $object)){
            $new = implode('|', $object['technique']);
            $object['technique'] = $new;
        }
        if (array_key_exists('artists', $object)){
            $new = implode('|', $object['artists']);
            $object['artists'] = $new;
        }
        if (array_key_exists('images', $object)){
            $new = implode('|', $object['images']);
            $object['images'] = $new;
        }
        
        $objects[] = $object;
    } 
    
    //iterate
    if (($from + $size) < $total){
        $from += $size;
        query_page($from, $size);
    }
}


?>