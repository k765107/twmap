<?php
$uid = 1;
require_once("../config.inc.php");
if(!ob_start("ob_gzhandler")) ob_start();


$r = $_REQUEST['r'];
$x = $_REQUEST['x'];
$y = $_REQUEST['y'];
$detail = $_REQUEST['detail'];
$dup=array();
$found=0;

if ($r>100)
	ajaxerr("too big range");
if ($r==0)
	ajaxerr("empty");
$data = get_waypoint($x,$y,$r,$detail);
$trk_data = get_track($x,$y,$r,$detail);
// 整理一下 data

if ($data === false || (count($data)==0 && count($trk_data)==0)) {
	header('Access-Control-Allow-Origin: *');
	ajaxerr("empty result");
}
if (empty($detail) || $detail == 0 ){
// 傳回高度
                $twDEM_path = "../db/DEM/twdtm_asterV2_30m.tif";
                $ele = get_elev($twDEM_path, $y, $x, 1);

	header('Access-Control-Allow-Origin: *');
	ajaxok(array("wpt"=>$data,"trk"=>$trk_data,"ele"=>$ele));
} else {
	// web page
	echo "<html>";
	echo "<head><title>TWMAP waypoint detail</title><meta charset=\"UTF-8\">";
	echo "<script src='https://code.jquery.com/jquery-2.1.4.min.js'></script>";
	echo "<style>
	<script src='https://code.jquery.com/jquery-2.1.4.min.js'></script>
	/* Document level adjustments */
html {
  font-size: 17px;
}
@media (max-width: 900px) {
  html,table { font-size: 15px; }
}
@media (max-width: 400px) {
  html,table { font-size: 13px; }
}

table, td, th {
    border: 1px solid green;
}

th {
    background-color: green;
    color: white;
}

</style></head>";
	echo "<body><div id='wpt_info' align=center>";
	echo "<hr>以下 GPS 航跡皆為山友無私貢獻分享,請大家上山前做好準備,快樂出門,平安回家!";
	echo "<br>距座標點". $_REQUEST['r'] ."M 的範圍的航點資訊";
	echo "<table>";
	echo "<tr><th width=30%>名稱<th>高度(M)<th>顯示<th>下載<th>地圖";
	$ans = array();
	$to_show = array();
	foreach($data as $row){
		if (isset($ans[$row['name']][$row['ele']]) && $ans[$row['name']][$row['ele']][0] == $row['title']){
					$row['dup'] = 1;
					// 尚未刪除的
					if ($row['flag'] == 0) {
						// $dup [ current mid ] = original mid, current still exist
						$dup[$row['mid']] = $ans[$row['name']][$row['ele']][1];
					}
		} else {
					$ans[$row['name']][$row['ele']] = array($row['title'],$row['mid']);
					$row['dup'] = 0;
		}
		$to_show[] = $row;
	}
	foreach($to_show as $row){
		if ($row['dup'] == 0 ) {
			// 如果有尚未刪除的, 列出尚未刪除的.
			if (count($dup)>0){
				$found = 0;
				foreach($dup as $d_cur => $d_orig){
					//echo  "$d_cur => $d_orig\n";
					if ($row['mid'] == $d_orig){
						$mid_to_show = $d_cur;
						$found = 1;
						break;
					}
				}
				if (!$found)
					$mid_to_show = $row['mid'];
			} else {
				$mid_to_show = $row['mid'];
			}
			if ($row['flag'] != 2 ) {
				$show_url = sprintf("<a href='/twmap/show.php?mid=%s' target=_blank>%s</a>",$mid_to_show,$row['title']);
			printf("<tr><td>%s<td>%s<td><a href=# class='showkml' data-id='%d' data-title='%s' data-link='%s'>%s</a><td>%s<td>%s",
			$row['name'],$row['ele'],
			$mid_to_show,$row['title'],rawurlencode($show_url),$mid_to_show,
			$show_url,($found)?'<img src="/twmap/icons/op_mapshow.png">':"");
			} else {
			printf("<tr><td>%s<td>%s<td>%s<td>%s<td><img src='/twmap/icons/op_delete.png'/>", $row['name'],$row['ele'], $mid_to_show, $row['title']);
			}


		}
	}
	if (count($trk_data) > 0 ) {
		foreach($trk_data as $row) {
			$mid_to_show = $row['mid'];
			if ($row['flag'] != 2 ) {
				$show_url = sprintf("<a href='/twmap/show.php?mid=%s' target=_blank>%s</a>",$mid_to_show,$row['title']);
				printf("<tr><td>%s<td>航跡<td><a href=# class='showkml' data-id='%d' data-title='%s' data-link='%s'>%s</a><td>%s<td>%s",
						$row['name'],
						$mid_to_show,$row['title'],rawurlencode($show_url),$mid_to_show,
						$show_url,($found)?'<img src="/twmap/icons/op_mapshow.png">':"");
			} else {
				printf("<tr><td>%s<td>航跡<td>%s<td>%s<td><img src='/twmap/icons/op_delete.png'/>", $row['name'], $mid_to_show, $row['title']);
			}

		}
	}
	echo "</table>";
	echo "<hr>";
	if (is_admin()) {
?>
	mid:<input type=text id='kmlshowmid' name='kmlshowmid'><input type=button value="Show" id='kmlbtnshow'>
<?php
	}
?>
<script>
$('document').ready(function(){ 
		$('.showkml').each(function(index) {
			$(this).click(function(event) {
				event.preventDefault();
				parent.showmapkml($(this).data('id'),$(this).data('title'),$(this).data('link'),false);
				});
			});
		$('#kmlbtnshow').click(function() {
			console.log("display mid:" + $("#kmlshowmid").val());
			if ($("#kmlshowmid").val()) 
				parent.showmapkml($("#kmlshowmid").val(),"","",true);
			});
		});
		 $('#kmlshowmid').keypress(function(e){
      if(e.keyCode==13)
      $('#kmlbtnshow').click();
    });
		
</script>
</div>
</html>
<?php
}

/*
   $tlx = $_REQUEST['tlx'];
   $tly = $_REQUEST['tly'];
   $brx = $_REQUEST['brx'];
   $bry = $_REQUEST['bry'];
   $gpx = (isset($_REQUEST['gpx'])) ? intval($_REQUEST['gpx']) : 0 ;
   $keys = (!empty($_REQUEST['keys'])) ? explode(",",$_REQUEST['keys']):array();
// 最多查幾筆
$maxkeys = ($_REQUEST['maxkeys']) ? intval($_REQUEST['maxkeys']) : 0;


if (empty($tlx) || empty($tly) || empty($brx) || empty($bry)) {
ajaxerr("insufficent parameters");
}

$bounds = array("tlx" => $tlx, "tly" => $tly, "brx" => $brx, "bry" => $bry );

$data = map_overlap($bounds, $gpx, $maxkeys);
/*
$mids = array();
$ret = array("add" => array(), "del" => array(), "all" => array(), "count"=> array("add" => 0 , "del" => 0 ));
foreach($data as $map) {
if ($map['hide'] == 1) continue;
if (!in_array($map['mid'],$keys)) {

$content =  sprintf("<a href='%s%s/show.php?mid=%s&info=%s&version=%d' target=_twmap>%s<img src='img/map.gif' title='地圖產生器' border=0/></a>",$site_url,$site_html_root, $map['mid'], urlencode(sprintf("%dx%s-%dx%d",$map['locX'],$map['locY'],$map['shiftX'],$map['shiftY'])), $map['version'], $map['title']);
if ($map['keepon_id'])
$content .= sprintf("<a href='http://www.keepon.com.tw/DocumentHandler.ashx?id=%s' target='_keepon'>%s</a>",$map['keepon_id'],"連結登山補給站");


$ret['add'][$map['mid']] = array('url' => sprintf('%s%s/api/getkml.php?mid=%d',$site_url, $site_html_root, $map['mid']),
'desc' =>  $content );
} 
$ret['all'][] = $map['mid'];
$mids[] = $map['mid'];
}
foreach($keys as $key) {
if (!in_array($key, $mids)) {
$ret['del'][$key] = 1;
}
}

$ret['count']['add'] = count($ret['add']);
$ret['count']['del'] = count($ret['del']);
ajaxok($ret);
 */
