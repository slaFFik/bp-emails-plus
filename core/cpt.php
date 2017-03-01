<?php

/**
 * Register a CPT that will store all sent emails
 */
function bpel_register_cpt() {
	register_post_type( BPEL_CPT, array(
		'label'  => __( 'BP Email Log Records', BPEL_I18N ),
		'public' => false
	) );
}

add_action( 'bp_init', 'bpel_register_cpt' );

/**
 * Register custom post statuses to track the difference between successful and failed emails delivery
 */
function bpel_register_cpt_statuses() {
	register_post_status( 'success', array(
		'label'                     => _x( 'Success', 'BP emails log record status', BPEL_I18N ),
		'public'                    => true,
		'internal'                  => true,
		'exclude_from_search'       => true,
		'show_in_admin_all_list'    => false,
		'show_in_admin_status_list' => false,
		'label_count'               => _n_noop( 'Success <span class="count">(%s)</span>', 'Success <span class="count">(%s)</span>', BPEL_I18N ),
	) );

	register_post_status( 'failure', array(
		'label'                     => _x( 'Failure', 'BP emails log record status', BPEL_I18N ),
		'public'                    => true,
		'internal'                  => true,
		'exclude_from_search'       => true,
		'show_in_admin_all_list'    => false,
		'show_in_admin_status_list' => false,
		'label_count'               => _n_noop( 'Failure <span class="count">(%s)</span>', 'Failure <span class="count">(%s)</span>', BPEL_I18N ),
	) );
}

add_action( 'init', 'bpel_register_cpt_statuses' );

/**
 * Get the list of all possible custom post statuses
 *
 * @return array
 */
function bpel_get_possible_stati() {
	return array( 'success', 'failure' );
}

/**
 * Get the current post status from the $_REQUEST global variable
 *
 * @return string
 */
function bpel_get_current_status() {
	$status = 'all';

	if (
		! empty( $_REQUEST['post_status'] ) &&
		in_array( $_REQUEST['post_status'], bpel_get_possible_stati() )
	) {
		$status = $_REQUEST['post_status'];
	}

	return $status;
}

function bpel_get_current_order() {
	$order = 'desc';

	if ( ! empty( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], array( 'asc', 'desc' ) ) ) {
		$order = $_REQUEST['order'];
	}

	return $order;
}

/**
 * @param WP_Post $record
 *
 * @return string
 */
function bpel_generate_preview_link( $record ) {
	return $record->guid;
}