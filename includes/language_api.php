<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}
/**
 * Get frontend active language.
 * @return mixed
 */
function fw_ext_translation_get_frontend_active_language() {
	return fw_ext( 'translation' )->get_frontend_active_language();
}

/**
 * Get backend active language.
 * @return mixed
 */
function fw_ext_translation_get_backend_active_language() {
	return fw_ext( 'translation' )->get_admin_active_language();
}