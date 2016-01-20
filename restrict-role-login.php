<?php
/**
 * Plugin Name: Restrict Role Login
 * Plugin URI: http://lab.konnektiv.de/giz/wp-plugins/restrict-role-login
 * Description: Allows administrators to restrict user login based on roles.
 * Version: 0.0.1
 * Author: Konnektiv
 * Author URI: http://konnektiv.de/
 * License: GPLv2 (license.txt)
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class RestrictLogin {

	/**
	 * @var RestrictLogin
	 */
	private static $instance;

	/**
	 * Main RestrictLogin Instance
	 *
	 * Insures that only one instance of RestrictLogin exists in memory at
	 * any one time. Also prevents needing to define globals all over the place.
	 *
	 * @since RestrictLogin (0.0.1)
	 *
	 * @staticvar array $instance
	 *
	 * @return RestrictLogin
	 */
	public static function instance( ) {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new RestrictLogin;
			self::$instance->setup_globals();
			self::$instance->setup_filters();
			self::$instance->setup_actions();
		}

		return self::$instance;
	}

	/**
	 * A dummy constructor to prevent loading more than one instance
	 *
	 * @since RestrictLogin (0.0.1)
	 */
	private function __construct() { /* Do nothing here */
	}


	/**
	 * Component global variables
	 *
	 * @since RestrictLogin (0.0.1)
	 * @access private
	 *
	 */
	private function setup_globals() {
		$this->options = get_option( 'rrl_options' );
	}

	/**
	 * Setup the filters
	 *
	 * @since RestrictLogin (0.0.1)
	 * @access private
	 *
	 * @uses remove_filter() To remove various filters
	 * @uses add_filter() To add various filters
	 */
	private function setup_filters() {
		add_filter('wp_authenticate_user', array($this, 'restrict_login' ),10,2);
	}

	/**
	 * Setup the actions
	 *
	 * @since RestrictLogin (0.0.1)
	 * @access private
	 *
	 * @uses remove_action() To remove various actions
	 * @uses add_action() To add various actions
	 */
	private function setup_actions() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'init_settings_page' ) );
	}

	function restrict_login($user, $password) {

		if (is_wp_error($user))
			return $user;

		$roles = $this->options['allowed_roles'];
		if (!$this->options['restrict_login'] || array_reduce($roles, function($allowed, $role) use ($user) {
				return $allowed || user_can($user, $role);
			}, false))
			return $user;

		return new WP_Error('auth', 'Access denied!');
	}

	/**
	 * Add options page
	 */
	function add_settings_page() {
		// This page will be under "Users"
		add_users_page(
			'Restrict role login Settings',
			'Restrict role login',
			'manage_options',
			'rrl-setting-admin',
			array( $this, 'create_settings_page' )
		);
	}

	/**
	 * Options page callback
	 */
	public function create_settings_page()
	{
		?>
		<div class="wrap">
			<h2>Restrict role login Settings</h2>
			<form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields( 'restrict_role_login_option_group' );
				do_settings_sections( 'rrl-setting-admin' );
				submit_button();
				?>
			</form>
		</div>
	<?php
	}

	/**
	 * Register and add settings
	 */
	function init_settings_page() {
		register_setting(
			'restrict_role_login_option_group', // Option group
			'rrl_options', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);

		add_settings_section(
			'rrl_section', // ID
			null, // Title
			null, // Callback
			'rrl-setting-admin' // Page
		);

		add_settings_field(
			'rrl_restrict_login', // ID
			'Restrict login by user role', // Title
			array( $this, 'restrict_login_settings_field_callback' ), // Callback
			'rrl-setting-admin', // Page
			'rrl_section'
		);

		add_settings_field(
			'rrl_allowed_roles', // ID
			'Roles allowed to login', // Title
			array( $this, 'allowed_roles_settings_field_callback' ), // Callback
			'rrl-setting-admin', // Page
			'rrl_section'
		);
	}

	public function restrict_login_settings_field_callback() { ?>
		<input id="rrl_option_all_roles" type="checkbox" name="rrl_options[restrict_login]" value="1"
			<?php  checked(true, $this->options['restrict_login']) ?> >
		<label for="rrl_option_all_roles"><?php _e('Check to restrict login by user role', 'restrict-role-login') ?></label><br>
	<?php }

	public function allowed_roles_settings_field_callback() {

		$roles = get_editable_roles();

		foreach($roles as $role_name => $role){ ?>

			<input type="checkbox" id="rrl_option_<?php echo $role_name ?>"
				   name="rrl_options[allowed_roles][<?php echo $role_name ?>]" value="<?php echo $role_name ?>"
				   <?php checked($role_name, $this->options['allowed_roles'][$role_name]) ?> >
			<label for="rrl_option_<?php echo $role_name ?>"><?php echo $role['name'] ?></label><br>
		<?php }
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 * @return array
	 */
	public function sanitize( $input )
	{
		return $input;
	}
}

RestrictLogin::instance();