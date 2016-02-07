<?php
/**
* Body file for the administration
* Functions related to HTML and front-end
*
**/
/**
* All the front-end / HTML related data for the admin
**/
$_ADM_BODY = array();
/**
* Loads a template from assets/templates
* These templates are partial, ex: header, footer
* And not for entire pages
**/
function adm_load_template( $name ) {
	global $db, $_ADM_BODY, $_USER, $lenguajeso, $lenguajest;
	$f = $_SERVER['DOCUMENT_ROOT'] .
		'/root/assets/templates/' . $name . '.phtml';
	if( ! file_exists($f) )
		return false;
	require $f;
}
/**
* Loads a template from assets/full/templates
* These templates are for whole pages
* Ex: audios, frame, etc.
* And they require the header/footer template
**/
function adm_load_full_template( $name ) {
	global $db, $_ADM_BODY, $_USER, $lenguajeso, $lenguajest;
	$f = $_SERVER['DOCUMENT_ROOT'] .
		'/root/assets/templates/full/' . $name . '.phtml';
	if( ! file_exists($f) )
		return false;
	require $f;
}
/**
* Returns the full url to a style in assets/css directory
* It must end with .css
* @return string|void
**/
function adm_load_style( $style, $return = false ) {
	if( $return )
		return url() . 'assets/css/' . $style;
	echo url() . 'root/assets/css/' . $style;
}
/**
* Returns the full url to a script in assets/js directory
* It must end with .js
* @return string|void
**/
function adm_load_script( $js, $return = false ) {
	if( $return )
		return url() . 'assets/js/' . $js;
	echo url() . 'root/assets/js/' . $js;
}
/**
* Returns the full url to a style in assets/img directory
* @return string|void
**/
function adm_load_img( $img, $return = false ) {
	if( $return )
		return url() . 'assets/img/' . $img;
	echo url() . 'root/assets/img/' . $img;
}
/**
* Returns a FULL JSON String
* for Morris Area Chart
* Showing the last week stats
* for $table
* @param string $table
* @param string ($query) (to make a custom query)
* @return string
**/
function load_stats( $table, $query = '' ) {
	global $db;
	//todo: make it monthly and yearly
	$q = 'SELECT COUNT(*) AS size FROM ' . $table;
	$q .= 'WHERE `time` BETWEEN ? AND ?';
	if( ! empty($query) )
		$q = $query;
	// get the UNIX timestamps for every day from the first hour
	// till the end
	// and run a query to check how many fields there are
	$data = array();
	for($i = 6; $i > 0; $i--) {
		$qry = $db->query(
				$q,   // -$i days at 00:00:00
	/* start */	$s =	strtotime('-' . $i . ' days 00:00:00'),
	/* end  */		strtotime('-' . $i . ' days 23:59:59')	
			);
		$data[] = array(
				'day'	=> date('d/m', $s),
				'count' => (int) $qry->count,
			);
	}
	$return = array(
			'element'	=> 'chart',
			'data' 		=> $data,
			'xkey' 		=> 'count',
			'ykeys' 	=> array('day'),
			'labels'		=> array('Total ' . $table),
		);
	return json_encode($return);
}