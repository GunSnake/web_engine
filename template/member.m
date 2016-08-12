<html>
<body>
{$data}, {$person}
<ul>
    {loop $b}
    <li>{v}</li>
    {/loop}
</ul>
<?php
echo $pai*2;
?>
{if $data == 'abc'}
abc
{elseif $data == 'aaa'}
aaa
{else}
it's me
{/if}
{#测试注释是否会出现#}
<!--测试html注释-->
</body>
</html>