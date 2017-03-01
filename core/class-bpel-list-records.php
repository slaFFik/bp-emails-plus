<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class BPEL_List_Records extends WP_List_Table {

	function __construct() {

		//Set parent defaults
		parent::__construct( array(
			                     'singular' => __( 'Record', BPEL_I18N ),
			                     'plural'   => __( 'Records', BPEL_I18N ),
			                     'ajax'     => false
		                     ) );

	}

	public function get_columns() {
		$columns = array(
			'subject' => __( 'Subject', BPEL_I18N ),
			'to'      => __( 'Sent To', BPEL_I18N ),
			'date'    => __( 'Date Sent', BPEL_I18N )
		);

		return $columns;
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
			'date' => array( 'date', true ) // already sorted DESC - latest first in a list
		);

		return $sortable_columns;
	}

	/**
	 * Display emails delivery status filters
	 *
	 * @param string $which
	 *
	 * @return string
	 */
	protected function bulk_actions( $which = '' ) {
		// display only at the top
		if ( $which == 'bottom' ) {
			return '';
		}

		$status = bpel_get_current_status();
		$link   = add_query_arg( array(
			                         'order' => bpel_get_current_order()
		                         ) );
		?>

        <ul class="subsubsub">
            <li class="all">
                <a href="<?php echo add_query_arg( 'post_status', false, $link ); ?>" <?php echo $status == 'all' ? 'class="current"' : ''; ?>>
					<?php _e( 'All', BPEL_I18N ); ?>
                    <span class="count">(<?php echo $this->get_items_count( 'all' ); ?>)</span>
                </a> |
            </li>
            <li class="success">
                <a href="<?php echo add_query_arg( 'post_status', 'success', $link ); ?>" <?php echo $status == 'success' ? 'class="current"' : ''; ?>>
					<?php _e( 'Success', BPEL_I18N ); ?>
                    <span class="count">(<?php echo $this->get_items_count( 'success' ); ?>)</span>
                </a> |
            </li>
            <li class="failure">
                <a href="<?php echo add_query_arg( 'post_status', 'failure', $link ); ?>" <?php echo $status == 'failure' ? 'class="current"' : ''; ?>>
					<?php _e( 'Failure', BPEL_I18N ); ?>
                    <span class="count">(<?php echo $this->get_items_count( 'failure' ); ?>)</span>
                </a>
            </li>
        </ul>

		<?php
	}

	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'subject':
			case 'to':
			case 'date':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Retrieve all the information
	 */
	public function prepare_items() {
		global $wpdb;
		$per_page = 20;
		$page     = ! empty( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 1;

		// define our column headers
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// build an array to be used by the class for column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$cur_status  = bpel_get_current_status();
		$post_status = $cur_status == 'all' ? '' : "AND post_status = '$cur_status'";
		$order       = 'ORDER BY post_date ' . bpel_get_current_order();
		$limit       = 'LIMIT ' . absint( ( $page - 1 ) * $per_page ) . ', ' . $per_page;

		$data = $wpdb->get_results( $wpdb->prepare(
			"SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->posts}
			WHERE post_type = %s
			  $post_status
			  $order
			  $limit",
			BPEL_CPT
		) );

		$data_found = $wpdb->get_var( ' SELECT FOUND_ROWS();' );

		$this->items = $this->populate_items_data( $data );

		// register our pagination options & calculations
		$this->set_pagination_args( array(
			                            'total_items' => $data_found,
			                            'per_page'    => $per_page,
			                            'total_pages' => ceil( $data_found / $per_page )
		                            ) );
	}

	/**
	 * Get counters of all/success/failure emails records
	 *
	 * @param string $type
	 *
	 * @return int
	 */
	function get_items_count( $type ) {
		/** @var $wpdb WPDB */
		global $wpdb;
		$count = 0;

		$type = sanitize_key( $type );

		switch ( $type ) {
			case 'all':
				$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s", BPEL_CPT ) );
				break;

			case 'success':
			case 'failure':
				$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s", BPEL_CPT, $type ) );
				break;
		}

		return $count;
	}

	/**
	 * Modify the data
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function populate_items_data( $data ) {
		$modified = array();

		foreach ( (array) $data as $record ) {
			/** @var $record WP_Post */

			array_push( $modified, array(
				'subject' => $this->populate_item_subject( $record ),
				'to'      => $this->populate_item_to( $record ),
				'date'    => $this->populate_item_date( $record ),
			) );
		}

		return $modified;
	}

	private function populate_item_subject( $record ) {
		$url = bpel_generate_preview_link( $record );

		return '<a href="' . esc_url( $url ) . '" target="_blank">'
		       . apply_filters( 'the_title', $record->post_title ) .
		       '</a>';
	}

	private function populate_item_to( $record ) {
		$user = get_user_by( 'email', $record->post_excerpt );

		if ( $user && ! is_wp_error( $user ) ) {
			$user_str = bp_core_get_userlink_by_email( $record->post_excerpt ) . ' &lt;' . $record->post_excerpt . '&gt;';
		} else {
			$user_str = $record->post_excerpt;
		}

		return $user_str;
	}

	private function populate_item_date( $record ) {
		return sprintf(
			_x( '%s ago', 'When was this email sent. Displayed in /wp-admin/: Email -> Log table', BPEL_I18N ),
			human_time_diff( strtotime( $record->post_date ) )
		);
	}

}
