<?php 

$data = generate_json('vases.csv');

$vases = array();
foreach ($data as $row){
	
	if (strlen($row[' Attributed To']) > 0){
		$record = array();
		
		if (strpos($row['URI'], 'href') !== FALSE){
			$obj = simplexml_load_string($row['URI']);
			$record[] = (string)$obj->attributes()->href;
		} else {
			$record[] = trim($row['URI']);
		}
		
		$record[] = $row['Vase Number'];
		$record[] = $row['Fabric'];
		$record[] = $row['Shape Name'];
		$record[] = $row['Date'];
		$record[] = $row[' Attributed To'];
		
		$vases[] = $record;
	}	
}

//export vases as new CSV
$fp = fopen('attributed-vases.csv', 'w');
//insert column headings
fputcsv($fp, array('URI', 'Vase Number', 'Fabric', 'Shape Name', 'Date', 'Attributed To'));
foreach ($vases as $fields) {
	fputcsv($fp, $fields);
}

fclose($fp);

/***** FUNCTIONS *****/
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