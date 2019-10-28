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

	$server=["backup1","backup2","casper"];
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

	$server=["backup1","backup2","casper"];
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
?>
