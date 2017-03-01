<?php

/**
 * Plugin Name: BuddyPress Emails Log
 * Plugin URI:  https://ovirium.com/
 * Description: Display in admin area the list of all sent BuddyPress emails
 * Author:      slaFFik
 * Author URI:  https://ovirium.com/
 * Version:     1.0
 * Text Domain: bp-emails-log
 * Domain Path: /langs/
 * License:     GPLv2 or later (license.txt)
 */
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

define( 'BPEL_I18N', 'bp-emails-log' );
define( 'BPEL_CPT', 'bp-email-record' );

/**
 * RLoad admin area
 */
function bpel_load_admin_area() {
	include_once dirname( __FILE__ ) . '/core/admin.php';
}

add_action( 'init', 'bpel_load_admin_area' );

/**
 * Load everything CPT related
 */
function bpel_load_cpt() {
	include_once dirname( __FILE__ ) . '/core/cpt.php';
}

add_action( 'bp_init', 'bpel_load_cpt' );

/**
 * @param WP_Error|bool $status
 * @param BP_Email $email
 */
function bpel_track_emails_success( $status, $email ) {
	$to = $email->get( 'to' );

	wp_insert_post( array(
		                'post_title'   => $email->get( 'subject', 'replace-tokens' ),
		                'post_content' => $email->get( 'content_html', 'replace-tokens' ),
		                'post_type'    => BPEL_CPT,
		                'post_excerpt' => array_shift( $to )->get_address(),
		                'post_status'  => 'success'
	                ) );
}

add_action( 'bp_send_email_success', 'bpel_track_emails_success', 10, 2 );

/**
 * @param WP_Error|bool $status
 * @param BP_Email $email
 */
function bpel_track_emails_failure( $status, $email ) {
	$to = $email->get( 'to' );

	wp_insert_post( array(
		                'post_title'   => $email->get( 'subject', 'replace-tokens' ),
		                'post_content' => $email->get( 'content_html', 'replace-tokens' ),
		                'post_type'    => BPEL_CPT,
		                'post_excerpt' => array_shift( $to )->get_address(),
		                'post_status'  => 'failure'
	                ) );
}

add_action( 'bp_send_email_failure', 'bpel_track_emails_failure', 10, 2 );
