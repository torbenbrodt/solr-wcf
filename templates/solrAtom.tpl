<?xml version="1.0" encoding="{@CHARSET}"?>
 <rss version="2.0" 
      xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/"
      xmlns:atom="http://www.w3.org/2005/Atom">
   <channel>
     <title><![CDATA[{lang}{@PAGE_TITLE}{/lang}]]></title>
     <link>{@PAGE_URL}/index.php?form=SolrSearch&amp;q={$query|urlencode}</link>
     <description><![CDATA[{lang}{@PAGE_TITLE}{/lang} {lang}wcf.search.title{/lang}]]></description>
     <opensearch:Query role="request" searchTerms="{$query}" startPage="{$pageNo}" />
     <opensearch:totalResults>{$items}</opensearch:totalResults>
     <opensearch:startIndex>{$startIndex}</opensearch:startIndex>
     <opensearch:itemsPerPage>{$itemsPerPage}</opensearch:itemsPerPage>
     {foreach from=$messages item=item}
     <item>
       <title><![CDATA[{@$item.message->subject}]]></title>
       <link>{$item.message->url}</link>
       <img>{$item.message->image}</img>
       <description><![CDATA[{@$item.message->getFormattedMessage()}]]></description>
     </item>
     {/foreach}
   </channel>
 </rss>
