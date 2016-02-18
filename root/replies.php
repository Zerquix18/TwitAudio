<?php
/**
* Here we manage replies c;
* This is a beautiful copy/paste of audios.php
*
* @author Zerquix18
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/root/load.php';
$_ADM_BODY['page'] = 'replies';
adm_load_template('header'); ?>
<h3>Replies</h3>
<?php
if( 'POST' === getenv('REQUEST_METHOD') ) {
	try {
		$id = validate_args( $_POST['id'] ) ? trim($_POST['id']) : '';
		if( empty($id) )
			throw new Exception('ID cannot be empty');
		$db->query("DELETE FROM audios WHERE id = ?", $id);
		if( $db->mysqli->affected_rows == 0 )
			throw new Exception('No audios were deleted the audio does not exist');
		$db->query("DELETE FROM favorites WHERE audio_id = ?", $id);
		$db->query("DELETE FROM plays WHERE audio_id = ?", $id);
		echo 'Audio successfully deleted';
	} catch (Exception $e) {
		// :v
		echo 'Error papuh: ' . $e->getMessage();
	}
}
?>
<div class="row">
	<div class="col l12">
		<div class="box">
			<p class="title">Last audios</p>
			<table
			class="centered bordered highlight">
				<tr>
					<th>ID</th>
					<th>Author</th>
					<th>Description</th>
					<th>Replying to</th>
					<th>Date</th>
				</tr>
<?php
	//todo: comment this
	$qe = 'SELECT * FROM audios
		WHERE reply_to != \'0\'
		ORDER BY `time` DESC';
	$count = $db->query(
		'SELECT COUNT(*) AS size FROM audios
		WHERE reply_to != \'0\'
		ORDER BY `time` DESC'
	);
	$page = validate_args($_GET['p']) ?
		sanitize_pageNumber( $_GET['p'] ) : 1;
	$limit = ' LIMIT '. ($page-1) * 10 . ',' . 10;
	$q = $db->query( $qe . ' ' . $limit );
	$pages = ceil( $count->size / 10 );
	while( $r = $q->r->fetch_array() ): ?>
				<tr>
					<td>
						<?php echo $r['id'] ?>
					</td>
					<td>
		<?php
		$a = $db->query('SELECT user FROM users
			WHERE id = ?', $r['user']);
		echo $a->user;
		?>
					</td>
					<td>
		<?php echo htmlspecialchars(
				$r['description'],
				ENT_QUOTES,
				'utf-8'
			)
		?>
					</td>
					<td>
		<?php
			echo $r['reply_to']
		?>
					</td>
					<td>
		<?php echo d_diff( $r['time'] ) ?>
					</td>
				</tr>
	<?php
	endwhile;
?>
			</table>
			todo:pagination
		</div>
	</div>
	<div class="col l6">
		<div class="box">
			<p class="title">Delete reply</p>
			<form method="POST" id="form_delete">
				<div class="input-field">
				<input
					type="text"
					name="id"
					required
				>
				<label>
					<i class="fa fa-pencil"></i>
					&nbsp;Reply ID *
				</label>
				</div>
				<input type="submit" class="btn">
			</form>
		</div>
	</div>
</div>
<?php
adm_load_template('footer');