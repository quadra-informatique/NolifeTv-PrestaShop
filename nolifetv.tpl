<!-- Block nolifetv -->
{*
	Available Options for $program
        (
            [cacheId] => 250 ### used to retrive the local image file
            [date] => 2011/12/21 17:50:42
            [timestampUTC] => 1324482642
            [dateUTC] => 2011/12/21 16:50:42
            [description] => Money Shot - 23
            [title] => Money Shot - 23
            [sub-title] =>
            [detail] =>
            [leveltype] => 100
            [color] => purple
            [csa] =>
            [url] => http://forum.nolife-tv.com/showthread.php?t=11112&page=999999
            [screenshot] => ### please do not use this, it's better to use the locale cache image system
            [NolifeOnlineURL] => http://online.nolife-tv.com/index.php?id=21397
            [NolifeOnlineStart] => 2011/05/12 20:40:00
            [NolifeOnlineEnd] => 3000/01/01 00:00:00
            [NolifeOnlineShowDate] => 2011/05/11 19:03:00
            [AdditionalScreenshot] => ### please do not use this, it's better to use the locale cache image system
            [Online_ExternalURL] => http://online.nolife-tv.com/index.php?id=21397
            [premierediff] => 0
            [type] => Rubrique
            [id_mastershow] => c707c950bb38c945570a4db803c5fdfc
        )
*}
<div id="nolifetv_block" class="block">
	<h4>{l s='Now on Nolife' mod='nolifetv'}</h4>
	<ul class="block_content">
		{foreach from=$noAirData key=id item=program}
		<li>
			{if $program.NolifeOnlineURL}
				<a href="{$program.NolifeOnlineURL}" alt="{$program.date|date_format:"%H:%M"} {$program.title}" title="{l s='View on Nolife Online' mod='nolifetv'}">
					<p class="bold">{$program.date|date_format:"%H:%M"} {$program.title}</p>
				</a>
				<a href="{$program.NolifeOnlineURL}" alt="{$program.title}" title="{l s='View on Nolife Online' mod='nolifetv'}">
					<img class="img_nolife"src="{$base_dir}modules/nolifetv/screenshot.php?id={$program.cacheId}" alt ="{$program.title}" />
				</a><br/>
				<a href="{$program.NolifeOnlineURL}" alt="{$program.description}" title="{l s='View on Nolife Online' mod='nolifetv'}">
						{$program.description}
				</a>
			{else}
				<p class="bold">{$program.date|date_format:"%H:%M"} {$program.title}</p>
				<img class="img_nolife"src="{$base_dir}modules/nolifetv/screenshot.php?id={$program.cacheId}" alt ="{$program.title}"/>
      				<br/>
				{$program.description}
			{/if}
		</li>
		{/foreach}
		<li style="font-size: xx-small;font-style: italic">
			<a href="{$base_dir}" alt="{$shop_name|escape:'htmlall':'UTF-8'}">
				{$shop_name|escape:'htmlall':'UTF-8'}
			</a>
			{l s='is not affiliated with ' mod='nolifetv'}
			<a href="http://www.nolife-tv.com" alt="Nolife">Nolife</a>
		</li>
	</ul>



</div>
<!-- /Block nolifetv -->
