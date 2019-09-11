<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:crm="http://www.cidoc-crm.org/cidoc-crm/" xmlns:dcterms="http://purl.org/dc/terms/" exclude-result-prefixes="xs xsl" version="2.0">

    <xsl:strip-space elements="*"/>
    <xsl:output method="xml" encoding="UTF-8" indent="yes"/>

    <xsl:template match="@* | node()">
        <xsl:copy>
            <xsl:apply-templates select="@* | node()"/>
        </xsl:copy>
    </xsl:template>

    <xsl:template match="rdf:RDF">
        <rdf:RDF xmlns:crm="http://www.cidoc-crm.org/cidoc-crm/" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
            xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#" xmlns:xsd="http://www.w3.org/2001/XMLSchema#" xmlns:kon="http://kerameikos.org/ontology#"
            xmlns:void="http://rdfs.org/ns/void#" xmlns:edm="http://www.europeana.eu/schemas/edm/" xmlns:svcs="http://rdfs.org/sioc/services#"
            xmlns:doap="http://usefulinc.com/ns/doap#" xmlns:kid="http://kerameikos.org/id/" xmlns:dcterms="http://purl.org/dc/terms/"
            xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#">
            <xsl:apply-templates/>
        </rdf:RDF>
    </xsl:template>

    <xsl:template match="dcterms:title">
        <crm:P1_is_identified_by>
            <crm:E33_E41_Linguistic_Appellation>
                <crm:P190_has_symbolic_content>
                    <xsl:value-of select="."/>
                </crm:P190_has_symbolic_content>
                <crm:P2_has_type rdf:resource="http://vocab.getty.edu/aat/300404670"/>
            </crm:E33_E41_Linguistic_Appellation>
        </crm:P1_is_identified_by>
    </xsl:template>

    <xsl:template match="dcterms:identifier">
        <crm:P1_is_identified_by>
            <crm:E42_Identifier>
                <crm:P190_has_symbolic_content>
                    <xsl:value-of select="."/>
                </crm:P190_has_symbolic_content>
                <crm:P2_has_type rdf:resource="http://vocab.getty.edu/aat/300312355"/>
            </crm:E42_Identifier>
        </crm:P1_is_identified_by>
    </xsl:template>


</xsl:stylesheet>
