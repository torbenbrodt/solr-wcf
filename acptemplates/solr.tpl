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
	<img src="{@RELATIVE_WCF_DIR}icon/statsL.png" alt="" />
	<div class="headlineContainer">
		<h2>{lang}wcf.acp.stats{/lang}</h2>
	</div>
</div>

{if $errorField}
	<p class="error">{lang}wcf.global.form.error{/lang}</p>
{/if}

{if $results|isset && !$results|count}
	<p class="error">{lang}wcf.acp.stats.noResults{/lang}</p>
{/if}
{*
<form  method="post" action="index.php?form=Stats">
	<div class="border content">
		<div class="container-1">
			<fieldset>
				<legend>{lang}wcf.acp.stats.config{/lang}</legend>
				
				<div class="formElement{if $errorField == 'username'} formError{/if}">
					<div class="formFieldLabel">
						<label for="username">{lang}wcf.user.username{/lang}</label>
					</div>
					<div class="formField">
						<input type="text" class="inputText" id="username" name="username" value="" />
						{if $errorField == 'username'}
							<p class="innerError">
								{if $errorType == 'notFound'}{lang}wcf.user.error.username.notFound{/lang}{/if}
							</p>
						{/if}
					</div>
				</div>
				
				{if $additionalFields|isset}{@$additionalFields}{/if}
			</fieldset>
		</div>
	</div>
		
	<div class="formSubmit">
		<input type="submit" accesskey="s" value="{lang}wcf.global.button.submit{/lang}" />
		<input type="reset" accesskey="r" value="{lang}wcf.global.button.reset{/lang}" />
		<input type="hidden" name="packageID" value="{@PACKAGE_ID}" />
 		{@SID_INPUT_TAG}
 	</div>
</form>
*}
{if $results|isset && $results|count}
	<div class="border content">
		<div class="container-1">
			<fieldset>
				<legend>{lang}wcf.acp.stats.results{/lang}</legend>
	
				{foreach from=$results item=result key=$type}
					<div class="formElement">
						<p class="formFieldLabel">{$type}</p>
						<div class="formField"><div class="statBar"><div style="width: {$result.percent|round}%;"></div></div><p class="statBarLabel">{#$result.current}/{#$result.total}</p></div>
					</div>
				
				{/foreach}
			</fieldset>
		</div>
	</div>
{/if}

{include file='footer'}
