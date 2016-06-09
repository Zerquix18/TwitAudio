<?php
/**
 * View.php
 * Loads all the templates and their stuff
 *
 * @author Zerquix18 <zerquix18@outlook.com>
 * @copyright 2016 Luis A. Martínez
**/
namespace application;
use \application\HTTP,
	\LightnCandy\LightnCandy;

class View {
	/**
	 * Stores the current page. The page will be
	 * 'home-logged'. With that we can detect what page are we in
	 * so we can show an specific content.
	 * @var string
	 */
	private static $page;
	/**
	 * Stores the title of the current window.
	 * @var string
	 */
	private static $title;
	/**
	 * Stores whether we should allow robots (.eg meta name="robots")
	 * @var boolean
	 */
	private static $robots;
	/**
	 * Saves the options.
	 * @see self::get_template_options
	 * @var array
	 */
	private static $options = array();
	/**
	 * This function returns if we are in one page specified by the params
	 * The page will be set with the load_template function
	 * Ex: is('404', 'text', 'home-logged')
	 * 
	 * @return boolean
	 */
	public static function is() {
		$args = func_get_args();
		return in_array(self::$page, $args);
	}
	/**
	 * Sets the current page
	 * @see self::$page
	 * @param string $page_name The current page name
	 * 
	 */
	public static function set_page( $page ) {
		self::$page = $page;
		// yup. it works.
	}
	/**
	 * Sets the title of the current window.
	 * @param string $title
	 */
	public static function set_title( $title ) {
		self::$title = $title;
	}
	/**
	 * Should we allow robots? (e.g meta name="robots")
	 * @param boolean $robots
	 */
	public static function set_robots( $robots ) {
		self::$robots = $robots;
	}
	/**
	 * Returns the full URL to a script
	 * @param  string $script The script to load (must contain .js)
	 * @return string
	 */
	public static function get_script( $script ) {
		return url('assets/js/' . $script);
	}
	/**
	 * Returns the full URL to a style
	 * @param  string $style The style to load (must contain .css)
	 * @return string
	 */
	public static function get_style( $style ) {
		if( ! \Config::get('is_production') ) {
			/*
				If we're not in production, then the CSS will be located in
				css/app/css.css
				Inside that CSS, if we call to ../img/
				it will point to css/img/
				So I made this file to deal with it.
				:)
			 */
			return url('assets/css/load_style.php?style=' . $style);
		}
		return url('assets/css/' . $style);
	}
	/**
	 * Returns the full URL to an image
	 * @param  string $image The image to load (must contain its format)
	 * @return string
	 */
	public static function get_image( $image ) {
		return url('assets/img/' . $image);
	}
	/** 
	 * Returns the list of options. They can be set in first place
	 * using self::$template_options = array(...)
	 * Here then it will add the ones that are missing.
	 * 
	 * @return array
	 */
	public static function get_template_options() {
		$is_production = \Config::get('is_production');
		if( $is_production ) {
			$templates_dir    = DOCUMENT_ROOT . '/views_compiled/';
			$templates_format = 'php';
		} else {
			$templates_dir    = DOCUMENT_ROOT . '/views/';
			$templates_format = 'hbs';
		}
		$global_helpers = array(
				'get_image'   => '\application\View::get_image',
				'get_style'   => '\application\View::get_style',
				'get_script'  => '\application\View::get_script',
				'alert_error' => '\application\View::alert_error',
				'alert_info'  => '\application\View::alert_info',
				'show_verified_badge' => // ↓
				'\application\View::show_verified_badge'
			);
		$default_options = array(
				/*
				 * the current group we are working with. It is set
				 * after a call to get_template.
				 */
				'current_group' => 'main',
				/*
				 * The dir to load the templates
				 * In production they'll be compiled
				 */
				'templates_dir' => $templates_dir,
				/**
				 * The format of the templates
				 * In production they'll be compiled (php)
				 * but in testing they'll be .hbs
				 */
				'templates_format' => $templates_format,
				/*
				 * LightnCandy flags for groups.
				 */
				'group_options' => array(
					/** helpers **/
					'helpers' => array(
						/**
						* Allow to load subtemplates
						**/
						'get_template' => '\application\View::get_template',
						'get_partial'  => '\application\View::get_partial',
					) + $global_helpers, // .helpers

					'flags' =>
					LightnCandy::FLAG_ERROR_LOG |
					LightnCandy::FLAG_ERROR_EXCEPTION |
					LightnCandy::FLAG_HANDLEBARS
				), //.group_options
				/*
				 * LightnCandy flags for templates
				 */
				'template_options' => array(
					'helpers' => array(
							// don't allow a subtemplate to call another
							// subtemplate.
							'get_partial' => '\application\View::get_partial',
						) + $global_helpers,
					'flags'  =>
					LightnCandy::FLAG_ERROR_LOG |
					LightnCandy::FLAG_ERROR_EXCEPTION |
					LightnCandy::FLAG_HANDLEBARS
				),
				/*
				 * LightnCandy flags for partials
				 */
				'partial_options' => array(
					'helpers' => array(
							// allow calling partials from partials
							'get_partial' => '\application\View::get_partial',
						) + $global_helpers,
					'flags'  =>
					LightnCandy::FLAG_ERROR_LOG |
					LightnCandy::FLAG_ERROR_EXCEPTION |
					LightnCandy::FLAG_HANDLEBARS
				),
				'renderer_options' => array(
						'debug' => \LightnCandy\Runtime::DEBUG_TAGS
					)
			); // .default_options
		return array_merge($default_options, self::$options);
	}
	/**
	 * Loads a group and a template.
	 * The bars for the template group must be in a file
	 * inside templates dir.
	 * Ex: main/main.php
	 * The main template group must be there too.
	 * Ex: main/main.hbs
	 * 
	 * @param  string $group   It must be the group and its template.
	 *                         With the format group/template.
	 *                         Ex: main/404, main/home, main/profile
	 * @param  array  $bars    The list of bars that the template
	 *                         will parse.
	 * @return string          Everything ready to be print.
	 * @throws ProgrammerException
	 */
	public static function get_group_template( $group, $bars = array() ) {
		//group/template
		$group_template = explode('/', $group);
		if( 2 !== count($group_template) ) {
			throw new \ProgrammerException(
					'Load template sintax must be "group/template"'
				);
		}
		$group         = $group_template[0];
		$template      = $group_template[1];
		$options       = self::get_template_options();
		$templates_dir = $options['templates_dir'];
		self::$options['current_group'] = $group;
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
			throw new \ProgrammerException(
						'Could not load templates dir ' . $templates_dir
					);
		}
		$template_file = // ↓
		$template_dir . $group . '.' . $options['templates_format'];
		if( ! file_exists($template_file) ) {
			/**
			* if the template does not exist...
			**/
			throw new \ProgrammerException(
						'Template does not exist: ' . $template_file
					);
			return;
		}
		/**
		* Get the default bars for the group
		* which are in a file. It contains
		* the header/footer bars
		* and other stuff.
		**/
		$default_bars  = self::get_default_bars($group);
		$bars          = array_merge_recursive($default_bars, $bars);
		/**
		* This is global info for the templates
		**/
		/**
		* Let's load our group template
		**/
		if( \Config::get('is_production') ) {
			// if it's in production is already compiled
			$renderer = include $template_file;
		} else {
			try {
				$template_php = LightnCandy::compile(
					file_get_contents($template_file),
					$options['group_options']
				);
			} catch ( \Exception $e ) {
				throw new \VendorException('LightnCandy', $e->getMessage());
			}
			$renderer = LightnCandy::prepare($template_php);
		}
		return $renderer(
			array(
				'body' => $template
			) + $bars,
			$options['renderer_options']
		);
	}
	/**
	 * This function instead, loads a subtemplate
	 * which is for an specific page.
	 * It can NOT load another subtemplates
	 * but it can load partials.
	 * @param string $subtemplate_name The name of the sub template.
	 * @param array  $args             Context passed from the template or if you're
	 *                                 calling it directly.
	 *                                 Ex:   {{load_subtemplate 'test' this}}
	 *                                 Ex 2: load_subtemplate('test', array())
	 * @return string
	**/
	public static function get_template( $template, $bars = array() ) {
		// all the methods here have to have its namespace
		$options       = \application\View::get_template_options();
		$template_file = sprintf(
					//ex: {...}views/main/templates/home.hbs
					'%s/%s/templates/%s.%s',
					$options['templates_dir'],
					$options['current_group'],
					$template,
					$options['templates_format']
				);
		if( ! file_exists($template_file) ) {
			throw new \ProgrammerException(
					'Template does not exist: ' . $template_file
				);
		}
		if( \Config::get('is_production') ) {
			// if its in production it's already compiled
			$renderer = include($template_file);
		}else {
			try {
				$template_php  = \LightnCandy\LightnCandy::compile(
					file_get_contents($template_file),
					$options['template_options']
				);
				$renderer = \LightnCandy\LightnCandy::prepare($template_php);
			} catch( \Exception $e ) {
				throw new \VendorException('LightnCandy', $e->getMessage());
			}
		}
		return $renderer($bars, $options['renderer_options']);
	}
	/**
	 * This is a function for loading partials inside the subtemplates
	 * and the group templates. With this we can call templates
	 * inside the group/partials dir and pass arguments.
	 * That may be used to loop posts, users, audios, etc.
	 * This function is not called from PHP. It is called
	 * from a template. Like this:
	 * {{get_partial 'posts' post}}
	 * {{get_partial 'sidebar'}}
	 *
	 * @param string $partial The name of the file without extension
	 * @param array  $args    An array with bars that
	 *                        the template needs.
	 *                        If you want to send the current context
	 *                        just use this. Ex: {{get_partial "lol" this}}
	 * @return string
	**/
	public static function get_partial( $partial, $bars = array() ) {
		/**
		* If the partial was called INSIDE a loop
		* then take the pair key=>value
		**/
		if( ! empty($bars['_this']) ) {
			$bars = $bars['_this'];
		}
		$options = \application\View::get_template_options();

		$template_file = sprintf(
						'%s/%s/partials/%s.%s',
						$options['templates_dir'],
						$options['current_group'],
						$partial,
						$options['templates_format']
					);
		if( ! file_exists($template_file) ) {
			throw new \ProgrammerException(
						'Partial does not exist: ' . $template_file
					);
		}
		if( \Config::get('is_production') ) {
			$renderer = include($template_file);
		} else {
			try {
				// namespace is obligatory
				$template_php = \LightnCandy\LightnCandy::compile(
					file_get_contents($template_file),
					$options['partial_options']
				);
				$renderer = \LightnCandy\LightnCandy::prepare($template_php);
			} catch ( \Exception $e ) {
				throw new \VendorException('LightnCandy', $e->getMessage());
			}
		}
		// go ahead!
		return $renderer($bars, $options['renderer_options']);
	}
	/**
	 * This function returns an array
	 * with the default bars
	 * for the group. Those bars are
	 * the header, footer and globals.
	 * It should be a PHP file with an array
	 * called $bars
	 * @param  string $group
	 * @return array
	**/
	public static function get_default_bars( $group ) {
		$options = self::get_template_options();
		$result  = array();
		$result['header'] = array();
		if( self::$title ) {
			$result['header']['title']  = self::$title;
		}
		if( self::$robots ) {
			$result['header']['robots'] = self::$robots;
		}
		$file    = sprintf(
					'%s/%s/%s-bars.php',
					$options['templates_dir'],
					$group,
					$group
				);
		if( ! file_exists($file) ) {
			return array();
		}
		require $file;
		return array_merge_recursive($result, $bars);
	}
	/**
	 * Exits the 404 page. It may be called from anywhere.
	 * @param string $group The group
	 */
	public static function exit_404( $group = 'main' ) {
		ob_end_clean();
		self::set_title('Error 404');
		self::set_page('404');
		self::set_robots(false);
		echo self::get_group_template("{$group}/404");
		exit;
	}
	public static function exit_500() {
		ob_end_clean();
		$result = file_get_contents(
			$_SERVER['DOCUMENT_ROOT'] . '/application/html/error-500.html'
		);
		echo $result;
		exit;
	}

	/* functions with HTML */

	/**
	 * Returs an error message
	 * @param  string $error The error message to be show
	 * @return string        The string with the HTML and the error
	 */
	public static function alert_error( $error ) {
		$error = sprintf('<div class="alert error">%s</div>', $error);
		return $error;
	}
	/**
	 * Returns an info message
	 * @param  string $info The error message to be show
	 * @return string        The string with the HTML and the info
	 */
	public static function alert_info( $info ) {
		$info = sprintf('<div class="alert info">%s</div>', $info);
		return $info;
	}
	/**
	 * Detects if the user is verified and returns the verified badge
	 * @param  string $numb The string that comes from the database.
	 *                      Must be 1 or 0
	 * @return string
	 */
	public static function show_verified_badge( $numb ) {
		if( '1' !== $numb ) {
			return '';
		}
		return '<i class="fa fa-check verified" title="Verified account"></i>';
	}
}