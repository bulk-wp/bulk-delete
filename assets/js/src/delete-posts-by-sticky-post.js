/**
 * Delete Posts by Stick Post Module.
 *
 * Part of Bulk Delete plugin.
 */
/*global BulkWP */
jQuery( document ).ready( function () {
	var stickyAction = jQuery( "input[name='smbd_sticky_post_sticky_action']" ),
		deleteAction = stickyAction.parents( 'tr' ).next();

	deleteAction.hide();

	stickyAction.change( function () {
		if ( 'delete' === stickyAction.filter( ':checked' ).val() ) {
			deleteAction.show();
		} else {
			deleteAction.hide();
		}
	} );
} );

/**
 * Validate that at least one post was selected.
 *
 * @returns {boolean} True if at least one post was selected, False otherwise.
 */
BulkWP.validateStickyPost = function () {
	return jQuery( "input[name='smbd_sticky_post[]']:checked" ).length > 0;
};

BulkWP.DeletePostsByStickyPostPreAction = function () {
	var stickyAction = jQuery( "input[name='smbd_sticky_post_sticky_action']:checked" ).val();

	if ( 'unsticky' === stickyAction ) {
		return 'unstickyPostsWarning';
	} else {
		return 'deletePostsWarning';
	}
};
