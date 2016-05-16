<?php
/**
* View
* Loads everything related to front-end
*
**/
namespace application;
use \application\HTTP;

class View {
	/**
	* Check if we are in a certain template
	* to do something.
	* Ex: if we are in a 404 page, don't show something
	* You can pass it multiple params
	* to check for multiple pages.
	* @return bool
	**/
	public static function is() {
		global $_PAGE;
		if( isset($_PAGE) ) {
			return in_array( $_PAGE, func_get_args() );
		}
		return false;
	}
	/**
	* Set the current page
	* @param $page string
	* @return void
	**/
	public static function set_page( $page ) {
		global $_PAGE;
		$_PAGE = $page;
	}
	/**
	* Set the title for the browser window
	* @param $title str
	* @return void
	**/
	public static function set_title( $title ) {
		global $_TITLE;
		$_TITLE = $title;
	}
	/**
	* Tell the search engines
	* if they should index the page
	* @param $robots bool
	* @return void
	**/
	public static function allow_robots( $robots ) {
		$_ROBOTS = $robots;
	}
	/**
	* Loads a template
	* from a group.
	* The param $template must be a template with its group.
	* Something like "main/audio" or "main/profile"
	* The function will return nothing if it doesn't.
	* @param $template string
	* @param $strings  array
	**/
	public static function load_template( $template, $strings = array() ) {
		$group_template = explode('/', $template);
		if( 2 !== $group_template ) {
			return;
		}
		$group         = $group_template[0];
		$template      = $group_template[1];
		$templates_dir = $_SERVER['DOCUMENT_ROOT'] . 'views/';
		/**
		* $templates_dir is the template for
		* all the dirs for templates
		* while $template_dir is only
		* for this group.
		**/
		$template_dir  = $templates_dir . $group . '/';
		if( ! is_dir($template_dir) ) {
			/**
			* The templates are separated in groups
			* The templates for the main site are in the dir
			* views/main
			* future templates will be in different dirs
			* like admin/main, support/main
			* etc.
			* If there's no dir, then we have nothing to look at there.
			**/
			trigger_error('Could not load templates dir ' . $templates_dir);
			return;
		}
		$template_file = $template_dir . $template . '.hbs';
		if( ! file_exists($template_file) ) {
			/**
			* if the template does not exist...
			**/
			trigger_error('Template does not exist: ' . $template);
			return;
		}
		/**
		* Let's load our template
		**/
		$template_php = LightnCandy::compile(
				$templates_dir . $template . 'hbs'
			);
		/**
		* Now, in the views dir we have the main template
		* which loads the header and the footer
		* and loads other partials.
		* First we compile the default template for the group.
		**/
		$group_php     = LightnCandy::compile($template_dir . $group . '.hbs');
		$group_strings = self::get_default_strings_for($group);
		// now we have everything, print it
		$renderer = LightnCandy::prepare($group_php, array(
				''
			)
		);
	}

	/**
	* Loads assets
	**/

	public static function load_script( $script_name ) {
		return url('assets/js/'  . $script_name);
	}

	public static function load_style( $style_name ) {
		return url('assets/css/' . $style_name);
	}

	public static function load_img( $img_name ) {
		return url('assets/img/' . $img_name);
	}
	/**
	* alias
	* @see load_img
	**/
	public static function load_image( $image_name ) {
		return self::load_img($image_name);
	}
	/**
	* Returns the twitter image
	* with different sizes
	* $link must be the common twitter url
	* @return string
	**/
	public static function get_twitter_image( $link, array $options ) {
		$hola   = explode(".", $link);
		$format = end($hola);
		$hola   = explode("_", $link);
		array_pop($hola);
		$link   = implode("_", $hola);
		$size   = isset($options['size']) ? $options['size'] : '';
		if( 'bigger' == $size ) {
			return $link . '_bigger.'. $format;
		} elseif( '' === $size ) {
			return $link . '.' . $format;
		} else {
			return $link . '_normal.' . $format;
		}
	}
	/**
	* Shows the verified badge
	* In case the user is verified
	* @param $numb
	**/
	public static function show_verified_badge( $numb ) {
		if( 0 == $numb ) {
			return;
		}
		echo '<i class="fa fa-check verified" title="Verified account"></i>';
	}

	public static function display_audio( array $audio,
										  array $options = array() ) {
		/** THERE ARE DRAGONS HERE **/
		//big is only for audio pages
		$big = @$options['size'] == 'big';
		$user = $audio['user'];
		$profile_url = url('audios/' . $user['user']);
		$get_avatar = $big ? $user['avatar_bigger'] : $user['avatar'];
	?>
	<div class="audio <?php
	if($big) echo 'audio-big';
	echo ' audio_' . $audio['id'];
	?>"
	id="<?php echo $audio['id'] ?>">
	<div class="audio-header">
		<a href="<?php echo url('audios/'. $user['user']) ?>">
			<img class="circle"
			src="<?php echo $get_avatar ?>"
			onerror="this.src='<?php echo url('assets/img/unknown.png') ?>'"
		<?php if($big): ?>
			height="73"
			width="73"
		<?php else: ?>
			height="48"
			width="48"
		<?php endif ?>
			>
		</a>
		<span class="audio-user-name">
			<a
			class="no-deco"
			href="<?php echo $profile_url ?>"
			>
				<?php echo HTTP::xss_protect( $user['name'] ) ?>
			</a>
		</span>
		<span class="audio-user-user">
			<a
			class="no-deco"
			href="<?php echo $profile_url ?>"
			>
				@<?php echo $user['user'] ?>
			</a>
		</span>
		<span class="audio-date">
			<i class="fa fa-clock-o grey-text lighten-1-text"></i>&nbsp;
			<a href="<?php
			echo \url();
			if( $audio['reply_to'] != '0' )
				echo $audio['reply_to'] .
				'?reply_id=' . $audio['id'];
			else
				echo $audio['id']
			?>">
				<?php echo get_date_differences( $audio['time'] ) ?>
			</a>
		</span>
		<?php
		if( array_key_exists('is_linked', $audio) ):
		?>
		<div class="chip linked_reply">
			<i class="fa fa-link"></i>&nbsp;
			Linked reply
		</div>
		<?php endif ?>
	</div>
	<?php if( ! empty($audio['description']) ): ?>
		<div class="audio-description">
			<?php echo HTTP::sanitize( $audio['description'] ) ?>
		</div>
	<?php endif; if( ! empty( $audio['audio'] ) ):  // if its not a reply ?>
	<div class="audio-play">
	<script>
	window.onLoadFunctions.push( function() {
		$("#player_<?php echo $audio['id'] ?>").jPlayer({
			ready: function(event) {
				$(this).jPlayer("setMedia", {
					mp3: "<?php echo $audio['audio'] ?>",
				});
			},
			play: function() {
				$(".jp-jplayer").not(this).jPlayer("pause");
			},
			cssSelectorAncestor : '#container_<?php echo $audio['id'] ?>',
			swfPath: swfPath,
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
	<div id="player_<?php echo $audio['id'] ?>" class="jp-jplayer"></div>
    <div id="container_<?php echo $audio['id'] ?>" class="jp-audio sm">
        <div class="jp-type-single">
            <div class="jp-gui jp-interface">
                <ul class="jp-controls">
                    <li><a href="javascript:;" class="jp-play plei" data-id="<?php echo $audio['id'] ?>" tabindex="1"><i class="fa fa-play control"></i></a></li>
                    <li><a href="javascript:;" class="jp-pause" tabindex="1"><i class="fa fa-pause control"></i></a></li>
                    <li><a href="javascript:;" class="jp-stop" tabindex="1"></a></li>
                    <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute"><i class="fa fa-volume-up vcontrol"></i></a></li>
                    <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute"><i class="fa fa-volume-down vcontrol"></i></a></li>
                    <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume"></a></li>
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
                <span>Update Required</span>
                To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
            </div>
        </div>
    </div>
	</div>
	<?php endif # / if its not a reply ?>
	<div class="audio-footer">
	<?php if( ! empty($audio['audio']) ): # if not a reply ?>
		<a
		class="audio-btn"
		id="plays_<?php echo $audio['id'] ?>"
		title="<?php
		$singular = '%d person played this';
		$plurar   = '%d people played this';
		echo sprintf(
				!! $audio['plays'] ? $singular : $plural,
				$audio['plays']
			);
		?>"
		>
			<i class="fa fa-headphones"></i>&nbsp;
			<span>
				<?php echo format_number($audio['plays']) ?>
			</span>
		</a>
	<?php endif ?>
		<a class="audio-btn
		<?php if( is_logged() ):
			echo 'laic';
			if($audio['favorited'])
				echo ' favorited';
			endif ?>"
		data-id="<?php echo $audio['id'] ?>"
		title="Mark as favorite">
			<i class="fa fa-star"></i>&nbsp;
			<span>
				<?php echo format_number($audio['favorites']) ?>
			</span>
		</a>
	<?php if( ! empty($audio['audio']) ): # if not a reply ?>
		<a class="audio-btn"
		      href="<?php echo url() . $audio['id'] ?>#replies"
		      title="Leave a reply"
		      >
			<i class="fa fa-reply"></i>&nbsp;
			<span>
				<?php echo format_number($audio['replies_count']) ?>
			</span>
		</a>
	<?php endif;
		// no, this is not dirty.
		// the 'is_logged' function
		// returns an integer with
		// the user ID
		if( is_logged() == $user['id'] ): ?>
		<a
		href="javascript:void(0);"
		class="audio-btn delit"
		data-id="<?php echo $audio['id'] ?>"
		title="'Delete this audio'"
		>
			<i class="fa fa-times"></i> Delete
		</a>
		<?php endif ?>
	</div>
	<div class="divider"></div>
	</div><!--/ .audio -->
	<?php
	}
	public static function display_user( array $user ) {
	?>
		<ul class="user">
			<li>
				<a href="<?php echo url() . 'audios/' . $user['user'] ?>">
					<img class="circle"
						 src="<?php echo $user['avatar'] ?>"
						 onerror="this.src='<?php
						 self::load_img('unknown.png')
						 ?>'"
						 height="48"
						 width="48"
					>
				</a>
			</li>
		<li class="audio-user-name">
			<a href="<?php echo url() . 'audios/' . $user['user'] ?>">
			<?php
			echo \application\HTTP::xss_protect( $user['name'] );
			self::show_verified_badge($user['verified']);
			?>
			</a>
		</li>
		<li class="audio-user-user">
			<a href="<?php echo url() . 'audios/' . $user['user'] ?>">
				@<?php echo $user['user'] ?>
			</a>
		</li>
	</ul>
	<?php
	}
	public static function load_more( $toLoad, $page, $extra = '' ) {
		$extra = !empty($extra) ? 'data-extra="'. $extra . '"' : '';
		echo '<div id="load_more"
		data-load="'.$toLoad.'"
		data-page="'.$page.'" '. $extra .'></div>';
	}

	public static function alert_error($error) {
		echo '<div class="alert error">'. $error . '</div>';
	}
	public static function alert_info($info) {
		echo '<div class="alert info">'. $info . '</div>';
	}

	public static function exit_404() {
		self::load_full_template('404');
		exit;
	}
}