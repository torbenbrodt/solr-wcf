{include file="documentHeader"}
<head>
	<title>{$query}, {lang}wcf.search.results{/lang} {lang}wcf.global.pageNo{/lang} - {lang}{PAGE_TITLE}{/lang}</title>
	
	{include file='headInclude' sandbox=false}
	<script type="text/javascript" src="{@RELATIVE_WCF_DIR}js/MultiPagesLinks.class.js"></script>
	<style type="text/css">
	.solr .containerIcon {
		padding-top:8px !important;
	}
	.solr .containerIcon img {
		width:90px;
	}
	.solr .containerContent, .solr .messageBody {
		margin-left:100px;
		width:auto;
	}
	</style>
</head>
<body{if $templateName|isset} id="tpl{$templateName|ucfirst}"{/if}>
{* --- quick search controls --- *}
{assign var='searchScript' value='index.php?page=SolrSearch'}
{assign var='searchFieldName' value='q'}
{* --- end --- *}
{include file='header' sandbox=false}

<div id="main">
	<ul class="breadCrumbs">
		<li><a href="index.php?page=Index{@SID_ARG_2ND}"><img src="{icon}indexS.png{/icon}" alt="" /> <span>{lang}{PAGE_TITLE}{/lang}</span></a> &raquo;</li>
		<li><a href="index.php?page=SolrSearch{@SID_ARG_2ND}"><img src="{icon}solrS.png{/icon}" alt="" /> <span>{lang}wcf.search.title{/lang}</span></a> &raquo;</li>
	</ul>
	
	<div class="mainHeadline">
		<img src="{icon}searchL.png{/icon}" alt="" />
		<div class="headlineContainer">
			<h2>{if $query}<a href="index.php?page=SolrSearch&amp;q={$query|rawurlencode}{@SID_ARG_2ND}">{lang}wcf.search.results{/lang}</a>{else}{lang}wcf.search.results{/lang}{/if}</h2>
			<p>{lang}wcf.search.results.description{/lang}</p>
		</div>
	</div>
	
	{if $userMessages|isset}{@$userMessages}{/if}
	
	{cycle print=false name="results" values="1,2" advance=false}
	
	<div class="contentHeader">
		{assign var=encodedQuery value=$query|urlencode}
		{pages print=false assign=pagesOutput link="index.php?page=SolrSearch&q=$encodedQuery$additionalPagesParameterString&pageNo=%d"|concat:SID_ARG_2ND_NOT_ENCODED}
		
		{if $alterable}
			<div class="largeButtons">
				<ul><li><a href="index.php?page=SolrSearch&amp;searchID={@$searchID}&amp;modify=1{@SID_ARG_2ND}"><img src="{icon}searchM.png{/icon}" alt=""> <span>{lang}wcf.search.results.change{/lang}</span></a></li></ul>
			</div>
		{/if}
	</div>

	{if !$query}
		<form method="get" action="index.php">
			<input type="hidden" name="page" value="SolrSearch"/>
			{@SID_INPUT_TAG}

			<div class="border content">
				<div class="container-1">
			
					{if $additionalBoxes1|isset}{@$additionalBoxes1}{/if}
				
					<div class="formElement">
						<div class="formFieldLabel">
							<label for="searchTerm">{lang}wcf.search.query{/lang}</label>
						</div>
						<div class="formField">
							<input type="text" name="q" value="{$query}" maxlength="255" style="width:400px;height:20px" />
							{if $additionalQueryOptions|isset}{@$additionalQueryOptions}{/if}
						
						
							<input type="submit" accesskey="s" value="{lang}wcf.global.button.submit{/lang}" />
						</div>
						<div class="formFieldDesc">
							<p>{lang}wcf.search.query.description{/lang}</p>
						</div>
					</div>
				</div>
			</div>
		</form>
	{/if}
	
	{assign var=i value=0}
	{assign var=length value=$messages|count}
	
	<div class="border">
		<div class="layout-2">
			<div class="columnContainer">
				<div class="container-1{if $singleColumn == false} column first{/if}">
					<div class="columnInner">
						<div class="contentBox" id="searchResults">
	
						{foreach from=$messages item=item}
							{include file=$types[$item.type]->getResultTemplateName()}
							{assign var=i value=$i+1}
						{/foreach}
						
						</div>
					</div>
				</div>
				{if $singleColumn == false}
				<div class="container-3 column second contestSidebar">
					<div class="columnInner">
						{foreach from=$facets item=items key=headline}
							<div class="contentBox">
								<div class="border"> 
									<div class="containerHead"> 
										<h3>{lang}{$headline}{/lang}</h3> 
									</div>
									 
									<ul class="dataList">
										{foreach from=$items item=count key=name}
											<li class="{cycle values='container-1,container-2'}">
												<div class="containerIcon">
													<img src="{@RELATIVE_WCF_DIR}icon/searchM.png" alt="" />
												</div>
												<div class="containerContent">
													<h4><a href="index.php?page=SolrSearch&amp;q={$query|rawurlencode}&fq={$headline}:{$name}{@SID_ARG_2ND}">{$name}</a></h4>
													<p class="light smallFont">{$count} Treffer</p>
												</div>
											</li>
										{/foreach}
									</ul>
								</div>
							</div>
						{/foreach}
					</div>
				</div>
				{/if}
			</div>
		</div>
	</div>
	<script type="text/javascript">
	onloadEvents.push(function() {
		if(_gaq) {
			var links = $$('#searchResults a');
			for(var i=0; i<links.length; i++) {
				links[i].onclick = function(href) {
					return function () {
						_gaq.push(['_trackEvent', 'search', 'solr', href]);
					};
				}(links[i].href);
			}
		}
	});
	</script>
	
	<div class="contentFooter">
		{@$pagesOutput}
		{if $additionalContentFooterElements|isset}{@$additionalContentFooterElements}{/if}
	</div>
	
	{if $additionalOptions|isset}<div class="pageOptions">{@$additionalOptions}</div>{/if}
	
	<div class="border container-3" style="padding:10px">
		powered by:<br/>
		<a href="http://trac.easy-coding.de/trac/wcf/wiki/solr"><img src="{@RELATIVE_WCF_DIR}images/solr.png" alt="" /></a>
	</div>
</div>

{include file='footer' sandbox=false}
</body>
</html>
