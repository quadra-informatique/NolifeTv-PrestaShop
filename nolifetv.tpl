<!-- Block nolifetv -->
<style type="text/css">

.img_nolife{
    width:40%;
    height:50px;
    float:left;
    padding-top:5px;
}
.hour_nolife{
    width:100%;
}
.type_nolife{
    width:100%;
}
.visualClear{
    clear:both;
}
.title_nolife{
    width:58%;
    text-align:left;
    float:left;
    font-style:italic;
    font-weight:bold;
    padding-left:5px;
}
.date_nolife{
    width:58%;
    text-align:left;
    float:left;
    font-style:italic;
    font-weight:bold;
    padding-left:5px;
}
.program_nolife{
    padding-top:5px;
    width:100%;
    min-height:100px;
}

</style>
<div id="mymodule_block_left" class="block">
<h4>A voir sur nolife TV</h4> 
<div class="block_content">
 {foreach from=$noAirData key=pgm item=i}
<div class="program_nolife">

    <div class="hour_nolife">{$i.begin} </div>
    <img class="img_nolife"src="{$i.img}"alt ="Nolife TV" />
    <div class="title_nolife">{$i.title}</div>
    <div class="date_nolife">{$i.date|date_format:"%d %b %Y %H:%M"}</div>
    <div class="visualClear"><!-- --></div>
    <div class="type_nolife">Genre : {$i.type}</div>
    <div class="description_nolife">Description : {$i.description}</div>
</div>
  {/foreach} 
</div>
</div>
<!-- /Block nolifetv -->
