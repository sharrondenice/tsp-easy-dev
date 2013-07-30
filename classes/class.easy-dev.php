<?php	
if ( !class_exists( 'TSP_Easy_Dev' ) )
{
	require_once( 'class.easy-dev-data.php' );
	require_once( 'class.easy-dev-options.php' );
	require_once( 'class.easy-dev-widget.php' );
	
	/**
	 * API implementations for TSP Easy Dev Pro, Use TSP Easy Dev package to easily create, manage and display wordpress plugins
	 * @package 	TSP_Easy_Dev
	 * @author 		sharrondenice, thesoftwarepeople
	 * @author 		Sharron Denice, The Software People
	 * @copyright 	2013 The Software People
	 * @license 	APACHE v2.0 (http://www.apache.org/licenses/LICENSE-2.0)
	 * @version 	1.0
	 */
	class TSP_Easy_Dev
	{
		/**
		 * An array of CSS URLs to include in the admin area
		 *
		 * @var array
		 */
		private $admin_css_files	= array();
		/**
		 * An array of JS URLs to include in the admin area
		 *
		 * @var array
		 */
		private $admin_js_files		= array();
		/**
		 * An array of CSS URLs to include in the user front-end
		 *
		 * @var array
		 */
		private $user_css_files		= array();
		/**
		 * An array of JS URLs to include in the user front-end
		 *
		 * @var array
		 */
		private $user_js_files		= array();
		/**
		 * An array of short codes that this plugin will process
		 *
		 * @var array
		 */
		private $shortcodes			= array();
		/**
		 * This plugin's icon URL
		 *
		 * @var string
		 */
		private $plugin_icon		= null;
		/**
		 * The version of WordPress that this plugin requires
         *
         * @api
		 *
		 * @var string
		 */
		public $required_wordpress_version;
		/**
		 * Does the plugin use shortcodes?
         *
         * @api
		 *
		 * @var boolean
		 */
		public $uses_shortcodes 	= false;
		/**
		 * A string that contains the base name (file) of the plugin
		 *
		 * @var string
		 */
		public $plugin_base_name 	= null;
		/**
		 * A string that contains the title of the plugin
		 *
		 * @var string
		 */
		public $plugin_title 		= null;
		/**
		 * A string that contains the name of the plugin
		 *
		 * @var string
		 */
		public $plugin_name 		= null;
		/**
		 * Does the plugin require Smarty?
         *
         * @api
		 *
		 * @var boolean
		 */
		public $uses_smarty 		= false;
		/**
		 * The extended TSP_Easy_Dev_Options class, must be instantiated (ie $my_plugin->set_options_handler ( new TSP_Easy_Dev_Options_MY_PLUGIN() ))
         * 
         * @api
		 *
		 * @var TSP_Easy_Dev_Options
		 */
		public $options;
		/**
		 * The name of the widget class created by the user, a placeholder because logic can not be handled  
		 * by this class, the widget class has to be static and and called statically by WordPress
         *
         * @api
		 *
		 * @var string
		 */
		public $widget; //TODO: There was no way to aggregate a class for widget it has to be handled by WordPress via a hook, look into this with newer versions of WordPress
				
		/**
		 * Constructor
		 *
		 * @since 1.0
		 *
		 * @param array $globals Required - Sets the global settings for the plugin
		 *
		 * @return none
		 */
		public function __construct( $plugin, $required_wordpress_version ) 
		{
			$this->required_wordpress_version = $required_wordpress_version;
			
			// register install/uninstall hooks
			register_activation_hook( $plugin, 		array( $this, 'activate') );
			register_deactivation_hook( $plugin, 	array( $this, 'deactivate') );
			register_uninstall_hook( $plugin, 		$this->uninstall() );
		}//end __construct
		

		/**
		 * After all the settings are initialized, run the plugin
		 *
		 * @api
		 *
		 * @since 1.0
		 *
		 * @param string $plugin Required - The file name of the plugin, __FILE__
		 *
		 * @return none
		 */
		 public function run( $plugin )
		 {
			add_action( 'admin_init', 				array( $this, 'init' ) );
			
			add_action('admin_enqueue_scripts', 	array( $this, 'enqueue_admin_scripts' ));
			add_action('wp_enqueue_scripts', 		array( $this, 'enqueue_user_scripts' ));

			// If the plugin uses settings add them
			if ( $this->options )
			{
				if ( $this->widget )
					$this->options->has_widget_options = true;
									
				if ( $this->uses_shortcodes )
					$this->options->set_value( 'shortcodes', $this->shortcodes );

				if ( !empty( $this->plugin_icon ))
					$this->options->set_value( 'plugin_icon', $this->plugin_icon );

				$this->options->has_settings_options = true;

				$this->options->init();
				
			}//end if
			
			if ( $this->options || $this->widget )
			{
				$this->uses_smarty = true; // Smarty required to display HTML on settings and widget pages
			}//end if
			
			if ( $this->uses_smarty )
			{
				require_once( 'class.easy-dev-smarty.php' );
			}//end if
		 }//end setup


		/**
		 * Function to initialize the plugin on install or activation
		 *
		 * @ignore - Must be public because used in WordPress hooks
		 *
		 * @since 1.0
		 *
		 * @param none
		 *
		 * @return none (extend to add additional checks)
		 */
		public function init() 
		{
			if ( $this->required_wordpress_version )
			{
				global $wp_version;
								
				if (version_compare($wp_version, $this->required_wordpress_version, "<"))
				{
			
					add_action( 'admin_notices', function (){
						global $wp_version;
						
						$message =  $this->plugin_title . " requires WordPress version <strong>{$this->required_wordpress_version} or higher</strong>.<br>You have version <strong>$wp_version</strong> installed.";
					    ?>
					    <div class="error">
					        <p><?php _e( $message, $this->plugin_name ); ?></p>
					    </div>
					    <?php
					} );
					
					deactivate_plugins( $this->plugin_base_name );
					
					return;
				}//endif
			}//endif
		}//end init
		
		/**
		 * Method to intialize the options class for this plugin
		 *
		 * @since 1.0
		 *
		 * @param TSP_Easy_Dev_Options $options Required The settings handler class for this plugin
		 * @param boolean $has_post_options Optional Does the plugin save post options?
		 * @param boolean $has_term_options Optional Does the plugin save term/category options?
		 *
		 * @return none
		 */
		public function set_options_handler( $options, $has_post_options = false, $has_term_options = false ) 
		{
			if ( is_subclass_of( $options, 'TSP_Easy_Dev_Options' ) )
			{
				$this->options 						= $options;
				$this->options->has_post_options 	= $has_post_options;
				$this->options->has_term_options 	= $has_term_options;
			}//end if
			else
			{
				wp_die ( "The settings handler must be a subclass of TSP_Easy_Dev_Options." );
			}//end else
		}//end set_options_handler
		
		
		/**
		 * Method to return the name of the widget class handler
		 *
		 * @since 1.0
		 *
		 * @param none
		 *
		 * @return TSP_Easy_Dev_Options object reference
		 */
		public function get_options_handler() 
		{
			return $this->options;
		}//end get_settings_handler
		
		/**
		 * Method to intialize the options class for this plugin
		 *
		 * @since 1.0
		 *
		 * @param string $widget Required The NAME of the widget handler class for this plugin
		 *
		 * @return none
		 */
		public function set_widget_handler( $widget ) 
		{
			$this->widget = $widget;
		}//end set_widget_handler
		
		/**
		 * Method to return the name of the widget class handler
		 *
		 * @since 1.0
		 *
		 * @param none
		 *
		 * @return string Widget class name
		 */
		public function get_widget_handler() 
		{
			return $this->widget;
		}//end get_widget_handler


		/**
		 * Add styles to the queue
		 *
		 * @api
		 *
		 * @since 1.0
		 *
		 * @param string $css Required - The full URL of the css file
		 * @param boolean $admin Optonal - Is the style for the admin or user interface
		 *
		 * @return none
		 */
		 public function add_css( $css, $admin = false )
		 {
			if ( $admin )
			{
				$this->admin_css_files[]  	= $css;
			}//endif
			else
			{
				$this->user_css_files[] 	= $css;
			}//end else
		 }//end add_css


		/**
		 * Add user scripts to the queue
		 *
		 * @api
		 *
		 * @since 1.0
		 *
		 * @param string $script Required - The full URL of the script file
		 * @param array $required_scripts Optonal - Array of required script tags (ie 'jquery','jquery-ui-widget')
		 * @param boolean $admin Optonal - Is the style for the admin or user interface
		 *
		 * @return none
		 */
		 public function add_script( $script, $required_scripts = array(), $admin = false )
		 {
			if ( $admin )
			{
				$this->admin_js_files[$script] 	= $required_scripts;
			}//endif
			else
			{
				$this->user_js_files[$script] 	= $required_scripts;
			}//end else
		 }//end add_css

		/**
		 * Add short codes for processing
		 *
		 * @api
		 *
		 * @since 1.0
		 *
		 * @param string $tag Required - the tag name of the shortcode
		 *
		 * @return none
		 */
		 public function add_shortcode( $tag )
		 {
			if ( $this->uses_shortcodes )
			{
				$this->shortcodes[] = $tag;
			}//endif
		 }//end add_shortcode

		/**
		 * Set the plugin icon (used by options class on run)
		 *
		 * @api
		 *
		 * @since 1.0
		 *
		 * @param string $icon Required - The full URL of the icon file
		 *
		 * @return none
		 */
		public function set_plugin_icon( $icon )
		{
			$this->plugin_icon = $icon;
		}//end set_plugin_icon

		/**
		 *  Implementation to queue user scripts and stylesheets
		 *
		 * @ignore - Must be public because used in WordPress hooks
		 *
		 * @since 1.0
		 *
		 * @param none
		 *
		 * @return none
		 */
		public function enqueue_user_scripts()
		{
			foreach ($this->user_css_files as $style)
			{
				$tag = basename($style);
				$tag = preg_replace( "/\.css/", "", $tag);
				$tag = preg_replace( "/-|\./", "_", $tag);
				$tag = "tsp_css_" . $tag;
				
				wp_register_style( $tag, $style );
				wp_enqueue_style( $tag );
			}//endforeach
			
			foreach ($this->user_js_files as $script => $requires)
			{
				$tag = basename($script);
				$tag = preg_replace( "/\.js/", "", $tag);
				$tag = preg_replace( "/-|\./", "_", $tag);
				$tag = "tsp_js_" . $tag;
				
				wp_register_script( $tag, $script, $requires );
				wp_enqueue_script( $tag );
			}//endforeach

		}//end  enqueue_styles
		
		/**
		 *  Implementation to queue admin scripts and stylesheets
		 *
		 * @ignore - Must be public because used in WordPress hooks
		 *
		 * @since 1.0
		 *
		 * @param none
		 *
		 * @return none
		 */
		public function enqueue_admin_scripts()
		{
			foreach ($this->admin_css_files as $style)
			{
				$tag = basename($style);
				$tag = preg_replace( "/\.css/", "", $tag);
				$tag = preg_replace( "/-|\./", "_", $tag);
				$tag = "tsp_css_" . $tag;
				
				wp_register_style( $tag, $style );
				wp_enqueue_style( $tag );
			}//endforeach
			
			foreach ($this->admin_js_files as $script => $requires)
			{
				$tag = basename($script);
				$tag = preg_replace( "/\.js/", "", $tag);
				$tag = preg_replace( "/-|\./", "_", $tag);
				$tag = "tsp_js_" . $tag;
				
				wp_register_script( $tag, $script, $requires );
				wp_enqueue_script( $tag );
			}//endforeach
		}//end enqueue_scripts
		 				
		/**
		 * Optional implementation to activate plugin - can be extended by subclasses, not to be called directly but extended by subclasses
		 *
		 * @api
		 *
		 * @since 1.0
		 *
		 * @param none
		 *
		 * @return string $message Optional any messages generated by deactivation
		 */
		public function activate()
		{
			$message = "";

			// If the plugin requries smarty create cache and compiled directories
			if ( $this->uses_smarty )
			{
				$smarty_cache_dir = $this->options->get_value('smarty_cache_dir');
				$smarty_compiled_dir = $this->options->get_value('smarty_compiled_dir');
				
				if ( !file_exists( $smarty_cache_dir ) )
				{
					if (!@mkdir( $smarty_cache_dir, 0777, true ))
					{
						$message .= "<br>Unable to create $smarty_cache_dir directory. Please create this directory manually via FTP or cPanel.";
					}//end if
				}//end if

				if ( !file_exists( $smarty_compiled_dir ) )
				{
					if (!!@mkdir( $smarty_compiled_dir, 0777, true ))
					{
						$message .= "<br>Unable to create $smarty_compiled_dir directory. Please create this directory manually via FTP or cPanel.";
					}//end if
				}//end if
			}//end if

			return $message;
		}//end activate
		
		/**
		 * Optional implementation to deactivate plugin - can be extended by subclasses, not to be called directly but extended by subclasses
		 *
		 * @api
		 *
		 * @since 1.0
		 *
		 * @param none
		 *
		 * @return string $message Optional any messages generated by deactivation
		 */
		public function deactivate()
		{
			$message = "";
			
			// If the plugin requries smarty create cache and compiled directories
			if ( $this->uses_smarty )
			{
				$smarty_cache_dir 		= $this->options->get_value('smarty_cache_dir');
				$smarty_compiled_dir 	= $this->options->get_value('smarty_compiled_dir');

				if ( file_exists( $smarty_cache_dir ) )
				{
					if (!@rmdir( $smarty_cache_dir ))
					{
						$message .= "<br>Unable to remove $smarty_cache_dir directory. Please remove this directory manually via FTP or cPanel.";
					}//end if
				}//end if
				
				if ( file_exists( $smarty_compiled_dir ) )
				{
					if (!@rmdir( $smarty_compiled_dir ))
					{
						$message .= "<br>Unable to remove $smarty_compiled_dir directory. Please remove this directory manually via FTP or cPanel.";
					}//end if
				}//end if
			}//end if
			
			return $message;
		}//end deactivate
		

		/**
		 * Optional implementation to uninstall plugin - can be extended by subclasses, not to be called directly but extended by subclasses
		 *
		 * @api
		 *
		 * @since 1.0
		 *
		 * @param none
		 *
		 * @return string $message Optional any messages generated by uninstall
		 */
		public function uninstall()
		{
			if ( $this->options )
			{
				$this->options->deregister_options();
			}//end if
		}//end uninstall
	}//end TSP_Easy_Dev
}//endif	
?>