<?php/*echo "<h3 class=\"item\">Systematics</h3>";echo "<h3 class=\"item\">Morphologial aspects</h3>";echo "<h3 class=\"item\">Habitat</h3>";echo "<h3 class=\"item\">General Habits</h3>";echo "<h3 class=\"item\">Voice</h3>";echo "<h3 class=\"item\">Food and Feeding</h3>";echo "<h3 class=\"item\">Breeding</h3>";echo "<h3 class=\"item\">Movements</h3>";echo "<h3 class=\"item\">Relationship with man</h3>";echo "<h3 class=\"item\">Status and conservation</h3>";  * */ echo "<h3 class=\"item\">Species</h3>";echo "<div id=\"photo\">";foreach($data['tab_img'] as $var){	echo "<span class=\"shadowImage\">	<div class=\"passive\">	<a href=\"".LINK."species/nominal/".$var['url']."\">	<table>";		echo "	<tr><td class=\"img\" style=\"background: url(".$var['photo'][0].")\">	<p class=\"text-ombre\">".$var['name']."</p></td>	</tr></table>	</a>	</div></span>";}echo "</div>";  //id=photo end?>