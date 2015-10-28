<?php
define("TEMP", "templates/");
define("FULL_TEMP", "templates/full/");
$_USER = ( $id = is_logged() ) ?
	$db->query("SELECT * FROM users WHERE id = ?", $id )
:
	NULL;
$_BODY = array();
//
$_BODY['css_url'] = url() . INC . CSS;
$_BODY['js_url'] = url() . INC . JS;
$_BODY['img_url'] = url() . INC . IMG;
$_BODY['meta'] = array(
		'title' => __('TwitAudio - Share voice notes and audio files using Twitter'),
		'robots' => true
	);
$_BODY['post_box'] = false;
function load_template( $name ) {
	global $db, $_BODY, $_USER, $lenguajeso, $lenguajest;
	$f = PATH . INC . TEMP . $name . '.phtml';
	if( ! file_exists($f) )
		return false;
	require_once($f);
}
function load_full_template( $name ) {
	global $db, $_BODY, $_USER, $lenguajeso, $lenguajest;
	$f = PATH . INC . FULL_TEMP . $name . '.phtml';
	if( ! file_exists($f) )
		return false;
	require_once($f);
}
function get_image( $link, $size = '' ){
	$format = end($hola = explode(".", $link));
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
function d_diff( $time ) {
	$n = new Datetime('@'.$time);
	$f = new DateTime();
	$diff = $f->diff($n);
	$diff->w = round( $diff->days / 7 );
	if( $diff->w >= 1)
		return sprintf( $diff->w == 1 ?
			__('%d week')
		:
			__('%d weeks')
		, $diff->w);
	if( $diff->d >= 1 )
		return sprintf( $diff->d == 1 ?
				__('%d day')
			:
				__('%d days')
			, $diff->d);
	if( $diff->h >= 1 )
		return sprintf( $diff->h == 1 ?
				__('%d hour')
			:
				__('%d hours')
			, $diff->h);
	if( $diff->i >= 1 )
		return sprintf( $diff->i == 1 ?
				__('%d min')
			:
				__('%d mins')
			, $diff->i);
	if( $diff->s >= 1 )
		return sprintf( $diff->s == 1 ?
				__('%d second')
			:
				__('%d seconds')
			, $diff->s);
	return __('now');
}
function display_audio( $a, $big = false ) {
	global $db, $_USER;
	$u = (is_logged() && $a->user == $_USER->id) ? $_USER : $db->query("SELECT * FROM users WHERE id = ?", $a->user);
	$was_played = $db->query("SELECT COUNT(*) AS size FROM plays WHERE audio_id = ? AND user_ip = ?", $a->id, getip() );
	$was_played = (int) $was_played->size;
	if( is_logged() ):
	$is_liked = $db->query("SELECT COUNT(*) AS size FROM likes WHERE audio_id = ? AND user_id = ?", $a->id, $_USER->id);
	$is_liked = (int) $is_liked->size;
	endif;
	$size = $big ? 'bigger' : 'normal'
?>
<div class="audio <?php if($big) echo 'big' ?>" id="<?php echo $a->id ?>">
	<div class="audio_header">
		<a href="<?php echo url() . 'audios/'. $u->user ?>">
			<img src="<?php echo get_image($u->avatar, $size) ?>">
		</a>
		<span class="name"><?php echo htmlentities($u->name) ?></span>
		<span class="uname">@<?php echo $u->user ?></span>
		<span class="adate">
			<a href="<?php echo url() . $a->id ?>" class="nodeco">
				<?php echo d_diff( $a->time ) ?>
			</a>
		</span>
	</div>
	<?php if( ! empty($a->description) ): ?>
		<div class="audio_desc">
			<?php echo sanitize( $a->description ) ?>
		</div>
	<?php endif; ?>
	<div class="audio_play">
	<script>
//<![CDATA[
$(document).ready( function() {

    $("#player_<?php echo $a->id ?>").jPlayer({
        ready: function(event) {
            $(this).jPlayer("setMedia", {
				mp3: "<?php echo url() . INC . 'audios/' . $a->audio ?>",
            });
        },
        cssSelectorAncestor : '#container_<?php echo $a->id ?>',
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
//]]>
</script>
<div id="player_<?php echo $a->id ?>" class="jp-jplayer"></div>
    <div id="container_<?php echo $a->id ?>" class="jp-audio sm">
        <div class="jp-type-single">
            <div class="jp-gui jp-interface">
                <ul class="jp-controls">
                    <li><a href="javascript:;" class="jp-play plei" data-id="<?php echo $a->id ?>" tabindex="1"><i class="fa fa-play control"></i></a></li>
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
	<div class="audio_footer">
		<a class="audiobtn" id="plays_<?php echo $a->id ?>">
			<i class="fa fa-headphones"></i> <span><?php echo $a->plays ?></span>
		</a>
		<a class="audiobtn <?php if( is_logged() ): echo 'laic'; if($is_liked) echo 'liked'; endif ?>" data-id="<?php echo $a->id ?>">
			<i class="fa fa-heart like"></i> <span><?php echo $a->likes ?></span>
		</a>
		<?php  if( is_logged() && $a->user == $_USER->id ): ?>
		<a class="audiobtn delit" data-id="<?php echo $a->id ?>">
			<i class="fa fa-times"></i> <?php _e('Delete') ?>
		</a>
		<?php endif ?>
	</div>
	<hr>
</div>
<?php
}
function can_listen( $id2 ) {
	global $db, $twitter, $_USER;
	$l = is_logged();
	if( $l && $_USER->id == $id2 ) // same user
		return true;
	// check if audios of user2 are private.
	$c = $db->query("SELECT audios_public FROM users WHERE id = ?", $id2);
	if( $c->nums > 0 && $c->audios_public == '1' )
		return true; // they're public.
	if( ! $l )
		return false; // not logged and audios aren't public.
	// not public. check if cached ...
	$db->query("DELETE FROM following_cache WHERE time < " . time() - 10800 );
	$x = $db->query("SELECT result FROM following_cache WHERE user_id = ? AND following = ?", $_USER->id, $id2 );
	if( $x->nums > 0 )
		return (int) $x->result;
	// not cached, make twitter requests
	$g = $twitter->tw->get('friendships/lookup', array('user_id' => $id2) );
	if( array_key_exists('errors', $g ) ) {
		// API rate limit reached :( try another
		$t = $twitter->tw->get('users/lookup', array('user_id' => $id2) );
		if( array_key_exists('errors', $t ) || array_key_exists('error', $t) ) // both limits reached... ):
			return false;
		$check = array_key_exists('following', $t[0]) && $t[0]->following;
	}else
		$check = in_array('following', $g[0]->connections);
	$db->insert("following_cache", array(
			$_USER->id,
			$id2,
			time(),
			(string) (int) $check
		)
	);
	return $check;
}
function sanitize( $str ) {
	if( mb_strlen( $str, 'utf8' ) < 1 )
		return '';
	$str = htmlspecialchars( $str );
	$str = str_replace( array( chr( 10 ), chr( 13 ) ), '' , $str );
	$str = preg_replace('/https?:\/\/[\w\-\.!~#?&=+%;:\*\'"(),\/]+/u','<a href="$0" target="_blank" rel="nofollow">$0</a>', $str);
    	$str = preg_replace_callback('~([#@])([^\s#@!\"\$\%&\'\(\)\*\+\,\-./\:\;\<\=\>?\[/\/\/\\]\^\`\{\|\}\~]+)~',
    		function($m) {
    			$dir = $m[1] == "#" ? "search/?q=%23" : "";
    			return '<a href="' . url() . $dir . $m[2] . '">' . $m[0] . '</a>';
    		},
       	$str );
	return $str;
}
function load_more( $what, $p, $extra = '' ) {
	$extra = !empty($extra) ? 'data-extra="'. $extra . '"' : '';
	echo '<button title="'. __("There is more!") .'" type="button" id="load_more" data-load="'.$what.'" data-page="'.$p.'" '. $extra .'>' . __('Load more') . '</button>';
}
function search($search, $s, $p = 1) {
	global $db;
	$search = trim($search);
	if( empty($search) )
		return;
	$escaped = $db->real_escape($search);
	$query = "SELECT * FROM audios WHERE reply_to = '0' AND MATCH(`description`) AGAINST (? IN BOOLEAN MODE)";
	$count = $db->query("SELECT COUNT(*) AS size FROM audios WHERE reply_to = '0' AND MATCH(`description`) AGAINST (? IN BOOLEAN MODE)", $escaped);
	$count = (int) $count->size;
	if( !$count )
		alert_error( __("No results were found. :("), true );
	$total_pages = ceil( $count / 10 );
	if( $p > $total_pages )
		return;
	if( 'd' == $s )
		$query .= ' ORDER BY time DESC';
	elseif('l' == $s) 
		$query .= ' ORDER BY likes DESC';
	else
		$query .= ' ORDER BY plays DESC';
	$query .= ' LIMIT '. ($p-1) * 10 . ',10';
	$audios = $db->query($query, $escaped );
	while($a = $audios->r->fetch_object() ) {
		$u = $db->query("SELECT audios_public FROM users WHERE id = ?", $a->user);
		if( ! (int) $u->audios_public )
			continue;
		display_audio($a);
	}
	if( $p < $total_pages )
		load_more('search', $p+1, $s);
}
function load_audios( $id, $p = 1 ) {
	global $db;
	$q = "SELECT * FROM audios WHERE user = ? AND reply_to = '0' ORDER BY time DESC";
	$count = $db->query("SELECT COUNT(*) AS size FROM audios WHERE user = ? AND reply_to = '0'", $id);
	$count = (int) $count->size;
	if( 0 === $count )
		return alert_error( __("This user has not uploaded audios... yet."), true);
	$total_pages = ceil( $count / 10 );
	if( $p > $total_pages )
		return;
	$q .= ' LIMIT '. ($p-1) * 10 . ',10';
	$audios = $db->query($q, $id);
	while( $a = $audios->r->fetch_object() )
		display_audio($a);
	if( $p < $total_pages )
		load_more('audios', $p+1);
}
function load_likes( $id, $p = 1 ) {
	global $db;
	$q = "SELECT * FROM audios WHERE id IN (SELECT audio_id FROM likes WHERE user_id = ? ORDER BY time DESC) AND reply_to = '0'";
	$count = $db->query("SELECT COUNT(*) AS size FROM audios WHERE id IN ( SELECT audio_id FROM likes WHERE user_id = ? ) AND reply_to = '0'", $id);
	$count = (int) $count->size;
	if( 0 === $count )
		return alert_error( __("This user has not liked audios... yet."), true );
	$total_pages = ceil( $count / 10 );
	if( $p > $total_pages )
		return;
	$q .= ' LIMIT '. ($p-1) * 10 . ',10';
	$audios = $db->query($q, $id);
	while( $a = $audios->r->fetch_object() )
		display_audio($a);
	if( $p < $total_pages )
		load_more('likes', $p+1);
}
function display_comment( $a ) {
	global $db, $_USER;
	$u = (is_logged() && $a->user == $_USER->id) ? $_USER : $db->query("SELECT * FROM users WHERE id = ?", $a->user);
	if( is_logged() ):
	$is_liked = $db->query("SELECT COUNT(*) AS size FROM likes WHERE audio_id = ? AND user_id = ?", $a->id, $_USER->id);
	$is_liked = (int) $is_liked->size;
	endif;
	$size = $big ? 'bigger' : 'normal'
?>
<div class="audio <?php if($big) echo 'big' ?>" id="<?php echo $a->id ?>">
	<div class="audio_header">
		<a href="<?php echo url() . 'audios/'. $u->user ?>">
			<img src="<?php echo get_image($u->avatar, $size) ?>">
		</a>
		<span class="name"><?php echo htmlentities($u->name) ?></span>
		<span class="uname">@<?php echo $u->user ?></span>
		<span class="adate">
			<a href="<?php echo url() . $a->reply_to . '#' . $a->id ?>" class="nodeco">
				<?php echo d_diff( $a->time ) ?>
			</a>
		</span>
	</div>
	<div class="audio_desc">
		<?php echo sanitize( $a->description ) ?>
	</div>
	<div class="audio_footer">
		<a class="audiobtn <?php if( is_logged() ): echo 'laic'; if($is_liked) echo 'liked'; endif ?>" data-id="<?php echo $a->id ?>">
			<i class="fa fa-heart like"></i> <span><?php echo $a->likes ?></span>
		</a>
		<?php  if( is_logged() && $a->user == $_USER->id ): ?>
		<a class="audiobtn delit" data-id="<?php echo $a->id ?>">
			<i class="fa fa-times"></i> <?php _e('Delete') ?>
		</a>
		<?php endif ?>
	</div>
	<hr>
</div>
<?php
}
function load_comments( $id, $p = 1 ) {
	global $db;
	$q = "SELECT * FROM audios WHERE reply_to = ? ORDER BY likes DESC";
	$count = $db->query("SELECT COUNT(*) AS size FROM audios WHERE reply_to = ?", $id);
	$count = (int) $count->size;
	if( 0 === $count ){
		echo '<div class="alert info center" id="no_comments">' . __("There are not comments yet. Be the first!") . '</div>';
		return;
	}
	$total_pages = ceil( $count / 10 );
	if( $p > $total_pages )
		return;
	$q .= ' LIMIT '. ($p-1) * 10 . ',10';
	$audios = $db->query($q, $id);
	while( $a = $audios->r->fetch_object() )
		display_comment($a);
	if( $p < $total_pages )
		load_more('comments', $p+1);
}
function load_trendings() {
	global $db;
	$time1 = time() - 432000;
	$time2 = time();
	$trends = $db->query("SELECT COUNT( DISTINCT user ) total, 
		trend FROM trends
		WHERE 
			time BETWEEN ? AND ?
       		&& trend != ''
		GROUP BY trend
		ORDER BY total DESC 
		LIMIT 10", $time1, $time2);
	if( $trends->nums === 0 ){
		return alert_error( __("There aren't trendings right now."), true );
	}
	while($t = $trends->r->fetch_array() ):
	?>
	<a href="<?php echo url() . 'search/?q=%23' . $t['trend'] ?>">#<?php echo $t['trend'] ?></a><br>
	<?php
	endwhile;
}