<?php

	$CONTENTDM_HOME = "http://www.ohiomemory.org"; // CONTENTdm home URL
	$CONTENTDM_WEB_SERVICES = "https://server16007.contentdm.oclc.org"; // CONTENTdm web services URL

	function do_curl($curl_url) {
		$ch = curl_init();
	  	curl_setopt($ch, CURLOPT_URL, $curl_url);
	  	curl_setopt($ch, CURLOPT_HEADER, 0);  // DO NOT RETURN HTTP HEADERS
	  	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // RETURN THE CONTENTS OF THE CALL
	  	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if (!strpos($curl_url, 'xml')) {
    		$cdm_data_json = curl_exec($ch);
		} else {
			$cdm_data_xml = curl_exec($ch);
			$xml = simplexml_load_string($cdm_data_xml);
			$cdm_data_json = json_encode($xml);
		}
		curl_close($ch);
		return json_decode($cdm_data_json, true);
	}
	
	if ( isset($_POST['link']) ) {
		
		// e.g. http://www.ohiomemory.org/cdm/singleitem/collection/p267401coll32/id/16306
		$CDM_link = strip_tags(trim($_POST['link']));
	 
		if ( isset($_POST['imagesize']) && is_numeric($_POST['imagesize']) ) {
			$img_size = $_POST['imagesize'];
		} else {
			$img_size = 400;
		}
		$CDM_link_arr = explode(",", preg_replace('/^.*collection\/(.*?)\/id\/(.*)$/','$1,$2',$CDM_link));
		
		// curl to get dimensions and scale to present image in crop window
		$curl_url = $CONTENTDM_WEB_SERVICES . "/dmwebservices/index.php?q=dmGetImageInfo/".$CDM_link_arr[0]."/".$CDM_link_arr[1]."/xml";
		$image_info = do_curl($curl_url);
		
		// get width and scale info
		$imgwidth = $image_info['width'];
		$imgheight = $image_info['height'];
		$longest_side = $imgwidth > $imgheight ? $imgwidth : $imgheight;
		$trimmed_scale = "20";
		//$scale = round(($img_size/$longest_side), 2);
		$scale = $img_size/$longest_side;
		$targ_w = $imgwidth*$scale;
		$targ_h = $imgheight*$scale;
		$formatted_scale = sprintf("%01.2f", $scale);
		$trimmed_scale = substr($formatted_scale, 2);
		if ($imgwidth < $img_size) { $trimmed_scale = 100; }
		
		$scaled_link = $CONTENTDM_HOME.'/utils/ajaxhelper/?CISOROOT='.$CDM_link_arr[0].'&CISOPTR='.$CDM_link_arr[1].'&action=2&DMSCALE='.$trimmed_scale.'&DMWIDTH='.$targ_w.'&DMHEIGHT='.$targ_h;
	  
	}
	
?>

<!doctype html public "-//w3c//dtd html 4.01 transitional//en" "http://www.w3.org/tr/1999/rec-html401-19991224/loose.dtd">
<html>
  <head>
    <title>Get sized CDM image from reference URL</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <script type="text/javascript">
			function SelectAll(id) {
		    document.getElementById(id).focus();
		    document.getElementById(id).select();
			}
		</script>
		<style type="text/css">
			#scaleimage {
				border: 1px solid gray;
				padding: 10px;
				display:-moz-inline-stack;
				display:inline-block;
				zoom:1;
				*display:inline;
			}
			li {
				text-align: left;
			}
		</style>
  </head>
  <body>
  	
		<div style="margin: 0 auto;text-align: center;">
	  	<form id="scaleimage" method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
				<p><b>Get a scaled image link from a CONTENTdm reference URL</b></p>
				<ul>
				<li>CDM Reference URL: <input type="text" name="link" size="30" value="<?php echo isset($_POST['link']) ? $_POST['link'] : '' ?>" /></li> 
				<li>Fit image into <input type="text" name="imagesize" size="4" value="<?php echo isset($_POST['imagesize']) ? $_POST['imagesize'] : '' ?>" /> pixel box. <input type="submit"></li>
				</ul>
				<div style="padding: 10px">
					<?php 
						if (isset($_POST['link'])) {
							echo 'Scaled image URL: <input id="cdmref" type="text" size="50" Value="'.$scaled_link.'" onClick="SelectAll(\'cdmref\');"">';
							echo '<br/><br/>';
							echo '<img src='.$scaled_link.'>';
						} 
					?>
				</div>
			</form>
		</div>
			
  </body>
</html>