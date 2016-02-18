<?php
/**
* Body file
* Functions related to HTML and front-end
*
**/
/**
* Stores all the info about the logged user
*
* @var object
*
**/
$_USER = ( $id = is_logged() ) ?
	$db->query("SELECT * FROM users WHERE id = ?", $id )
:
	NULL;
/**
* All the front-end / HTML related data
**/
$_BODY = array();
$_BODY['meta'] = array(
		'title' => __('TwitAudio - Share voice notes and audio files using Twitter'),
		'robots' => true
	);
/**
* Loads a template from assets/templates
* These templates are partial, ex: header, footer
* And not for entire pages
**/
function load_template( $name ) {
	global $db, $_BODY, $_USER, $lenguajeso, $lenguajest;
	$f = $_SERVER['DOCUMENT_ROOT'] .
		'/assets/templates/' . $name . '.phtml';
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
function load_full_template( $name ) {
	global $db, $_BODY, $_USER, $lenguajeso, $lenguajest;
	$f = $_SERVER['DOCUMENT_ROOT'] .
		'/assets/templates/full/' . $name . '.phtml';
	if( ! file_exists($f) )
		return false;
	require $f;
}
/**
* Returns the full url to a style in assets/css directory
* It must end with .css
* @return string|void
**/
function load_style( $style, $return = false ) {
	if( $return )
		return url() . 'assets/css/' . $style;
	echo url() . 'assets/css/' . $style;
}
/**
* Returns the full url to a script in assets/js directory
* It must end with .js
* @return string|void
**/
function load_script( $js, $return = false ) {
	if( $return )
		return url() . 'assets/js/' . $js;
	echo url() . 'assets/js/' . $js;
}
/**
* Returns the full url to a style in assets/img directory
* @return string|void
**/
function load_img( $img, $return = false ) {
	if( $return )
		return url() . 'assets/img/' . $img;
	echo url() . 'assets/img/' . $img;
}
function alert_error($error) {
	echo '<div class="alert error">'. $error . '</div>';
}
function alert_info($info) {
	echo '<div class="alert info">'. $info . '</div>';
}
function get_image( $link, $size = '' ){
	$hola = explode(".", $link);
	$format = end($hola);
	$hola = explode("_", $link);
	array_pop($hola);
	$link = implode("_", $hola);
	if( $size == 'bigger' )
		return $link . '_bigger.'. $format;
	elseif($size == '')
		return $link . '.' . $format;
	else
		return $link . '_normal.' . $format;
}
function verified( $numb ) {
	if( ! (int) $numb )
		return;
	echo '<i class="fa fa-check verified" title="'. __('Verified account') .'"></i>';
}
function display_audio( $audio, $big = false ) {
	global $db, $_USER;
	$user = ( is_logged() && $audio->user == $_USER->id) ?
		$_USER // if its the same user, dont do an extra query
	:
		$db->query(
			'SELECT * FROM users WHERE id = ?',
			$audio->user
		);
	if( is_logged() ):
		$is_faved = $db->query(
			"SELECT COUNT(*) AS size FROM favorites
			WHERE audio_id = ? AND user_id = ?",
			$audio->id,
			$_USER->id
		);
		$is_faved = (int) $is_faved->size;
	endif;
	$replies_count = $db->query(
			'SELECT COUNT(*) AS size FROM audios
			WHERE reply_to = ? AND status = \'1\'',
			$audio->id
		);
	$replies_count = (int) $replies_count->size;
	$image_size = $big ? 'bigger' : 'normal';
	$profile_url = url() . 'audios/' . $user->user
?>
<div class="audio <?php
	if($big) echo 'big';
	echo ' audio_' . $audio->id;
	?>" id="<?php echo $audio->id ?>">
	<div class="audio_header">
		<a href="<?php echo url() . 'audios/'. $user->user ?>">
			<img class="circle"
			src="<?php echo get_image($user->avatar, $size) ?>"
			onerror="this.src='<?php echo url() . 'assets/img/unknown.png' ?>'"
		<?php if($big): ?>
			height="73"
			width="73"
		<?php else: ?>
			height="48"
			width="48"
		<?php endif ?>
			>
		</a>
		<span class="name">
			<a
			class="nodeco"
			href="<?php echo $profile_url ?>"
			>
				<?php echo htmlspecialchars(
					$user->name,
					ENT_QUOTES,
					'utf-8'
				)
				?>
			</a>
		</span>
		<span class="uname">
			<a
			class="nodeco"
			href="<?php echo $profile_url ?>"
			>
				@<?php echo $user->user ?>
			</a>
		</span>
		<span class="adate">
			<i class="fa fa-clock-o grey-text lighten-1-text"></i>&nbsp;
			<a href="<?php
			echo url();
			if( $audio->reply_to != '0' )
				echo $audio->reply_to .
				'?reply_id=' . $audio->id;
			else
				echo $audio->id
			?>">
				<?php echo d_diff( $audio->time ) ?>
			</a>
		</span>
		<?php
		if( defined('LINKED')
			&& $audio->id === constant('LINKED') 
			):
		?>
		<div class="chip" id="linked">
			<i class="fa fa-link"></i>&nbsp;
			<?php _e('Linked reply') ?>
		</div>
		<?php endif ?>
	</div>
	<?php if( ! empty($audio->description) ): ?>
		<div class="audio_desc">
			<?php echo sanitize( $audio->description ) ?>
		</div>
	<?php endif; if( ! empty( $audio->audio ) ):  // if its not a reply?>
	<div class="audio_play">
	<script>
$(document).ready( function() {
		$("#player_<?php echo $audio->id ?>").jPlayer({
		ready: function(event) {
		$(this).jPlayer("setMedia", {
			mp3: "<?php
			echo url() . 'assets/audios/' . $audio->audio
			?>",
		});
		},
		play: function() {
			$(".jp-jplayer").not(this).jPlayer("pause");
		},
		cssSelectorAncestor : '#container_<?php echo $audio->id ?>',
		swfPath: swfpath,
		supplied: "mp3",
		wmode: "window",
		useStateClassSkin: true,
		autoBlur: false,
		smoothPlayBar: true,
		keyEnabled: true,
		remainingDuration: true,
		toggleDuration: true
	});
});
</script>
<div id="player_<?php echo $audio->id ?>" class="jp-jplayer"></div>
    <div id="container_<?php echo $audio->id ?>" class="jp-audio sm">
        <div class="jp-type-single">
            <div class="jp-gui jp-interface">
                <ul class="jp-controls">
                    <li><a href="javascript:;" class="jp-play plei" data-id="<?php echo $audio->id ?>" tabindex="1"><i class="fa fa-play control"></i></a></li>
                    <li><a href="javascript:;" class="jp-pause" tabindex="1"><i class="fa fa-pause control"></i></a></li>
                    <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
                    <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute"><i class="fa fa-volume-up vcontrol"></i></a></li>
                    <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute"><i class="fa fa-volume-down vcontrol"></i></a></li>
                    <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
                </ul>
                <div class="jp-progress">
                    <div class="jp-seek-bar">
                        <div class="jp-play-bar"></div>
                    </div>
                </div>
                <div class="jp-volume-bar">
                    <div class="jp-volume-bar-value"></div>
                </div>
                <div class="jp-current-time"></div>
                <div class="jp-duration"></div>
            </div>
            <div class="jp-no-solution">
                <?php _e('<span>Update Required</span>
                To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>') ?>.
            </div>
        </div>
    </div>
	</div>
	<?php endif # / if its not a reply ?>
	<div class="audio_footer">
	<?php if( ! empty($audio->audio) ): # if not a reply ?>
		<a
		class="audiobtn"
		id="plays_<?php echo $audio->id ?>"
		title="<?php
		echo _n(
				__('%d person played this'),
				__('%d people have played this'),
				$audio->plays
			);
		?>"
		>
			<i class="fa fa-headphones"></i>&nbsp;
			<span>
				<?php echo format_number($audio->plays) ?>
			</span>
		</a>
	<?php endif ?>
		<a class="audiobtn
		<?php if( is_logged() ):
			echo 'laic';
			if($is_faved)
				echo ' favorited';
			endif ?>"
		data-id="<?php echo $audio->id ?>"
		title="<?php _e('Mark as favorite') ?>">
			<i class="fa fa-star"></i>&nbsp;
			<span>
				<?php echo format_number($audio->favorites) ?>
			</span>
		</a>
	<?php if( ! empty($audio->audio) ): # if not a reply ?>
		<a class="audiobtn"
		      href="<?php echo url() . $audio->id ?>#replies"
		      title="<?php _e('Leave a reply') ?>"
		      >
			<i class="fa fa-reply"></i>&nbsp;
			<span>
				<?php echo format_number($replies_count) ?>
			</span>
		</a>
	<?php endif;
		if( is_logged() && $audio->user == $_USER->id ): ?>
		<a
		href="javascript:void(0);"
		class="audiobtn delit"
		data-id="<?php echo $audio->id ?>"
		title="<?php _e('Delete this audio') ?>"
		>
			<i class="fa fa-times"></i> <?php _e('Delete') ?>
		</a>
		<?php endif ?>
	</div>
	<div class="divider"></div>
</div>
<?php
}
function display_user( $user ) { ?>
<ul class="user">
	<li>
		<a href="<?php echo url() . 'audios/' . $user->user ?>">
			<img class="circle"
			src="<?php echo $user->avatar ?>"
			onerror="this.src='<?php load_img('unknowin.png') ?>'"
			height="48"
			width="48"
			>
		</a>
	</li>
	<li class="name">
		<a href="<?php echo url() . 'audios/' . $user->user ?>">
			<?php
			echo htmlspecialchars(
				$user->name,
				ENT_QUOTES,
				'utf-8'
			);
			verified($user->verified);
			?>
		</a>
	</li>
	<li class="uname">
		<a href="<?php echo url() . 'audios/' . $user->user ?>">
			@<?php echo $user->user ?>
		</a>
	</li>
</ul>
<?php
}
function load_more( $toLoad, $page, $extra = '' ) {
	$extra = !empty($extra) ? 'data-extra="'. $extra . '"' : '';
	echo '<div id="load_more"
	data-load="'.$toLoad.'"
	data-page="'.$page.'" '. $extra .'></div>';
}
function search($search, $sort, $type, $page = 1) {
	global $db;
	$search = trim($search, "\x20*\t\n\r\0\x0B");
	$escaped = '*' . $search . '*';
	if( 'a' == $type ):
		$query = 'SELECT * FROM audios
			WHERE reply_to = \'0\'
			AND status = \'1\'
			AND MATCH(`description`)
			AGAINST (? IN BOOLEAN MODE)';
		$count = $db->query(
			'SELECT COUNT(*) AS size FROM audios
			WHERE reply_to = \'0\'
			AND status =  \'1\'
			AND MATCH(`description`)
			AGAINST (? IN BOOLEAN MODE)',
			$escaped
		);
	else: // if the type is user
		$query = 'SELECT * FROM users
			WHERE MATCH(`user`, `name`, `bio`)
			AGAINST (? IN BOOLEAN MODE)';
		$count = $db->query(
			'SELECT COUNT(*) AS size FROM users
			WHERE MATCH(`user`, `name`, `bio`)
			AGAINST (? IN BOOLEAN MODE)',
			$escaped
		);
	endif;
	$count = (int) $count->size;
	# min 3
	if( mb_strlen( $search, 'utf-8') < 3 ||  0 == $count )
		return alert_error( __("No results were found... Maybe if my mom comes she may find something.") );
	$total_pages = ceil( $count / 10 );
	if( $page > $total_pages )
		return;
	#--
	if( 'a' == $type ):
		if( 'd' == $sort )
			$query .= ' ORDER BY time DESC';
		else
			$query .= ' ORDER BY plays DESC';
	endif;
	#--
	$query .= ' LIMIT '. ($page-1) * 10 . ',10';
	$result = $db->query($query, $escaped );
	while($result = $result->r->fetch_object() ) {
		if( 'a' === $type ): // if looking for audios
			if( can_listen($result->user) ):
				display_audio($result);
			endif;
		else: // if looking for users
			display_user($result);
		endif;
	}
	if( $page < $total_pages )
		load_more('search', $page+1, $sort);
}
function load_audios( $id, $page = 1 ) {
	global $db;
	$query = "SELECT * FROM audios
	WHERE user = ?
	AND reply_to = '0'
	AND status = '1'
	ORDER BY time DESC";
	$count = $db->query(
		"SELECT COUNT(*) AS size FROM audios
	WHERE user = ? AND reply_to = '0'", $id);
	$count = (int) $count->size;
	if( 0 == $count )
		return alert_error( __("This user has not uploaded audios... yet."), true);
	$total_pages = ceil( $count / 10 );
	if( $page > $total_pages )
		return;
	$query .= ' LIMIT '. ($page-1) * 10 . ',10';
	$audios = $db->query($query, $id);
	while( $audio = $audios->r->fetch_object() )
		display_audio($audio);
	if( $page < $total_pages )
		load_more('audios', $page+1);
}
function load_favs( $id, $page = 1 ) {
	global $db;
	$query = "SELECT DISTINCT A.* FROM audios
		AS A INNER JOIN favorites AS F ON A.id = F.audio_id
		AND F.user_id = ? AND A.status = '1'
		ORDER BY F.time DESC";
	$count = $db->query(
		"SELECT COUNT(*) AS size FROM audios
		WHERE id IN (
			SELECT audio_id FROM favorites
			WHERE user_id = ?
			)
		AND status = '1'",
		$id
	);
	$count = (int) $count->size;
	if( 0 == $count )
		return alert_error(
			__("This user has not favorited audios/replies... yet."),
			true
		);
	$total_pages = ceil( $count / 10 );
	if( $page > $total_pages )
		return;
	$query .= ' LIMIT '. ($page-1) * 10 . ',10';
	$audios = $db->query($query, $id);
	while( $audio = $audios->r->fetch_object() )
		display_audio($audio);
	if( $page < $total_pages )
		load_more('favorites', $page+1);
}
function load_replies( $id, $page = 1 ) {
	global $db;
	$query = "SELECT * FROM audios
		WHERE reply_to = ?
		ORDER BY `time` DESC";
	$count = $db->query(
		"SELECT COUNT(*) AS size FROM audios
		WHERE reply_to = ? AND status = '1'",
		$id
	);
	$count = (int) $count->size;
	if( 0 == $count ){
		echo '<div class="alert info" id="noreplies">' .
			__("There are not replies yet. Be the first!") .
		'</div>';
		return;
	}
	$total_pages = ceil( $count / 10 );
	if( $page > $total_pages )
		return;
	$query .= ' LIMIT '. ($page-1) * 10 . ',10';
	$audios = $db->query($query, $id);
	# linked replies:
	$is_reply = isset($_REQUEST['reply_id']) &&
	is_audio_id_valid($_REQUEST['reply_id']);
	if( is('audio') && $is_reply ) {
		$result = $db->query(
			'SELECT * FROM audios
			WHERE id = ?
			AND reply_to = ?
			AND status = \'1\'',
			$_REQUEST['reply_id'],
			$id
		);
		if( $result->nums > 0 ) {
			define('LINKED', $_REQUEST['reply_id']);
			display_audio($result);
		}
	}
	while( $audio = $audios->r->fetch_object() ):
		if( $is_reply && $_REQUEST['reply_id'] == $audio->id )
			continue;
		display_audio($audio);
	endwhile;
	if( $page < $total_pages )
		load_more('replies', $page+1);
}
/**
* @deprecated until the future ... :o
**/
function load_trendings() {
	global $db;
	$time1 = time() - 432000;
	$time2 = time();
	$trends = $db->query(
		"SELECT COUNT( DISTINCT user ) total, trend
		FROM trends
		WHERE 
			time BETWEEN ? AND ?
       		&& trend != ''
		GROUP BY trend
		ORDER BY total DESC 
		LIMIT 10",
		$time1,
		$time2
	);
	if( $trends->nums === 0 ){
		return alert_error(
			__("There aren't trendings right now."),
			true
		);
	}
	while($t = $trends->r->fetch_array() ):
	?>
	<a href="<?php echo url() . 'search/?q=%23' . $t['trend'] ?>">#<?php echo $t['trend'] ?></a><br>
	<?php
	endwhile;
}