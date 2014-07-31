<?php

class GO_Loggly_Admin_Table extends WP_List_Table
{
	public $log_query = NULL;

	public function __construct()
	{
		// Set parent defaults
		parent::__construct(
			array(
				'singular' => 'loggly-item',  //singular name of the listed records
				'plural'   => 'loggly-items', //plural name of the listed records
				'ajax'     => FALSE,          //does this table support ajax?
			)
		);
	} //end __construct

	/**
	 * Display the various columns for each item, it first checks
	 * for a column-specific method. If none exists, it defaults to this method instead.
	 *
	 * @param array $item This is used to store the raw data you want to display.
	 * @param string $column_name a slug of a column name
	 * @return string $items an item index at the $column_name
	 */
	public function column_default( $item, $column_name )
	{
		if ( array_key_exists( $column_name, $this->_column_headers[0] ) )
		{
			return $item[ $column_name ];
		} //end if
	} //end column_default

	/**
	 * Custom display stuff for the data column
	 *
	 * @param array $item this is used to store the loggly data you want to display.
	 * @return String an index of the array $item
	//NOTE: keeping this in here for now; we know we'll require some serialized data handling for our custom log events.
	public function column_loggly_data( $item )
	{
		return '<pre>' . $item['loggly_data'] . '</pre>';
	} //end column_loggly_data
	 */

	/**
	 * Return an array of the columns with keys that match the compiled items
	 *
	 * @return array $columns an associative array of columns
	 */
	public function get_columns()
	{
		$columns = array(
			'loggly_item_num'=> 'Number',
			'loggly_id'      => 'ID',
			'loggly_date'    => 'Date',
			'loggly_tags'    => 'Tags',
			'loggly_host'    => 'Host',
			'loggly_message' => 'Message',
			//'loggly_data'    => 'Data',
		);

		return $columns;
	} //end get_columns

	/**
	 * Display the individual rows of the table
	 *
	 * @param array $item an array of search controls
	 */
	public function single_row( $item )
	{
		static $row_class = '';
		$row_class = ( '' == $row_class ) ? ' class="alternate"' : '';

		echo '<tr' . $row_class . '>';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	} //end single_row

	/**
	 * Display nav items for the table
	 *
	 * @param string $which "top" to display the nav, else "bottom"
	 */
	public function display_tablenav( $which )
	{
		if ( 'top' == $which )
		{
			$this->table_nav_top();
		}
		else
		{
			$this->table_nav_bottom();
		} //end else
	} //end display_tablenav

	/**
	 * Display nav items for above the table
	 */
	public function table_nav_top()
	{
		$count = count( $this->items );
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>" style="min-height: 43px;">
			<div class="alignleft">
				<p>
					<?php echo $count; ?> Log Items
				</p>
			</div>
			<div class="alignright">
				<?php
				if ( 1 < $count )
				{
					?>
					<!-- preserve this space for something, possibly also export and clear -->
					<?php
				} // END if
				?>
			</div>
			<br class="clear" />
		</div>
		<?php
	} //end table_nav_top

	/**
	 * Display nav items to show below the table.
	 */
	public function table_nav_bottom()
	{
		if ( '' != Go_Slog::simple_db()->NextToken )
		{
			$next_link = 'tools.php?page=go-loggly-show' . go_loggly()->admin->current_loggly_vars . '&next=' . base64_encode( Go_Slog::simple_db()->NextToken );
			?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<span class="pagination-links">
						<a class="next-page" href="<?php echo $next_link; ?>">
							Next Page &rsaquo;
						</a>
					</span>
				</div>
			</div>
			<?php
		} //end if
	} //end table_nav_bottom

	/**
	 * Initial prep for WP_List_Table
	 *
	 * @global wpdb $wpdb
	 */
	public function prepare_items()
	{
		// Set columns
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $this->compile_posts();
	} //end prepare_items

	/**
	 * Display the log or an error message that the log is empty
	 */
	public function custom_display()
	{
		if ( ! empty( $this->items ) )
		{
			$this->display();
		} //end if
		else
		{
			?>
			<div id="message" class="error">
				<p>Your log is empty.</p>
			</div>
			<?php
		} //end else
	} //end custom_display

	/**
	 * Compile the log items into a format appropriate for WP_List_Table
	 *
	 * @return array $compiled
	 */
	public function compile_posts()
	{
		$compiled = array( array( 'search' => TRUE ) );

		foreach ( $this->log_query as $key => $row )
		{
			$compiled[] = array(
				'loggly_item_num' => esc_html( $key ),
				'loggly_id'       => esc_html( $row->id ),
				'loggly_date'     => esc_html( $row->event->syslog->timestamp ),
				'loggly_tags'     => esc_html( $row->tags[0] ),
				'loggly_host'     => esc_html( $row->event->syslog->host ),
				'loggly_message'  => esc_html( $row->logmsg ),
				//'loggly_data'    => esc_html( go_loggly()->admin->format_data( $row['data'] ) ),
			);
		} //end foreach

		return $compiled;
	} //end compile_posts
}
//end GO_Loggly_Admin_Table