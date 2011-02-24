<?php 
/**
 * MediaWiki Changelog Graphs
 * For documentation, please see https://github.com/aaronpk/MediaWiki-Changelog-Graphs
 *
 * @ingroup Extensions
 * @author Aaron Parecki
 * @version 0.9
 */

$wgSpecialPages['ChangeGraphs'] = 'pkSpecialChangeGraphs';
$wgHooks['BeforePageDisplay'][] = 'pkSpecialChangeGraphsBeforePage';

$wgCategoryTreeExtPath = '/extensions/CategoryTree';

function pkSpecialChangeGraphsBeforePage()
{
	global $wgOut, $wgScriptPath;	
	$wgOut->addScriptFile($wgScriptPath . '/extensions/ChangeGraphs/Raphael.js');
	$wgOut->addScriptFile($wgScriptPath . '/extensions/ChangeGraphs/dots.js');
	$wgOut->addInlineScript('
		$(function(){
			$("#dotchart1").dotChart("chart1");
			$("#dotchart2").dotChart("chart2");
		});
	');
	return true;
}

class pkSpecialChangeGraphs extends SpecialPage
{
	function __construct()
	{
		SpecialPage::SpecialPage('ChangeGraphs');
	}
	
	function execute()
	{
		global $wgOut, $wgAction, $wgRequest;

		$wgOut->setPageTitle('Changelog Graphs');

		$this->dayVsHour();
		$this->monthVsDay();
	}
	
	
	function monthVsDay()
	{
		global $wgOut;
		
		$db = wfGetDB(DB_SLAVE);
		$res = $db->select('revision', 
			array('DAYOFWEEK(rev_timestamp) AS `day`', 'MONTH(rev_timestamp) AS `month`', 'COUNT(1) AS `num`'),
			'',
			__METHOD__,
			array('GROUP BY' => 'DAYOFWEEK(rev_timestamp), MONTH(rev_timestamp)'));

		ob_start();
		
		$data = array();
		for($d=1; $d<=7; $d++)
			for($m=1; $m<=12; $m++)
				$data[$d][$m] = 0;
		
		foreach($res as $row) {
			$data[$row->day][$row->month] = $row->num;
		}
		
		echo '<table id="dotchart2">';
		echo '<tfoot>';
			echo '<tr>';
				for($m=1; $m<=12; $m++)
					echo '<th>' . $this->monthName($m) . '</th>';
			echo '</tr>';
		echo '</tfoot>';
		echo '<tbody>';
		foreach($data as $day=>$row) {
			echo '<tr>';
			echo '<th scope="row">' . $this->dayName($day) . '</th>';
			foreach($row as $month=>$num) {
				echo '<td>' . $num . '</td>';
			}
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
		echo '<div id="chart2" style="margin-top: 40px;"></div>';
			
		$wgOut->addHTML(ob_get_clean());
	}

	
	function dayVsHour()
	{
		global $wgOut;
		
		$db = wfGetDB(DB_SLAVE);
		$res = $db->select('revision', 
			array('DAYOFWEEK(rev_timestamp) AS `day`', 'MOD(HOUR(rev_timestamp)+16, 24) AS `hour`', 'COUNT(1) AS `num`'),
			'',
			__METHOD__,
			array('GROUP BY' => 'DAYOFWEEK(rev_timestamp), MOD(HOUR(rev_timestamp)+16, 24)'));

		ob_start();
		
		$data = array();
		for($d=1; $d<=7; $d++)
			for($h=0; $h<24; $h++)
				$data[$d][$h] = 0;
		
		foreach($res as $row) {
			$data[$row->day][$row->hour] = $row->num;
		}
		
		echo '<table id="dotchart1">';
		echo '<tfoot>';
			echo '<tr>';
				echo '<td>';
				for($h=0; $h<24; $h++)
					echo '<th>' . $this->hourName($h) . '</th>';
			echo '</tr>';
		echo '</tfoot>';
		echo '<tbody>';
		foreach($data as $day=>$row) {
			echo '<tr>';
			echo '<th scope="row">' . $this->dayName($day) . '</th>';
			foreach($row as $hour=>$num) {
				echo '<td>' . $num . '</td>';
			}
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
		echo '<div id="chart1"></div>';
			
		$wgOut->addHTML(ob_get_clean());
	}
	
	function dayName($num)
	{
		$days = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
		return $days[$num-1];
	}

	function monthName($num)
	{
		$months = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
		return $months[$num-1];
	}
	
	function hourName($num)
	{
		$hours = array('12am', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, '12pm', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11);
		return $hours[$num];
	}
}

