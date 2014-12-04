<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui" exclude-result-prefixes="ds md mdui">
	<xsl:template match="/">
		<idps>
			<xsl:for-each select="/md:EntitiesDescriptor/md:EntityDescriptor/md:IDPSSODescriptor/md:Extensions/mdui:DiscoHints/mdui:DomainHint">
				<xsl:sort select="." />
				<xsl:element name="idp">
					<xsl:value-of select="." />
				</xsl:element>
			</xsl:for-each>
		</idps>
	</xsl:template>
</xsl:stylesheet>
