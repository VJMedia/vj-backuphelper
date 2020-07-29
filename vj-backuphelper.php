<?php
/*
Plugin Name: 輔仁網: Backup Dashboard Monitor
Description:
Version: 1.0
Author: <a href="http://www.vjmedia.com.hk/">技術組</a>
GitHub Plugin URI: https://github.com/VJMedia/vj-backuphelper
*/

defined('WPINC') || (header("location: /") && die());

/* Date File Generator */

/*register_activation_hook(__FILE__, 'vjbh_activation');

function vjbh_activation() {
	if (! wp_next_scheduled ( 'vjbh_event' )) {
		wp_schedule_event(time(), 'hourly', 'vjbh_event');
	}
	vjbh_do();
}

add_action('vjbh_event', 'vjbh_do');

function vjbh_do() {
	$upload_dir=wp_upload_dir()["basedir"];
	file_put_contents($upload_dir."/vj-backuphelper.dat",current_time("YmdHis"));
}
*/

/* Home Dashboard (VirtusJustitia Only) */
function vjbh_dashboardwidget( $post, $callback_args ) {
	$ctx = stream_context_create(array('http'=> array( 'timeout' => 3, ) ));

	//$server=["backup1","backup2","casper"];
	$server=["backup1","casper"];
	$datatype=["web","db","uploads","log",];
	foreach($server as $row){
		$data[$row]=json_decode(file_get_contents("http://".$row,false,$ctx));
	}
	

	foreach($data as $key=>$row){
		foreach($datatype as $type){
			foreach($row->$type as $entry=>$null){
				$dataentry[$type][]=$entry;
			}
			$dataentry[$type]=array_unique($dataentry[$type]);
		}
	}
	
	foreach($datatype as $type){
		echo "<table style=\"width: 100%;\">";
		
		echo "<tr><td><b>{$type}</b></td>";
		foreach($server as $row){ echo "<td width=\"1\">{$row}</td>"; }
		echo "</tr>";
		
		foreach($dataentry[$type] as $entry){
			echo "<tr><td>";
			echo $entry;
			echo "</td>";
			foreach($server as $row){
				echo "<td>";
				$offset=current_time("timestamp")-strtotime($data[$row]->$type->$entry);
				$color=$offset > 86400*2 ? "red" : "green";
				$offset=round($offset/60);
				echo "<div style=\"width: 100%; background-color: {$color}; color: white;\">{$offset}</div>";
				echo "</td>";
			}
			echo "</tr>";
		}
		
		echo "</table>";
		echo "<hr />";
	}
	echo "<div>數字最後同步時間分鐘差，綠色代表正常，紅色請通知羊羊</div>";
}






function vjbh_adddashboardwidgets() {
	wp_add_dashboard_widget('vjbh_dashboardwidget', 'VJMedia Backup Status', 'vjbh_dashboardwidget');
} add_action('wp_dashboard_setup', 'vjbh_adddashboardwidgets' );

/*vjbh_activation();*/




























function vjdf_dashboardwidget( $post, $callback_args ) {
	$ctx = stream_context_create(array('http'=> array( 'timeout' => 3, ) ));

	$server=["backup1",/*"backup2",*/"casper"];
	foreach($server as $row){
		$data[$row]=json_decode(file_get_contents("http://".$row."/df.php",false,$ctx));
	}
	
	foreach($data as $key => $df){
		echo "<table style=\"width: 100%;\">";
			

		echo "<tr><td style=\"width:64px;\">";
		echo $key;
		echo "</td><td>";
		foreach($df as $path=>$precent){
			echo "<table><tr><td style=\"width: 64px;\">{$path}</td><td style=\"width: 32px;\">{$precent}%</td><td style=\"width:192px;\"><div style=\"width: {$precent}%; background-color: ".($precent >=95 ? "red" : "green").";\">&nbsp;</div></td></tr></table>";
		}
		echo "</tr>";
		
		echo "</table>";
		echo "<hr />";
	}
	
}






function vjdf_adddashboardwidgets() {
	wp_add_dashboard_widget('vjdf_dashboardwidget', 'VJMedia DF Status', 'vjdf_dashboardwidget');
} add_action('wp_dashboard_setup', 'vjdf_adddashboardwidgets' );


function vjgd_dashboardwidget( $post, $callback_args ) {
	$result=shell_exec("/home/vjmedia/gopath/bin/gdrive-sora list -q 'parents in \"1xBIrrXaBocIiy1Pwt1LPZa5F236fXFI6\"'");
	
	if(! preg_match("/^Id\s+Name\s+Type\s+Size\s+Created$/",explode("\n",$result)[0])){
		echo "<span style='color: red'>輸出不符合預期，請聯絡羊</span>";
	}else{
		$result=explode("\n",$result);
		array_shift($result);
		
		echo "<table style=\"width: 100%;\">";
		echo "<tr><td><b>web</b></td><td><b>size</b></td><td><b>timestamp</b></td></tr>";
		foreach($result as $line){
			
			if(preg_match("/^(.*?)\s+(.*?)\s+dir\s+\d\d\d\d-\d\d-\d\d\s+\d\d:\d\d:\d\d$/",$line,$parsed_line)){
				
				$site_id=$parsed_line[1];
				$site_name=$parsed_line[2];
				echo "<tr><td>{$site_name}</td>";
				
				$result2=explode("\t",shell_exec("/home/vjmedia/gopath/bin/gdrive-sora list -q 'parents in \"{$site_id}\"'  --order \"name desc\"  | sed -n 2p | awk '{ print \$4,\"\\011\",\$6,\"\\040\",$7; }'"));
				echo "<td>";
				echo $result2[0]."MB";
				
				$offset=current_time("timestamp")-strtotime($result2[1]);
				$color=$offset > 86400*2 ? "red" : "green";
				
				echo "</td><td style=\"background-color: {$color}; color: white;\">";
				echo $result2[1];
				echo "</td></tr>";
			}
		}
		echo "</table>";
	}
	
	echo "<hr />";
	$result=shell_exec("/home/vjmedia/gopath/bin/gdrive-sora list -q \"'11mIZeWcvd_AnKq5l_j8Lh_v_bpvMyQvU' in parents\" | tail -n +2 |  awk '{ print \$1,\$2; }'");
	
	//echo $result;
	$result=explode("\n",$result);
	$query=[];
	foreach($result as $line){
		if(strlen($line)){
			//echo $line."\n";
			if(preg_match("/^(.*?) (.*?)$/",$line,$parsed_line)){
				$query[]="'{$parsed_line[1]}' in parents";
			}else{
				echo "<span style='color: red'>輸出不符合預期，請聯絡羊</span>";
				break;
			}
		}
	}
	
	$query=implode(" or ", $query);
	$result=shell_exec($q="/home/vjmedia/gopath/bin/gdrive-sora list -q \"{$query}\" --order \"modifiedTime desc\" -m 8 | tail -n +2 | awk '{ print \$2,\$4,\$5,\$6,\$7; }'");
	//echo $q;
	
	$result=explode("\n",$result);
	array_pop($result);
	echo "<table style=\"width: 100%;\">";
	echo "<tr><td><b>db</b></td><td><b>size</b></td><td><b>timestamp</b></td></tr>";
		
	foreach($result as $line){
		$line=explode(" ",$line);
		//var_dump($line);
		preg_match("/^(.*?)\-(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)\.sql\.gz$/",$line[0],$parsed_line[0]);
		$offset=current_time("timestamp")-strtotime($parsed_line[0][2]."-".$parsed_line[0][3]."-".$parsed_line[0][4]." ".$parsed_line[0][5].":".$parsed_line[0][6].":".$parsed_line[0][7]);
		$color=$offset > 86400*2 ? "red" : "green";
		
		
		echo "<tr><td>";
		echo $parsed_line[0][1];
		echo "</td><td>";
		echo $line[1].$line[2];
		echo "</td><td style=\"background-color: {$color}; color: white;\">";
		echo $parsed_line[0][2]."-".$parsed_line[0][3]."-".$parsed_line[0][4]." ".$parsed_line[0][5].":".$parsed_line[0][6].":".$parsed_line[0][7];
		echo "</td></tr>";
	}
	echo "</table>";
	
	
}

function vjgd_adddashboardwidgets() {
	wp_add_dashboard_widget('vjgd_dashboardwidget', '羊學生無限Google Drive','vjgd_dashboardwidget');
} add_action('wp_dashboard_setup', 'vjgd_adddashboardwidgets' );




?>
