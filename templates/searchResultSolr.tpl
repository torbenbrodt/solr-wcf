<div class="message content solr">
	<div class="messageInner container-{cycle name='results' values='1,2'}">
		<div class="messageHeader">
			<div class="containerIcon">
				<img src="{$item.message->image}" alt="" />
			</div>
			<div class="containerContent">
				<h3><a href="{$item.message->url}{@SID_ARG_2ND}">{@$item.message->subject}</a></h3>
			</div>
		</div>
		
		<div class="messageBody">
			{@$item.message->getFormattedMessage()}
		</div>
		
		<div class="messageFooter">
			<a href="{$item.message->url}{@SID_ARG_2ND}" class="externalURL">{@$item.message->displayurl}</a>
		</div>
		<hr />
	</div>
</div>
