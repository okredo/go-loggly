<?php

class GO_Loggly_Admin extends GO_Loggly
{
	public $var_dump = FALSE;
	public $week     = 'curr_week';
	public $limit    = 100;
	public $limits   = array(
		'100'  => '100',
		'250'  => '250',
		'500'  => '500',
		'1000' => '1000',
	);
	public $current_loggly_vars;
	public $domain_suffix;

	/**
	 * Constructor to establish ajax endpoints
	 */
	public function __construct()
	{
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_ajax_go-loggly-clear', array( $this, 'clear_log' ) );
	} //end __construct

	public function admin_init()
	{
		wp_enqueue_style( 'go-loggly', plugins_url( 'css/go-loggly.css', __FILE__ ) );
		wp_enqueue_script( 'go-loggly', plugins_url( 'js/go-loggly.js', __FILE__ ), array( 'jquery' ) );
	} //end admin_init

	public function admin_menu()
	{
		add_submenu_page( 'tools.php', 'View Loggly', 'View Loggly', 'manage_options', 'go-loggly-show', array( $this, 'show_log' ) );
	} //end admin_menu

	/**
	 * Delete all entries in the log
	 */
	public function clear_log()
	{
		if (
			   ! current_user_can( 'manage_options' )
			|| ! isset( $_REQUEST['_wpnonce'] )
			|| ! isset( $_REQUEST['week'] )
			|| ! isset( $this->domain_suffix[ $_REQUEST['week'] ] )
			|| ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'go_loggly_clear' )
		)
		{
			wp_die( 'Not cool', 'Unauthorized access', array( 'response' => 401 ) );
		} //end if

		// figure out the actual clear mechanism here
		//$this->simple_db()->deleteDomain( $this->config['aws_sdb_domain'] . $this->domain_suffix[ $_REQUEST['week'] ] );

		wp_redirect( admin_url( 'tools.php?page=go-loggly-show&loggly-cleared=yes' ) );
		die;
	} //end clear_log

	/**
	 * Formats data for output
	 *
	 * @param array $data
	 * @return string formatted data
	 */
	public function format_data( $data )
	{
		if ( $this->var_dump == FALSE )
		{
			$data = print_r( unserialize( $data ), TRUE );
		} // end if
		else
		{
			ob_start();
			var_dump( unserialize( $data ) );
			$data = ob_get_clean();
		} // end else

		return $data;
	} //end format_data

	/**
	 * Show the contents of the log
	 *
	 * @return Null
	 */
	public function show_log()
	{
		if ( ! current_user_can( 'manage_options' ) )
		{
			return;
		} //end if

		nocache_headers();

		$this->var_dump = isset( $_GET['var_dump'] ) ? TRUE : FALSE;

		$log_query = $this->log_query();
wlog($log_query);
		$this->current_loggly_vars = $this->var_dump ? '&var_dump=yes' : '';
		$this->current_loggly_vars .= 50 != $this->limit ? '&limit=' . $this->limit : '';
		$this->current_loggly_vars .= 'curr_week' != $this->week ? '&week=' . $this->week : '';
		$this->current_loggly_vars .= isset( $_REQUEST['host'] ) && '' != $_REQUEST['host'] ? '&host=' . $_REQUEST['host'] : '';
		$this->current_loggly_vars .= isset( $_REQUEST['code'] ) && '' != $_REQUEST['code'] ? '&code=' . $_REQUEST['code'] : '';
		// Handle the two cases of a message value separately
		$this->current_loggly_vars .= isset( $_POST['message'] ) && '' != $_POST['message'] ? '&message=' . base64_encode( $_POST['message'] ) : '';
		$this->current_loggly_vars .= isset( $_GET['message'] ) && '' != $_GET['message'] ? '&message=' . $_GET['message'] : '';

		$js_loggly_url = 'tools.php?page=go-loggly-show' . preg_replace( '#&limit=[0-9]+|&week=(curr_week|prev_week)#', '', $this->current_loggly_vars );

		require_once __DIR__ . '/class-go-loggly-admin-table.php';

		$go_loggly_table = new GO_Loggly_Admin_Table();

		$go_loggly_table->log_query = $log_query;
		?>
		<div class="wrap view-loggly">
			<?php screen_icon( 'tools' ); ?>
			<h2>
				View Slog
				<select name='go_loggly_week' class='select' id="go_loggly_week">
					<?php echo $this->build_options( array( 'curr_week' => 'Current Week', 'prev_week' => 'Previous Week' ), $this->week ); ?>
				</select>
			</h2>
			<?php
			if ( isset( $_GET['loggly-cleared'] ) )
			{
				?>
				<div id="message" class="updated">
					<p>Slog cleared!</p>
				</div>
				<?php
			}

			$go_loggly_table->prepare_items();
			$go_loggly_table->custom_display();
			?>
			<input type="hidden" name="js_loggly_url" value="<?php echo esc_attr( $js_loggly_url ); ?>" id="js_loggly_url" />
		</div>
		<?php
	} //end show_log

	/**
	 * Returns relevant log items from the log
	 *
	 * @return array log data
	 */
	public function log_query()
	{
		$this->limit = isset( $_GET['limit'] ) && isset( $this->limits[ $_GET['limit'] ] ) ? $_GET['limit'] : $this->limit;
		$this->week  = isset( $_GET['week'] ) && isset( $this->domain_suffix[ $_GET['week'] ] ) ? $_GET['week'] : $this->week;
		$next_token  = isset( $_GET['next'] ) ? base64_decode( $_GET['next'] ) : NULL;

		return go_loggly()->search( '*&from=-30s&until=now' );
	} //end log_query

	/**
	 * Return SQL limits for the three search/filter fields
	 *
	 * @return string $limits limits for the query
	 */
	public function search_limits()
	{
		$limits = '';

		if ( isset( $_REQUEST['host'] ) && '' != $_REQUEST['host'] )
		{
			$limits .= " AND host = '" . esc_sql( $_REQUEST['host'] ) . "'";
		} // END if

		if ( isset( $_REQUEST['code'] ) && '' != $_REQUEST['code'] )
		{
			$limits .= " AND code = '" . esc_sql( $_REQUEST['code'] ) . "'";
		} // END if

		if ( isset( $_REQUEST['message'] ) && '' != $_REQUEST['message'] )
		{
			$message = isset( $_GET['message'] ) ? base64_decode( $_GET['message'] ) : $_POST['message'];
			$limits .= " AND message LIKE '%" . esc_sql( $message ) . "%'";
		} // END if

		return $limits;
	} //end search_limits

	/**
	 * Helper function to build select options
	 *
	 * @param array $options of options
	 * @param string $existing which option to preselect
	 * @return string $select_options html options
	 */
	public function build_options( $options, $existing )
	{
		$select_options = '';

		foreach ( $options as $option => $text )
		{
			$select_options .= '<option value="' . esc_attr( $option ) . '"' . selected( $option, $existing, FALSE ) . '>' . $text . "</option>\n";
		} //end foreach

		return $select_options;
	} //end build_options
}//end GO_Loggly_Admin