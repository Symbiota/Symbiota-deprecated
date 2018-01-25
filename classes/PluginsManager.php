<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
 
class PluginsManager {

 	public function __construct(){
 	}
 	
 	public function __destruct(){
	}
	
	public function createSlidewhow($ssId,$numSlides,$width,$numDays,$imageType,$clId,$dayInterval,$interval=7000){
		if($width > 800){
			$width = 800;
		}
		if($width < 275){
			$width = 275;
		}
		$ssInfo = $this->setSlidewhow($ssId,$numSlides,$numDays,$imageType,$clId,$dayInterval);
		$imageArr = $ssInfo['files'];
		$imageHtml = $this->getImageList($imageArr,$width);
		$showHtml = $this->setShowHtml($imageHtml,$width,$interval);
		return $showHtml;
	}
	
	public function setSlidewhow($ssId,$numSlides,$numDays,$imageType,$clId,$dayInterval){
		global $SERVER_ROOT;
		$currentDate = date("Y-m-d");
		$replace = 0;
		if(file_exists($SERVER_ROOT.'/temp/slideshow/'.$ssId.'_info.json')){
			$oldArr = json_decode(file_get_contents($SERVER_ROOT.'/temp/slideshow/'.$ssId.'_info.json'), true);
			$lastDate = $oldArr['lastDate'];
			$lastCLID = $oldArr['clid'];
			$lastNumSlides = $oldArr['numslides'];
			$lastNumDays = $oldArr['numdays'];
			$lastImageType = $oldArr['imagetype'];
			$replaceDate = date('Y-m-d', strtotime($lastDate. ' + '.$dayInterval.' days'));
			if(($currentDate > $replaceDate) || ($clId != $lastCLID) || ($numSlides != $lastNumSlides) || ($numDays != $lastNumDays) || ($imageType != $lastImageType)){
				$replace = 1;
			}
		}
		else{
			$replace = 1;
		}
		
		if($replace == 1){
			ini_set('max_execution_time', 180); //180 seconds = 3 minutes
			$sinceDate = date('Y-m-d', strtotime($currentDate. ' - '.$numDays.' days'));
			
			//Delete old files
			if($clId){
				$previous = Array();
				if(file_exists($SERVER_ROOT.'/temp/slideshow/'.$ssId.'_previous.json')){
					$previous = json_decode(file_get_contents($SERVER_ROOT.'/temp/slideshow/'.$ssId.'_previous.json'), true);
					unlink($SERVER_ROOT.'/temp/slideshow/'.$ssId.'_previous.json');
					if($clId != $lastCLID){
						$previous = Array();
					}
				}
			}
			else{
				if(file_exists($SERVER_ROOT.'/temp/slideshow/'.$ssId.'_previous.json')){
					unlink($SERVER_ROOT.'/temp/slideshow/'.$ssId.'_previous.json');
				}
			}
			if(file_exists($SERVER_ROOT.'/temp/slideshow/'.$ssId.'_info.json')){
				unlink($SERVER_ROOT.'/temp/slideshow/'.$ssId.'_info.json');
			}
			
			//Create new files
			$ssIdInfo = array();
			$ssIdInfo['lastDate'] = $currentDate;
			$ssIdInfo['clid'] = $clId;
			if($numSlides > 10){
				$numSlides = 10;
			}
			if($numSlides < 5){
				$numSlides = 5;
			}
			$ssIdInfo['numslides'] = $numSlides;
			$ssIdInfo['numdays'] = $numDays;
			$ssIdInfo['imagetype'] = $imageType;
			
			$files = Array();
			$limit = $numSlides * 3;
			$sql = 'SELECT i.imgid, i.tid, i.occid, i.url, i.photographer, i.`owner`, t.SciName, o.sciname AS occsciname, '.
				'CONCAT_WS(" ",u.firstname,u.lastname) AS photographerName, '.
				'CONCAT_WS("; ",o.sciname, o.catalognumber, CONCAT_WS(" ",o.recordedby,IFNULL(o.recordnumber,o.eventdate))) AS identifier '.
				'FROM (((images AS i LEFT JOIN users AS u ON i.photographeruid = u.uid) '.
				'LEFT JOIN omoccurrences AS o ON i.occid = o.occid) '.
				'LEFT JOIN taxa AS t ON i.tid = t.tid) ';
			if($clId){
				$sql .= 'LEFT JOIN fmchklsttaxalink AS v ON t.tid = v.TID ';
				$sql .= 'WHERE v.CLID IN('.$clId.') ';
			}
			else{
				$sql .= 'WHERE i.InitialTimeStamp < "'.$sinceDate.'" AND i.tid IS NOT NULL ';
			}
			if(!$clId && $imageType == 'specimen'){
				$sql .= 'AND i.occid IS NOT NULL ';
			}
			if(!$clId && $imageType == 'field'){
				$sql .= 'AND ISNULL(i.occid) ';
			}
			$sql .= 'ORDER BY i.sortsequence ';
			if(!$clId){
				$sql .= 'LIMIT '.$limit.' ';
			}
			//echo '<div>'.$sql.'</div>';
			$cnt = 1;
			$imgIdArr = Array();
 			$conn = MySQLiConnectionFactory::getCon("readonly");
			$rs = $conn->query($sql);
			while(($row = $rs->fetch_object()) && ($cnt < ($numSlides + 1))){
				$file = '';
				$imgId = $row->imgid;
				if($clId){
					if(!in_array($imgId, $previous)){
						if (substr($row->url, 0, 1) == '/'){
							//If imageDomain variable is set within symbini file, image  
							if(isset($GLOBALS['imageDomain']) && $GLOBALS['imageDomain']){
								$file = $GLOBALS['imageDomain'].$row->url;
							}
							else{
								//Use local domain 
								$domain = "http://";
								if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $domain = "https://";
								$domain .= $_SERVER["SERVER_NAME"];
								if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $domain .= ':'.$_SERVER["SERVER_PORT"];
								$file = $domain.$row->url;
							}
						}
						else{
							$file = $row->url;
						}
						@$size = getimagesize(str_replace(' ', '%20', $file));
						if($size){
							$width = $size[0];
							$height = $size[1];
							$files[$imgId]['url'] = $file;
							$files[$imgId]['width'] = $width;
							$files[$imgId]['height'] = $height;
							$files[$imgId]['imgid'] = $row->imgid;
							$files[$imgId]['tid'] = $row->tid;
							$files[$imgId]['occid'] = $row->occid;
							$files[$imgId]['photographer'] = $row->photographer;
							$files[$imgId]['owner'] = $row->owner;
							$files[$imgId]['SciName'] = $row->SciName;
							$files[$imgId]['occsciname'] = $row->occsciname;
							$files[$imgId]['photographerName'] = $row->photographerName;
							$files[$imgId]['identifier'] = $row->identifier;
							$imgIdArr[] = $row->imgid;
							$cnt++;
						}
					}
				}
				else{
					if (substr($row->url, 0, 1) == '/'){
						//If imageDomain variable is set within symbini file, image  
						if(isset($GLOBALS['imageDomain']) && $GLOBALS['imageDomain']){
							$file = $GLOBALS['imageDomain'].$row->url;
						}
						else{
							//Use local domain 
							$domain = "http://";
							if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $domain = "https://";
							$domain .= $_SERVER["SERVER_NAME"];
							if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $domain .= ':'.$_SERVER["SERVER_PORT"];
							$file = $domain.$row->url;
						}
					}
					else{
						$file = $row->url;
					}
					if(fopen($file, "r")){
						if($size = getimagesize(str_replace(' ', '%20', $file))){
							$width = $size[0];
							$height = $size[1];
							$files[$imgId]['url'] = $file;
							$files[$imgId]['width'] = $width;
							$files[$imgId]['height'] = $height;
							$files[$imgId]['imgid'] = $row->imgid;
							$files[$imgId]['tid'] = $row->tid;
							$files[$imgId]['occid'] = $row->occid;
							$files[$imgId]['photographer'] = $row->photographer;
							$files[$imgId]['owner'] = $row->owner;
							$files[$imgId]['SciName'] = $row->SciName;
							$files[$imgId]['occsciname'] = $row->occsciname;
							$files[$imgId]['photographerName'] = $row->photographerName;
							$files[$imgId]['identifier'] = $row->identifier;
							$cnt++;
						}
					}
				}
			}
			$rs->free();
			$conn->close();
			$ssIdInfo['files'] = $files;
			$previous = array_merge($previous,$imgIdArr);
			
			if($clId){
				$fp = fopen($SERVER_ROOT.'/temp/slideshow/'.$ssId.'_previous.json', 'w');
				fwrite($fp, json_encode($previous));
				fclose($fp);
			}
			$fp = fopen($SERVER_ROOT.'/temp/slideshow/'.$ssId.'_info.json', 'w');
			fwrite($fp, json_encode($ssIdInfo));
			fclose($fp);
		}
		
		$infoArr = json_decode(file_get_contents($SERVER_ROOT.'/temp/slideshow/'.$ssId.'_info.json'), true);
		//echo json_encode($infoArr);
		return $infoArr;
	}
	
	public function getImageList($imageArr,$width){
		global $clientRoot;
		$windowHeight = $width + 75;
		$imageHeight = $width + 50;
		$html = '';
		$html = '<div id="slideshowcontainer" style="clear:both;width:'.$width.'px;height:'.$windowHeight.'px;">';
		$html .= '<div class="container">';
		$html .= '<div id="slides">';
		foreach($imageArr as $igmAr => $imgIdArr){ 
			$imgSize = '';
			if($imgIdArr["width"] > $imgIdArr["height"]){
				$offSet = (($imgIdArr["width"]/$imgIdArr["height"])*$imageHeight)/2;
				$imgSize = 'height:'.$imageHeight.'px;position:absolute;left:50%;margin-left:-'.$offSet.'px;';
			}
			else{
				$offSet = (($imgIdArr["height"]/$imgIdArr["width"])*$width)/2;
				$imgSize = 'width:'.$width.'px;position:absolute;top:50%;margin-top:-'.$offSet.'px;';
			}
			$linkUrl = '';
			if($imgIdArr["occid"]){
				$linkUrl = $clientRoot.'/collections/individual/index.php?occid='.$imgIdArr["occid"].'&clid=0';
			}
			elseif($imgIdArr["tid"]){
				$name = str_replace(' ','%20',$imgIdArr["SciName"]);
				$linkUrl = $clientRoot.'/taxa/index.php?taxon='.$name;
			}
			$html .= '<div style="width:'.$width.'px;height:'.$imageHeight.'px;position:relative;">';
			$html .= '<div style="width:'.$width.'px;max-height:'.$imageHeight.'px;overflow:hidden;">';
			$html .= '<a href="'.$linkUrl.'" target="_blank">';
			$html .= '<img src="'.$imgIdArr["url"].'" style="'.$imgSize.'" alt="'.($imgIdArr["occsciname"]?$imgIdArr["occsciname"]:$imgIdArr["SciName"]).'">';
			$html .= '</a>';
			$html .= '</div>';
			$html .= '<div style="width:'.$width.'px;position:absolute;bottom:0;font-size:12px;background-color:rgba(255,255,255,0.8);">';
			$onclickText = "toggle('slidecaption".$imgIdArr["imgid"]."');toggle('showcaption".$imgIdArr["imgid"]."');";
			$html .= '<div id="slidecaption'.$imgIdArr["imgid"].'">';
			$html .= '<a style="cursor:pointer;cursor:hand;font-size:9px;text-decoration:none;float:right;clear:both;margin-right:5px;" onclick="'.$onclickText.'">HIDE CAPTION</a>';
			$html .= '<div style="clear:both;padding-left:3px;padding-right:3px;"><b>';
			if($imgIdArr["SciName"] || $imgIdArr["identifier"]){
				$html .= '<a href="'.$linkUrl.'" target="_blank">';
				$html .= ($imgIdArr["identifier"]?$imgIdArr["identifier"]:$imgIdArr["SciName"]);
				$html .= '</a>. ';
			}
			if($imgIdArr["photographer"] || $imgIdArr["photographerName"]){
				$html .= 'Image by: '.($imgIdArr["photographer"]?$imgIdArr["photographer"]:$imgIdArr["photographerName"]).'. ';
			}
			if($imgIdArr["owner"]){
				$html .= 'Courtesy of: '.$imgIdArr["owner"].'. ';
			}
			$html .= '</b></div>';
			$html .= '</div>';
			$html .= '<a id="showcaption'.$imgIdArr["imgid"].'" style="cursor:pointer;cursor:hand;font-size:9px;text-decoration:none;float:right;clear:both;margin-right:5px;display:none;" onclick="'.$onclickText.'">SHOW CAPTION</a>';
			$html .= '</div></div>';
		}
		$html .= '</div></div></div>';
		
		return $html;
	}
	
	public function setShowHtml($imageHtml,$width,$interval){
		global $clientRoot;
		$height = $width + 50;
		$html = '';
		$html .= '<link rel="stylesheet" href="'.$clientRoot.'/css/slideshowstyle.css">';
		$html .= '<style>';
		$html .= '@font-face{';
		$html .= "font-family:'FontAwesome';";
		$html .= "src:url('".$clientRoot."/css/images/fontawesome-webfont.eot?v=3.0.1');";
		$html .= "src:url('".$clientRoot."/css/images/fontawesome-webfont.eot?#iefix&v=3.0.1') format('embedded-opentype'),";
		$html .= "url('".$clientRoot."/css/images/fontawesome-webfont.woff?v=3.0.1') format('woff'),";
		$html .= "url('".$clientRoot."/css/images/fontawesome-webfont.ttf?v=3.0.1') format('truetype');";
		$html .= 'font-weight:normal;';
		$html .= 'font-style:normal}';
		$html .= '[class^="icon-"],[class*=" icon-"]{font-family:FontAwesome;font-weight:normal;font-style:normal;text-decoration:inherit;-webkit-font-smoothing:antialiased;display:inline;width:auto;height:auto;line-height:normal;vertical-align:baseline;background-image:none;background-position:0 0;background-repeat:repeat;margin-top:0}.icon-white,.nav-pills>.active>a>[class^="icon-"],.nav-pills>.active>a>[class*=" icon-"],.nav-list>.active>a>[class^="icon-"],.nav-list>.active>a>[class*=" icon-"],.navbar-inverse .nav>.active>a>[class^="icon-"],.navbar-inverse .nav>.active>a>[class*=" icon-"],.dropdown-menu>li>a:hover>[class^="icon-"],.dropdown-menu>li>a:hover>[class*=" icon-"],.dropdown-menu>.active>a>[class^="icon-"],.dropdown-menu>.active>a>[class*=" icon-"],.dropdown-submenu:hover>a>[class^="icon-"],.dropdown-submenu:hover>a>[class*=" icon-"]{background-image:none}[class^="icon-"]:before,[class*=" icon-"]:before{text-decoration:inherit;display:inline-block;speak:none}a [class^="icon-"],a [class*=" icon-"]{display:inline-block}.icon-large:before{vertical-align:-10%;font-size:1.3333333333333333em}.btn [class^="icon-"],.nav [class^="icon-"],.btn [class*=" icon-"],.nav [class*=" icon-"]{display:inline}.btn [class^="icon-"].icon-large,.nav [class^="icon-"].icon-large,.btn [class*=" icon-"].icon-large,.nav [class*=" icon-"].icon-large{line-height:.9em}.btn [class^="icon-"].icon-spin,.nav [class^="icon-"].icon-spin,.btn [class*=" icon-"].icon-spin,.nav [class*=" icon-"].icon-spin{display:inline-block}.nav-tabs [class^="icon-"],.nav-pills [class^="icon-"],.nav-tabs [class*=" icon-"],.nav-pills [class*=" icon-"],.nav-tabs [class^="icon-"].icon-large,.nav-pills [class^="icon-"].icon-large,.nav-tabs [class*=" icon-"].icon-large,.nav-pills [class*=" icon-"].icon-large{line-height:.9em}li [class^="icon-"],.nav li [class^="icon-"],li [class*=" icon-"],.nav li [class*=" icon-"]{display:inline-block;width:1.25em;text-align:center}li [class^="icon-"].icon-large,.nav li [class^="icon-"].icon-large,li [class*=" icon-"].icon-large,.nav li [class*=" icon-"].icon-large{width:1.5625em}ul.icons{list-style-type:none;text-indent:-0.75em}ul.icons li [class^="icon-"],ul.icons li [class*=" icon-"]{width:.75em}.icon-muted{color:#eee}.icon-border{border:solid 1px #eee;padding:.2em .25em .15em;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}.icon-2x{font-size:2em}.icon-2x.icon-border{border-width:2px;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.icon-3x{font-size:3em}.icon-3x.icon-border{border-width:3px;-webkit-border-radius:5px;-moz-border-radius:5px;border-radius:5px}.icon-4x{font-size:4em}.icon-4x.icon-border{border-width:4px;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px}.pull-right{float:right}.pull-left{float:left}[class^="icon-"].pull-left,[class*=" icon-"].pull-left{margin-right:.3em}[class^="icon-"].pull-right,[class*=" icon-"].pull-right{margin-left:.3em}.btn [class^="icon-"].pull-left.icon-2x,.btn [class*=" icon-"].pull-left.icon-2x,.btn [class^="icon-"].pull-right.icon-2x,.btn [class*=" icon-"].pull-right.icon-2x{margin-top:.18em}.btn [class^="icon-"].icon-spin.icon-large,.btn [class*=" icon-"].icon-spin.icon-large{line-height:.8em}.btn.btn-small [class^="icon-"].pull-left.icon-2x,.btn.btn-small [class*=" icon-"].pull-left.icon-2x,.btn.btn-small [class^="icon-"].pull-right.icon-2x,.btn.btn-small [class*=" icon-"].pull-right.icon-2x{margin-top:.25em}.btn.btn-large [class^="icon-"],.btn.btn-large [class*=" icon-"]{margin-top:0}.btn.btn-large [class^="icon-"].pull-left.icon-2x,.btn.btn-large [class*=" icon-"].pull-left.icon-2x,.btn.btn-large [class^="icon-"].pull-right.icon-2x,.btn.btn-large [class*=" icon-"].pull-right.icon-2x{margin-top:.05em}.btn.btn-large [class^="icon-"].pull-left.icon-2x,.btn.btn-large [class*=" icon-"].pull-left.icon-2x{margin-right:.2em}.btn.btn-large [class^="icon-"].pull-right.icon-2x,.btn.btn-large [class*=" icon-"].pull-right.icon-2x{margin-left:.2em}.icon-spin{display:inline-block;-moz-animation:spin 2s infinite linear;-o-animation:spin 2s infinite linear;-webkit-animation:spin 2s infinite linear;animation:spin 2s infinite linear}@-moz-keyframes spin{0%{-moz-transform:rotate(0deg)}100%{-moz-transform:rotate(359deg)}}@-webkit-keyframes spin{0%{-webkit-transform:rotate(0deg)}100%{-webkit-transform:rotate(359deg)}}@-o-keyframes spin{0%{-o-transform:rotate(0deg)}100%{-o-transform:rotate(359deg)}}@-ms-keyframes spin{0%{-ms-transform:rotate(0deg)}100%{-ms-transform:rotate(359deg)}}@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(359deg)}}@-moz-document url-prefix(){.icon-spin{height:.9em}.btn .icon-spin{height:auto}.icon-spin.icon-large{height:1.25em}.btn .icon-spin.icon-large{height:.75em}}.icon-glass:before{content:"\f000"}.icon-music:before{content:"\f001"}.icon-search:before{content:"\f002"}.icon-envelope:before{content:"\f003"}.icon-heart:before{content:"\f004"}.icon-star:before{content:"\f005"}.icon-star-empty:before{content:"\f006"}.icon-user:before{content:"\f007"}.icon-film:before{content:"\f008"}.icon-th-large:before{content:"\f009"}.icon-th:before{content:"\f00a"}.icon-th-list:before{content:"\f00b"}.icon-ok:before{content:"\f00c"}.icon-remove:before{content:"\f00d"}.icon-zoom-in:before{content:"\f00e"}.icon-zoom-out:before{content:"\f010"}.icon-off:before{content:"\f011"}.icon-signal:before{content:"\f012"}.icon-cog:before{content:"\f013"}.icon-trash:before{content:"\f014"}.icon-home:before{content:"\f015"}.icon-file:before{content:"\f016"}.icon-time:before{content:"\f017"}.icon-road:before{content:"\f018"}.icon-download-alt:before{content:"\f019"}.icon-download:before{content:"\f01a"}.icon-upload:before{content:"\f01b"}.icon-inbox:before{content:"\f01c"}.icon-play-circle:before{content:"\f01d"}.icon-repeat:before{content:"\f01e"}.icon-refresh:before{content:"\f021"}.icon-list-alt:before{content:"\f022"}.icon-lock:before{content:"\f023"}.icon-flag:before{content:"\f024"}.icon-headphones:before{content:"\f025"}.icon-volume-off:before{content:"\f026"}.icon-volume-down:before{content:"\f027"}.icon-volume-up:before{content:"\f028"}.icon-qrcode:before{content:"\f029"}.icon-barcode:before{content:"\f02a"}.icon-tag:before{content:"\f02b"}.icon-tags:before{content:"\f02c"}.icon-book:before{content:"\f02d"}.icon-bookmark:before{content:"\f02e"}.icon-print:before{content:"\f02f"}.icon-camera:before{content:"\f030"}.icon-font:before{content:"\f031"}.icon-bold:before{content:"\f032"}.icon-italic:before{content:"\f033"}.icon-text-height:before{content:"\f034"}.icon-text-width:before{content:"\f035"}.icon-align-left:before{content:"\f036"}.icon-align-center:before{content:"\f037"}.icon-align-right:before{content:"\f038"}.icon-align-justify:before{content:"\f039"}.icon-list:before{content:"\f03a"}.icon-indent-left:before{content:"\f03b"}.icon-indent-right:before{content:"\f03c"}.icon-facetime-video:before{content:"\f03d"}.icon-picture:before{content:"\f03e"}.icon-pencil:before{content:"\f040"}.icon-map-marker:before{content:"\f041"}.icon-adjust:before{content:"\f042"}.icon-tint:before{content:"\f043"}.icon-edit:before{content:"\f044"}.icon-share:before{content:"\f045"}.icon-check:before{content:"\f046"}.icon-move:before{content:"\f047"}.icon-step-backward:before{content:"\f048"}.icon-fast-backward:before{content:"\f049"}.icon-backward:before{content:"\f04a"}.icon-play:before{content:"\f04b"}.icon-pause:before{content:"\f04c"}.icon-stop:before{content:"\f04d"}.icon-forward:before{content:"\f04e"}.icon-fast-forward:before{content:"\f050"}.icon-step-forward:before{content:"\f051"}.icon-eject:before{content:"\f052"}.icon-chevron-left:before{content:"\f053"}.icon-chevron-right:before{content:"\f054"}.icon-plus-sign:before{content:"\f055"}.icon-minus-sign:before{content:"\f056"}.icon-remove-sign:before{content:"\f057"}.icon-ok-sign:before{content:"\f058"}.icon-question-sign:before{content:"\f059"}.icon-info-sign:before{content:"\f05a"}.icon-screenshot:before{content:"\f05b"}.icon-remove-circle:before{content:"\f05c"}.icon-ok-circle:before{content:"\f05d"}.icon-ban-circle:before{content:"\f05e"}.icon-arrow-left:before{content:"\f060"}.icon-arrow-right:before{content:"\f061"}.icon-arrow-up:before{content:"\f062"}.icon-arrow-down:before{content:"\f063"}.icon-share-alt:before{content:"\f064"}.icon-resize-full:before{content:"\f065"}.icon-resize-small:before{content:"\f066"}.icon-plus:before{content:"\f067"}.icon-minus:before{content:"\f068"}.icon-asterisk:before{content:"\f069"}.icon-exclamation-sign:before{content:"\f06a"}.icon-gift:before{content:"\f06b"}.icon-leaf:before{content:"\f06c"}.icon-fire:before{content:"\f06d"}.icon-eye-open:before{content:"\f06e"}.icon-eye-close:before{content:"\f070"}.icon-warning-sign:before{content:"\f071"}.icon-plane:before{content:"\f072"}.icon-calendar:before{content:"\f073"}.icon-random:before{content:"\f074"}.icon-comment:before{content:"\f075"}.icon-magnet:before{content:"\f076"}.icon-chevron-up:before{content:"\f077"}.icon-chevron-down:before{content:"\f078"}.icon-retweet:before{content:"\f079"}.icon-shopping-cart:before{content:"\f07a"}.icon-folder-close:before{content:"\f07b"}.icon-folder-open:before{content:"\f07c"}.icon-resize-vertical:before{content:"\f07d"}.icon-resize-horizontal:before{content:"\f07e"}.icon-bar-chart:before{content:"\f080"}.icon-twitter-sign:before{content:"\f081"}.icon-facebook-sign:before{content:"\f082"}.icon-camera-retro:before{content:"\f083"}.icon-key:before{content:"\f084"}.icon-cogs:before{content:"\f085"}.icon-comments:before{content:"\f086"}.icon-thumbs-up:before{content:"\f087"}.icon-thumbs-down:before{content:"\f088"}.icon-star-half:before{content:"\f089"}.icon-heart-empty:before{content:"\f08a"}.icon-signout:before{content:"\f08b"}.icon-linkedin-sign:before{content:"\f08c"}.icon-pushpin:before{content:"\f08d"}.icon-external-link:before{content:"\f08e"}.icon-signin:before{content:"\f090"}.icon-trophy:before{content:"\f091"}.icon-github-sign:before{content:"\f092"}.icon-upload-alt:before{content:"\f093"}.icon-lemon:before{content:"\f094"}.icon-phone:before{content:"\f095"}.icon-check-empty:before{content:"\f096"}.icon-bookmark-empty:before{content:"\f097"}.icon-phone-sign:before{content:"\f098"}.icon-twitter:before{content:"\f099"}.icon-facebook:before{content:"\f09a"}.icon-github:before{content:"\f09b"}.icon-unlock:before{content:"\f09c"}.icon-credit-card:before{content:"\f09d"}.icon-rss:before{content:"\f09e"}.icon-hdd:before{content:"\f0a0"}.icon-bullhorn:before{content:"\f0a1"}.icon-bell:before{content:"\f0a2"}.icon-certificate:before{content:"\f0a3"}.icon-hand-right:before{content:"\f0a4"}.icon-hand-left:before{content:"\f0a5"}.icon-hand-up:before{content:"\f0a6"}.icon-hand-down:before{content:"\f0a7"}.icon-circle-arrow-left:before{content:"\f0a8"}.icon-circle-arrow-right:before{content:"\f0a9"}.icon-circle-arrow-up:before{content:"\f0aa"}.icon-circle-arrow-down:before{content:"\f0ab"}.icon-globe:before{content:"\f0ac"}.icon-wrench:before{content:"\f0ad"}.icon-tasks:before{content:"\f0ae"}.icon-filter:before{content:"\f0b0"}.icon-briefcase:before{content:"\f0b1"}.icon-fullscreen:before{content:"\f0b2"}.icon-group:before{content:"\f0c0"}.icon-link:before{content:"\f0c1"}.icon-cloud:before{content:"\f0c2"}.icon-beaker:before{content:"\f0c3"}.icon-cut:before{content:"\f0c4"}.icon-copy:before{content:"\f0c5"}.icon-paper-clip:before{content:"\f0c6"}.icon-save:before{content:"\f0c7"}.icon-sign-blank:before{content:"\f0c8"}.icon-reorder:before{content:"\f0c9"}.icon-list-ul:before{content:"\f0ca"}.icon-list-ol:before{content:"\f0cb"}.icon-strikethrough:before{content:"\f0cc"}.icon-underline:before{content:"\f0cd"}.icon-table:before{content:"\f0ce"}.icon-magic:before{content:"\f0d0"}.icon-truck:before{content:"\f0d1"}.icon-pinterest:before{content:"\f0d2"}.icon-pinterest-sign:before{content:"\f0d3"}.icon-google-plus-sign:before{content:"\f0d4"}.icon-google-plus:before{content:"\f0d5"}.icon-money:before{content:"\f0d6"}.icon-caret-down:before{content:"\f0d7"}.icon-caret-up:before{content:"\f0d8"}.icon-caret-left:before{content:"\f0d9"}.icon-caret-right:before{content:"\f0da"}.icon-columns:before{content:"\f0db"}.icon-sort:before{content:"\f0dc"}.icon-sort-down:before{content:"\f0dd"}.icon-sort-up:before{content:"\f0de"}.icon-envelope-alt:before{content:"\f0e0"}.icon-linkedin:before{content:"\f0e1"}.icon-undo:before{content:"\f0e2"}.icon-legal:before{content:"\f0e3"}.icon-dashboard:before{content:"\f0e4"}.icon-comment-alt:before{content:"\f0e5"}.icon-comments-alt:before{content:"\f0e6"}.icon-bolt:before{content:"\f0e7"}.icon-sitemap:before{content:"\f0e8"}.icon-umbrella:before{content:"\f0e9"}.icon-paste:before{content:"\f0ea"}.icon-lightbulb:before{content:"\f0eb"}.icon-exchange:before{content:"\f0ec"}.icon-cloud-download:before{content:"\f0ed"}.icon-cloud-upload:before{content:"\f0ee"}.icon-user-md:before{content:"\f0f0"}.icon-stethoscope:before{content:"\f0f1"}.icon-suitcase:before{content:"\f0f2"}.icon-bell-alt:before{content:"\f0f3"}.icon-coffee:before{content:"\f0f4"}.icon-food:before{content:"\f0f5"}.icon-file-alt:before{content:"\f0f6"}.icon-building:before{content:"\f0f7"}.icon-hospital:before{content:"\f0f8"}.icon-ambulance:before{content:"\f0f9"}.icon-medkit:before{content:"\f0fa"}.icon-fighter-jet:before{content:"\f0fb"}.icon-beer:before{content:"\f0fc"}.icon-h-sign:before{content:"\f0fd"}.icon-plus-sign-alt:before{content:"\f0fe"}.icon-double-angle-left:before{content:"\f100"}.icon-double-angle-right:before{content:"\f101"}.icon-double-angle-up:before{content:"\f102"}.icon-double-angle-down:before{content:"\f103"}.icon-angle-left:before{content:"\f104"}.icon-angle-right:before{content:"\f105"}.icon-angle-up:before{content:"\f106"}.icon-angle-down:before{content:"\f107"}.icon-desktop:before{content:"\f108"}.icon-laptop:before{content:"\f109"}.icon-tablet:before{content:"\f10a"}.icon-mobile-phone:before{content:"\f10b"}.icon-circle-blank:before{content:"\f10c"}.icon-quote-left:before{content:"\f10d"}.icon-quote-right:before{content:"\f10e"}.icon-spinner:before{content:"\f110"}.icon-circle:before{content:"\f111"}.icon-reply:before{content:"\f112"}.icon-github-alt:before{content:"\f113"}.icon-folder-close-alt:before{content:"\f114"}.icon-folder-open-alt:before{content:"\f115"}';
		$html .= 'a.slidesjs-next,';
		$html .= 'a.slidesjs-previous,';
		$html .= 'a.slidesjs-play,';
		$html .= 'a.slidesjs-stop {';
		$html .= 'background-image: url('.$clientRoot.'/css/images/btns-next-prev.png);';
		$html .= 'background-repeat: no-repeat;}';
		$html .= '.slidesjs-pagination li a {';
		$html .= 'background-image: url('.$clientRoot.'/css/images/pagination.png);';
		$html .= 'background-position: 0 0;}';
		$html .= '</style>';
		$html .= '<script src="'.$clientRoot.'/js/jquery-1.9.1.js"></script>';
		$html .= '<script src="'.$clientRoot.'/js/jquery.slides.js"></script>';
		$html .= '<script src="'.$clientRoot.'/js/symb/plugins.js"></script>';
		$html .= $imageHtml;
		$html .= '<script>';
		$html .= '$(function() {';
		$html .= "$('#slides').slidesjs({";
		$html .= 'width: '.$width.',';
		$html .= 'height: '.$height.',';
		$html .= 'play: {';
		$html .= 'active: true,';
		$html .= 'auto: true,';
		$html .= 'interval: '.$interval.',';
		$html .= 'swap: true}});});';
		$html .= '</script>';
		
		return $html;
	}
	
	public function createQuickSearch($buttonText,$searchText=''){
		global $clientRoot;
		$html = '';
		$html .= '<link href="'.$clientRoot.'/css/jquery-ui.css" type="text/css" rel="Stylesheet" />';
        $html .= "<script type='text/javascript'>if(!window.jQuery){";
        $html .= 'var jqresource = document.createElement("script");';
        $html .= 'jqresource.async = "true";';
        $html .= 'jqresource.src = "'.$clientRoot.'/js/jquery.js";';
        $html .= 'var jqscript = document.getElementsByTagName("script")[0];';
        $html .= 'jqscript.parentNode.insertBefore(jqresource,jqscript);';
        $html .= 'var jquiresource = document.createElement("script");';
        $html .= 'jquiresource.async = "true";';
        $html .= 'jquiresource.src = "'.$clientRoot.'/js/jquery-ui.js";';
        $html .= 'var jquiscript = document.getElementsByTagName("script")[0];';
        $html .= 'jquiscript.parentNode.insertBefore(jquiscript,jquiresource);';
        $html .= '}</script>';
		$html .= '<script type="text/javascript">';
		$html .= '$(document).ready(function() {';
		$html .= 'function split( val ) {';
		$html .= 'return val.split( /,\s*/ );}';
		$html .= 'function extractLast( term ) {';
		$html .= 'return split( term ).pop();}';
		$html .= '$( "#quicksearchtaxon" )';
		$html .= '.bind( "keydown", function( event ) {';
		$html .= 'if ( event.keyCode === $.ui.keyCode.TAB &&';
		$html .= '$( this ).data( "autocomplete" ).menu.active ) {';
		$html .= 'event.preventDefault();}})';
		$html .= '.autocomplete({';
		$html .= 'source: function( request, response ) {';
		$html .= '$.getJSON( "'.$clientRoot.'/collections/rpc/taxalist.php", {';
		$html .= 'term: extractLast( request.term ), t: function() { return document.quicksearch.taxon.value; }}, response );},';
		$html .= 'appendTo: "#quicksearchdiv",';
        $html .= 'search: function() {';
		$html .= 'var term = extractLast( this.value );';
		$html .= 'if ( term.length < 4 ) {';
		$html .= 'return false;}},';
		$html .= 'focus: function() {';
		$html .= 'return false;},';
		$html .= 'select: function( event, ui ) {';
		$html .= 'var terms = split( this.value );';
		$html .= 'terms.pop();';
		$html .= 'terms.push( ui.item.value );';
		$html .= 'this.value = terms.join( ", " );';
		$html .= 'return false;}},{});});';
		$html .= 'function verifyQuickSearch(f){';
		$html .= 'if(document.getElementById("quicksearchtaxon").value == ""){';
		$html .= 'alert("Please enter a scientific name to search for.");';
		$html .= 'return false;}';
		$html .= 'return true;}';
		$html .= '</script>';
		$html .= '<form name="quicksearch" id="quicksearch" action="'.$clientRoot.'/taxa/index.php" method="get" onsubmit="return verifyQuickSearch(this.form);">';
		if($searchText){
			$html .= '<div id="quicksearchtext" ><b>'.$searchText.'</b></div>';
		}
		$html .= '<input type="text" name="taxon" id="quicksearchtaxon" title="Enter taxon name here." />';
		$html .= '<button name="formsubmit"  id="quicksearchbutton" type="submit" value="Search Terms">'.$buttonText.'</button>';
		$html .= '</form>';
		
		return $html;
	}
}
?>