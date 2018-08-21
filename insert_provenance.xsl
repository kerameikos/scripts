<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:foaf="http://xmlns.com/foaf/0.1/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:prov="http://www.w3.org/ns/prov#" xmlns:skos="http://www.w3.org/2004/02/skos/core#"
    xmlns:crm="http://www.cidoc-crm.org/cidoc-crm/" xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#" xmlns:dcterms="http://purl.org/dc/terms/"
    xmlns:un="http://www.owl-ontologies.com/Ontology1181490123.owl#" xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#" xmlns:kid="http://kerameikos.org/id/"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema#" xmlns:kon="http://kerameikos.org/ontology#" xmlns:owl="http://www.w3.org/2002/07/owl#"
    xmlns:org="http://www.w3.org/ns/org#" xmlns:osgeo="http://data.ordnancesurvey.co.uk/ontology/geometry/" xmlns:ontolex="http://www.w3.org/ns/lemon/ontolex#"
    xmlns:lexinfo="http://www.lexinfo.net/ontology/2.0/lexinfo#" exclude-result-prefixes="xs xsl" version="2.0">

    <xsl:strip-space elements="*"/>
    <xsl:output method="xml" encoding="UTF-8" indent="yes"/>

    <xsl:template match="@* | node()">
        <xsl:copy>
            <xsl:apply-templates select="@* | node()"/>
        </xsl:copy>
    </xsl:template>

    <xsl:template match="rdf:RDF">
        <xsl:variable name="uri" select="*[1]/@rdf:about"/>
        <xsl:variable name="id" select="tokenize($uri, '/')[last()]"/>


        <xsl:element name="rdf:RDF">
            <xsl:namespace name="rdf">http://www.w3.org/1999/02/22-rdf-syntax-ns#</xsl:namespace>
            <xsl:namespace name="crm">http://www.cidoc-crm.org/cidoc-crm/</xsl:namespace>
            <xsl:namespace name="dcterms">http://purl.org/dc/terms/</xsl:namespace>
            <xsl:namespace name="foaf">http://xmlns.com/foaf/0.1/</xsl:namespace>
            <xsl:namespace name="geo">http://www.w3.org/2003/01/geo/wgs84_pos#</xsl:namespace>
            <xsl:namespace name="kid">http://kerameikos.org/id/</xsl:namespace>
            <xsl:namespace name="kon">http://kerameikos.org/ontology#</xsl:namespace>
            <xsl:namespace name="lexinfo">http://www.lexinfo.net/ontology/2.0/lexinfo#</xsl:namespace>
            <xsl:namespace name="ontolex">http://www.w3.org/ns/lemon/ontolex#</xsl:namespace>
            <xsl:namespace name="org">http://www.w3.org/ns/org#</xsl:namespace>
            <xsl:namespace name="osgeo">http://data.ordnancesurvey.co.uk/ontology/geometry/</xsl:namespace>
            <xsl:namespace name="owl">http://www.w3.org/2002/07/owl#</xsl:namespace>
            <xsl:namespace name="prov">http://www.w3.org/ns/prov#</xsl:namespace>
            <xsl:namespace name="rdfs">http://www.w3.org/2000/01/rdf-schema#</xsl:namespace>
            <xsl:namespace name="skos">http://www.w3.org/2004/02/skos/core#</xsl:namespace>
            <xsl:namespace name="un">http://www.owl-ontologies.com/Ontology1181490123.owl#</xsl:namespace>
            <xsl:namespace name="xsd">http://www.w3.org/2001/XMLSchema#</xsl:namespace>
            <xsl:apply-templates/>

            <!-- insert provenance events -->
            <dcterms:ProvenanceStatement rdf:about="{concat($uri, '#provenance')}">
                <foaf:topic rdf:resource="{$uri}"/>
                <xsl:variable name="creation">
                    <xsl:choose>
                        <xsl:when test="document('xml-modifications.xml')//file[@id = $id]">
                            <xsl:value-of select="document('xml-modifications.xml')//file[@id = $id]/date[last()]"/>
                        </xsl:when>
                        <xsl:when test="document('modifications.xml')//file[@id = $id]">
                            <xsl:value-of select="document('modifications.xml')//file[@id = $id]/date[last()]"/>
                        </xsl:when>
                    </xsl:choose>
                </xsl:variable>

                <prov:wasGeneratedBy>
                    <prov:Activity>
                        <rdf:type rdf:resource="http://www.w3.org/ns/prov#Create"/>
                        <prov:atTime rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">
                            <xsl:value-of select="$creation"/>
                        </prov:atTime>
                        <prov:wasAssociatedWith rdf:resource="http://kerameikos.org/id/egruber"/>
                        <dcterms:type>manual</dcterms:type>
                    </prov:Activity>
                </prov:wasGeneratedBy>

                <prov:activity>
                    <prov:Activity>
                        <rdf:type rdf:resource="http://www.w3.org/ns/prov#Modify"/>
                        <prov:atTime rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">
                            <xsl:value-of select="document('modifications.xml')//file[@id = $id]/date[1]"/>
                        </prov:atTime>
                        <prov:wasAssociatedWith rdf:resource="http://kerameikos.org/id/egruber"/>
                        <dcterms:type>manual</dcterms:type>
                    </prov:Activity>
                </prov:activity>
            </dcterms:ProvenanceStatement>
        </xsl:element>
    </xsl:template>

    <xsl:template match="*[rdf:type/@rdf:resource = 'http://www.w3.org/2004/02/skos/core#Concept']">
        <xsl:element name="{name()}">
            <xsl:attribute name="rdf:about" select="@rdf:about"/>
            <xsl:variable name="uri" select="@rdf:about"/>
            <xsl:apply-templates/>
            <skos:changeNote rdf:resource="{concat($uri, '#provenance')}"/>
        </xsl:element>
    </xsl:template>
</xsl:stylesheet>
