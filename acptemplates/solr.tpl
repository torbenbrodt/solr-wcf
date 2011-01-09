{capture append='specialStyles'}
<style type="text/css">
/*<![CDATA[*/
.statBar {
	text-align: left;
	padding: 1px;
	background-color: #fff;
	border: 1px solid #8da4b7;
	float: left;
	width: 400px;
}

.statBar div {
	font-size: 6px; /* needed for correct usage-bar display in IE-browsers */
	background-color: #0c0;
	border-bottom: 6px solid #0a0;
	height: 6px;
}

.statBarLabel {
	margin-left: 410px;
}
/*]]>*/
</style>
{/capture}
{include file='header'}

<div class="mainHeadline">
	<img src="{@RELATIVE_WCF_DIR}icon/solrL.png" alt="" />
	<div class="headlineContainer">
		<h2>{lang}wcf.acp.solr{/lang}</h2>
	</div>
</div>

{if $errorField}
	<p class="error">{lang}wcf.global.form.error{/lang}{if $errorFieldMessage|isset}{@$errorFieldMessage}{/if}</p>
{/if}

{if $results|isset && !$results|count}
	<p class="error">{lang}wcf.acp.solr.noResults{/lang}</p>
{/if}
{if $results|isset && $results|count}
	<form method="post" action="index.php?form=Solr">
		<div class="border content">
			<div class="container-1">
				<fieldset>
					<legend>{lang}wcf.acp.solr.results{/lang}</legend>

					{foreach from=$results item=result key=$type}
						<div class="formElement">
							<p class="formFieldLabel"><a href="#" onclick="$('options_{$type}').toggle();return false;">{$type}</a></p>
							<div class="formField">
								<div class="statBar"><div style="width: {$result.percent|round}%;"></div></div>
								<p class="statBarLabel">
									{$result.percent|round}% {#$result.current}/{#$result.total}
								</p>
								<div id="options_{$type}" style="display:none">
									<div class="formElement">
										<p class="formFieldLabel">{lang}boost{/lang}</p>
										<div class="formField">
											<input type="text" value="1.0" size="4" />
										</div>
									</div>
								</div>
							</div>
						</div>

					{/foreach}
				</fieldset>
				<fieldset>
					<legend>{lang}wcf.acp.solr.reindex{/lang}</legend>

					{foreach from=$reindex item=result key=$type}
						<div class="formElement">
							<p class="formFieldLabel">{$type}</p>
							<div class="formField">
								<div class="statBar"><div style="width: {$result.percent|round}%;"></div></div>
								<p class="statBarLabel">
									{$result.percent|round}% {#$result.current}/{#$result.total}
								</p>
							</div>
						</div>

					{/foreach}
				</fieldset>
			</div>
		</div>

		<div class="formSubmit">
			<input type="submit" accesskey="s" value="{lang}wcf.global.button.submit{/lang}" />
			<input type="reset" accesskey="r" value="{lang}wcf.global.button.reset{/lang}" />
			<input type="hidden" name="packageID" value="{@PACKAGE_ID}" />
	 		{@SID_INPUT_TAG}
	 	</div>
	</form
{/if}

{include file='footer'}
