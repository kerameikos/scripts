<?php 

/*****
 * Author: Ethan Gruber
 * Date: August 2018
 * Function: Iterate through RDF files for Kerameikos.org in order to get a list of modifications with `git log` 
 *      The first date will be a prov:Create and most recent prov:Modify. Original *.xml files are ignored
 *      The modification dates will be outputted to an XML file to be processed with XSLT later
 *****/

$path = '/usr/local/projects/kerameikos-data/id';
$files = scandir($path);

$writer = new XMLWriter();
$writer->openURI("modifications.xml");
//$writer->openURI('php://output');
$writer->startDocument('1.0','UTF-8');
$writer->setIndent(true);
//now we need to define our Indent string,which is basically how many blank spaces we want to have for the indent
$writer->setIndentString("    ");

$writer->startElement('nodes');

foreach ($files as $file){
    
    if (strpos($file, '.rdf') !== FALSE){
        echo "\nProcessing {$file}\n";
        
        $writer->startElement('file');
            $writer->writeAttribute('id', str_replace('.rdf', '', $file));
        
            chdir($path);
            exec("git log --date=format:'%Y-%m-%dT%H:%M:%S%zZ' " . $file, $output);
            
            foreach($output as $line){
                if (strpos($line, 'Date:') !== FALSE){
                    $dateTime = str_replace('Date:   ', '', $line);
                    $writer->writeElement('date', $dateTime);
                }
            }
            
            unset($output);
        $writer->endElement();
    }
}

//close XML file
$writer->endElement();
$writer->flush();


?>