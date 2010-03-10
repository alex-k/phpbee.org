<?php

function gs_controller_paging($firstitemname, $type,$total_items,$itemsperpage,$first_item=0) {
	if($first_item>$total_items) $first_item=0;
	if (!$itemsperpage || $itemsperpage==0) $itemsperpage=1;
	

	$href=preg_replace("/[?&]$firstitemname=\S+/","",$_SERVER[REQUEST_URI]);
	$href.=(strpos($href,'?')>0) ? "&" : "?";

	$ret=array();

	switch ($type)  {
		case "pagenums" :
			$cp=floor($first_item/$itemsperpage)+1;
			$tp=ceil($total_items/$itemsperpage);
			for ($i=1; $i<=$tp; $i++) {
				$fi=($i-1)*$itemsperpage;
				$ret[$i]="<a href=\"$href$firstitemname=$fi\">$i</a>";
			}
			$ret[$cp]="<a id=\"curr\" href=\"$href$firstitemname=$first_item\">$cp</a>";

			/*

			for ($i=1;$i<=$total_items;$i=$i+$itemsperpage) {
				$firstitem=$i-1;
				$j=$i+$itemsperpage-1<$total_items? $i+$itemsperpage-1 : $total_items;
				$a=ceil($i/$itemsperpage);
				if ($i>=$first_item+$itemsperpage || $j<$first_item) {
					$ret[]="<a href=\"$href$firstitemname=$firstitem\">$a</a>";
				} else {
					$ret[]="<a id=\"curr\" href=\"$href$firstitemname=$firstitem\">$a</a>";
				}
			}
			*/
			break;
	}

	$ret=implode(" | ",$ret);
	return $ret;
}
	
?>
