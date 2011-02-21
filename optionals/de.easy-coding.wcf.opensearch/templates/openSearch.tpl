<?xml version="1.0" encoding="{@CHARSET}"?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
	<ShortName><![CDATA[{lang}{@PAGE_TITLE}{/lang}]]></ShortName>
	<Description><![CDATA[{lang}{@PAGE_TITLE}{/lang} {lang}wcf.search.title{/lang}]]></Description>
	<Url type="text/html" template="{@PAGE_URL}/index.php?form=SolrSearch&amp;q={literal}{searchTerms}{/literal}"/>
	<Url type="application/atom+xml" template="{@PAGE_URL}/index.php?form=SolrSearch&amp;format=atom&amp;q={literal}{searchTerms}{/literal}"/>
{*	<Url type="application/x-suggestions+json" template="{@PAGE_URL}/index.php?form=SolrSearchSuggestion&amp;q={literal}{searchTerms}{/literal}"/>*}
</OpenSearchDescription>
