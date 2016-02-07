<?php
/**
* Here we manage audios
*
* @author Zerquix18
*
**/
require $_SERVER['DOCUMENT_ROOT'] . '/root/load.php';
$_ADM_BODY['page'] = 'audios';
adm_load_template('header'); ?>
<h3>Audios</h3>
<?php
if( 'POST' === getenv('REQUEST_METHOD') ) {
	try {
		$id = validate_args( $_POST['id'] ) ? trim($_POST['id']) : '';
		if( empty($id) )
			throw new Exception('ID cannot be empty');
		// i don't even trust myself
		$id = $db->real_escape($id);
		$reason = validate_args( $_POST['reason'] ) ?
			trim( $_POST['reason'] )
		:
			'';
		if( empty( $reason ) ){
			// I am sorry babe
			$exists = $db->query('SELECT audio FROM audios WHERE id = ?',
				$db->real_escape($id)
			);
			$db->query("DELETE FROM audios WHERE id = ?", $id);
			$db->query("DELETE FROM favorites WHERE audio_id = ?", $id);
			$db->query("DELETE FROM plays WHERE audio_id = ?", $id);
			$db->query("DELETE FROM audios WHERE reply_to = ?", $id);
			@unlink(
				$_SERVER['DOCUMENT_ROOT'] .
				'assets/audios/' . $exists->audio
			);
			if( $db->mysqli->affected_rows == 0 )
				throw new Exception('No audios were deleted the audio does not exist');
		}else{
			$db->update('audios', array(
					'status' => '0',
					'delete_reason' =>
					$db->real_escape($reason)
				)
			)->where('id', $id)->_();
		}
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
					<th>Replies</th>
					<th>Date</th>
					<th>Plays</th>
					<th>Duration</th>
					<th>Status</th>
				</tr>
<?php
	//todo: comment this
	$qe = 'SELECT * FROM audios
		WHERE reply_to = \'0\'
		ORDER BY `time` DESC';
	$count = $db->query(
		'SELECT COUNT(*) AS size FROM audios
		WHERE reply_to = \'0\'
		ORDER BY `time` DESC'
	);
	$page = isset($_GET['p']) && is_numeric($_GET['p']) ? $_GET['p'] : 1;
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
		$c = $db->query(
				'SELECT COUNT(*) AS size FROM audios
				WHERE reply_to = ?',
				$r['id']
			);
		echo $c->size;
		?>
					</td>
					<td>
		<?php echo d_diff( $r['time'] ) ?>
					</td>
					<td>
		<?php echo $r['plays'] ?>
					</td>
					<td>
		<?php echo $r['duration'] ?>s
					</td>
					<td>
		<?php echo $r['status'] ?>
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
			<p class="title">Delete audio</p>
			<form method="POST" id="form_delete">
				<div class="input-field">
				<input
					type="text"
					name="id"
					required
				>
				<label>
					<i class="fa fa-pencil"></i>
					&nbsp;Audio ID *
				</label>
				</div>
				<div class="input-field">
					<textarea class="materialize-textarea" name="reason" maxlength="200" maxlength="200"></textarea>
					<label id="label_reply">
						<i class="fa fa-pencil"></i>
					&nbsp;Is there a reason?
						</label>
				</div>
				<input type="submit" class="btn">
			</form>
		</div>
	</div>
	<div class="col s4">
		<div class="box">
			<p class="title">Help</p>
			If you delete an audio with no reason<br>
			The audio will be permanently deleted from the database<br>
			But if it has a reason <br>
			It won't be deleted and the user <br>
			Will be able to see it.
		</div>
	</div>
</div>
<?php
adm_load_template('footer');