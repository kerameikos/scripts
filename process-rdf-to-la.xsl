<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:foaf="http://xmlns.com/foaf/0.1/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:crm="http://www.cidoc-crm.org/cidoc-crm/" xmlns:doap="http://usefulinc.com/ns/doap#"
    xmlns:edm="http://www.europeana.eu/schemas/edm/" xmlns:svcs="http://rdfs.org/sioc/services#" xmlns:dcterms="http://purl.org/dc/terms/"
    exclude-result-prefixes="xs xsl" version="2.0">

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
            xmlns:void="http://rdfs.org/ns/void#" xmlns:kid="http://kerameikos.org/id/" xmlns:dcterms="http://purl.org/dc/terms/"
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

    <!-- remove thumbnails for now -->
    <xsl:template match="foaf:thumbnail"/>

    <xsl:template match="foaf:depiction">
        <xsl:variable name="uri" select="@rdf:resource"/>

        <crm:P138i_has_representation>
            <xsl:choose>
                <xsl:when test="//edm:WebResource[@rdf:about = $uri]">
                    <crm:E36_Visual_Item rdf:about="{//edm:WebResource[@rdf:about = $uri]/svcs:has_service/@rdf:resource}">
                        <dcterms:conformsTo rdf:resource="http://iiif.io/api/image"/>
                    </crm:E36_Visual_Item>
                </xsl:when>
                <xsl:otherwise>
                    <crm:E36_Visual_Item rdf:about="{$uri}">
                        <dcterms:format>image/jpeg</dcterms:format>
                    </crm:E36_Visual_Item>
                </xsl:otherwise>
            </xsl:choose>
        </crm:P138i_has_representation>

        <!-- insert manifest reference, if applicable -->
        <xsl:if test="//edm:WebResource[@rdf:about = $uri]/dcterms:isReferencedBy">
            <crm:P129i_is_subject_of>
                <crm:E73_Information_Object rdf:about="{//edm:WebResource[@rdf:about = $uri]/dcterms:isReferencedBy/@rdf:resource}">
                    <dcterms:format>application/ld+json;profile="http://iiif.io/api/presentation/2/context.json"</dcterms:format>
                    <dcterms:conformsTo rdf:resource="http://iiif.io/api/presentation"/>
                </crm:E73_Information_Object>
            </crm:P129i_is_subject_of>
        </xsl:if>
    </xsl:template>

    <!-- 3D models -->
    <xsl:template match="edm:isShownBy">
        <xsl:variable name="uri" select="@rdf:resource"/>

        <crm:P138i_has_representation>
            <crm:E36_Visual_Item rdf:about="{$uri}">
                <xsl:copy-of select="//edm:WebResource[@rdf:about = $uri]/dcterms:format"/>
            </crm:E36_Visual_Item>
        </crm:P138i_has_representation>
    </xsl:template>

    <!-- remove service, web resource -->
    <xsl:template match="svcs:Service | edm:WebResource"/>

</xsl:stylesheet>
