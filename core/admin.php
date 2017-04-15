<?php
/**
 * Register admin area menu item
 */
function bpel_admin_menu() {

	add_submenu_page(
		'edit.php?post_type=' . bp_get_email_post_type(),
		__( 'BP Emails Log', BPEL_I18N ),
		__( 'Log', BPEL_I18N ),
		'manage_options',
		'bp-emails-log',
		'bpel_admin_page'
	);
}

add_action( 'bp_admin_menu', 'bpel_admin_menu' );

/**
 * Display a page with a list
 */
function bpel_admin_page() {
	if ( ! class_exists( 'BPEL_List_Records' ) ) {
		require_once( dirname( __FILE__ ) . '/class-bpel-list-records.php' );
	}
	$list = new BPEL_List_Records();
	$list->prepare_items();

	?>

	<div class="wrap">
		<h1><?php _e( 'BuddyPress Sent Emails Log', BPEL_I18N ); ?></h1>

		<p class="description"><?php _e( 'Click on an email title in a table to preview, what exactly was sent to a user. Users names are linked directly to BuddyPress profiles.', BPEL_I18N ); ?></p>

		<form id="bp-emails-records" method="get">
			<!-- Ensure that the form posts back to our current page -->
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>

			<p class="search-box"><?php $list->search_box( __( 'Search', BPEL_I18N ), 'bp-emails-records' ); ?></p>

			<?php $list->display(); ?>
		</form>

	</div>

	<?php
}
