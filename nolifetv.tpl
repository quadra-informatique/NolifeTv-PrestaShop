<!-- Block mymodule -->
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
float:left;
}
.title_nolife{
width:50%;
text-align:center;
float:left;
padding-top:20px;
font-style:italic;
font-weight:bold;
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
 {foreach from=$result key=pgm item=i}
<div class="program_nolife">

    <span class="hour_nolife">{$i.begin} </span>
    <img class="img_nolife"src="{$i.img}"alt ="Nolife TV" />
    <span class="title_nolife"> {$i.title}</span>
    <span class="type_nolife">Genre : {$i.type}</span>

</div>
  {/foreach}
</div>
</div>
<!-- /Block mymodule -->