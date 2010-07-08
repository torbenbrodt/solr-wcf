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
		{pages print=false assign=pagesOutput link="index.php?page=SolrSearch&q=$encodedQuery&pageNo=%d"|concat:SID_ARG_2ND_NOT_ENCODED}
		
		{if $alterable}
			<div class="largeButtons">
				<ul><li><a href="index.php?page=SolrSearch&amp;searchID={@$searchID}&amp;modify=1{@SID_ARG_2ND}"><img src="{icon}searchM.png{/icon}" alt=""> <span>{lang}wcf.search.results.change{/lang}</span></a></li></ul>
			</div>
		{/if}
	</div>

	{if !$query}
		<p class="info">Der Suchserver ist zur Zeit noch experimentell. Wir arbeiten noch mit Hochdruck an Suchqualität und Ergebnismenge. Wir bitten um Verständnis.</p>
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
	
	{foreach from=$messages item=item}
		{include file=$types[$item.type]->getResultTemplateName()}
		{assign var=i value=$i+1}
	{/foreach}
	
	<div class="contentFooter">
		{@$pagesOutput}
		
		{if $additionalContentFooterElements|isset}{@$additionalContentFooterElements}{/if}
		
		{if $alterable}
			<div class="largeButtons">
				<ul><li><a href="index.php?page=SolrSearch&amp;searchID={@$searchID}&amp;modify=1{@SID_ARG_2ND}"><img src="{icon}searchM.png{/icon}" alt=""> <span>{lang}wcf.search.results.change{/lang}</span></a></li></ul>
			</div>
		{/if}
	</div>
	
	{if $alterable}
		<div class="border infoBox">
			<div class="container-1">
				<div class="containerIcon"><img src="{icon}sortM.png{/icon}" alt="" /> </div>
				<div class="containerContent">
					<h3>{lang}wcf.search.results.display{/lang}</h3>
					<form method="post" action="index.php">
						
						<div class="floatContainer">
							<input type="hidden" name="form" value="Search" />
							<input type="hidden" name="pageNo" value="{@$pageNo}" />
							<input type="hidden" name="highlight" value="{@$highlight}" />
							
							<div class="floatedElement">
								<label for="sortField">{lang}wcf.search.sortBy{/lang}</label>
								<select id="sortField" name="sortField">
									<option value="relevance"{if $sortField == 'relevance'} selected="selected"{/if}>{lang}wcf.search.sortBy.relevance{/lang}</option>
									<option value="subject"{if $sortField == 'subject'} selected="selected"{/if}>{lang}wcf.search.sortBy.subject{/lang}</option>
									<option value="time"{if $sortField == 'time'} selected="selected"{/if}>{lang}wcf.search.sortBy.creationDate{/lang}</option>
									<option value="username"{if $sortField == 'username'} selected="selected"{/if}>{lang}wcf.search.sortBy.author{/lang}</option>
								</select>
							
								<select name="sortOrder">
									<option value="ASC"{if $sortOrder == 'ASC'} selected="selected"{/if}>{lang}wcf.global.sortOrder.ascending{/lang}</option>
									<option value="DESC"{if $sortOrder == 'DESC'} selected="selected"{/if}>{lang}wcf.global.sortOrder.descending{/lang}</option>
								</select>
							</div>
							
							<div class="floatedElement">
							{if $additionalDisplayOptions|isset}{@$additionalDisplayOptions}{/if}						
							</div>
							<div class="floatedElement">
								<input type="image" class="inputImage" src="{icon}submitS.png{/icon}" alt="{lang}wcf.global.button.submit{/lang}" />
							</div>
	
							<input type="hidden" name="modify" value="1" />
							{@SID_INPUT_TAG}
						</div>
					</form>
				</div>
			</div>
			
			{if $additionalBoxes|isset}{@$additionalBoxes}{/if}
		</div>
	{/if}
	
	{if $additionalOptions|isset}<div class="pageOptions">{@$additionalOptions}</div>{/if}
	
	<div class="border container-3" style="padding:10px">
		powered by:<br/>
		<a href="http://trac.easy-coding.de/trac/wcf/wiki/solr"><img src="{@RELATIVE_WCF_DIR}images/solr.png" alt="" /></a>
	</div>
</div>

{include file='footer' sandbox=false}
</body>
</html>
