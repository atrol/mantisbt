<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Crypto API
 *
 * @package CoreAPI
 * @subpackage CryptoAPI
 * @copyright Copyright 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright 2002  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses error_api.php
 */

require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'error_api.php' );

/**
 * Initialize the CryptoAPI subsystem.
 *
 * This function checks whether the master salt is specified correctly within
 * the configuration. If not, a fatal error is produced to protect against
 * invalid configuration impacting the security of the MantisBT installation.
 *
 * @return void
 */
function crypto_init() {
	if( !defined( 'MANTIS_MAINTENANCE_MODE' ) ) {
		if( strlen( config_get_global( 'crypto_master_salt' ) ) < 16 ) {
			trigger_error( ERROR_CRYPTO_MASTER_SALT_INVALID, ERROR );
		}
	}
}

/**
 * Generate a random string (raw binary output) for cryptographic purposes such
 * as nonces, IVs, default passwords, etc.
 *
 * This function will attempt to generate strong randomness but can optionally
 * be used to generate weaker randomness if less security is needed or a strong
 * source of randomness isn't available. The use of weak randomness for
 * cryptographic purposes is strongly discouraged because it contains low
 * entropy and is predictable.
 *
 * @param int $p_bytes                     Number of bytes of randomness required.
 * @param bool $p_require_strong_generator Whether a weak source of randomness
 *                                         can be used by this function (unused,
 *                                         kept for backwards compatibility).
 *
 * @return string|null Raw binary string containing the requested number of bytes
 *                     of random output or null if the output couldn't be created.
 * @throws Exception (with PHP >= 8.2, actually \Random\RandomException)
 *
 * @deprecated 2.26.0 random_bytes() should be used in preference to this function
 * @noinspection PhpUnusedParameterInspection
 */
function crypto_generate_random_string( $p_bytes, $p_require_strong_generator = true ) {
	error_parameters( __FUNCTION__ . '()', 'random_bytes()' );
	trigger_error( ERROR_DEPRECATED_SUPERSEDED, DEPRECATED );
	return random_bytes( $p_bytes );
}

/**
 * Generate a strong random string (raw binary output) for cryptographic
 * purposes such as nonces, IVs, default passwords, etc.
 *
 * If a strong source of randomness is not available, this function will fail
 * and produce an error. Strong randomness is different from weak randomness in
 * that a strong randomness generator doesn't produce predictable output and has
 * much higher entropy. Where randomness is being used for cryptographic
 * purposes, a strong source of randomness should always be used.
 *
 * @param int $p_bytes Number of bytes of strong randomness required.
 *
 * @return string Raw binary string containing the requested number of bytes of random output
 * @throws Exception (with PHP >= 8.2, actually \Random\RandomException)
 *
 * @deprecated 2.26.0 random_bytes() should be used in preference to this function
 */
function crypto_generate_strong_random_string( $p_bytes ) {
	error_parameters( __FUNCTION__ . '()', 'random_bytes()' );
	trigger_error( ERROR_DEPRECATED_SUPERSEDED, DEPRECATED );
	return random_bytes( $p_bytes );
}

/**
 * Generate a nonce encoded using the base64 with URI safe alphabet approach
 * described in RFC4648.
 *
 * Note that the minimum length is rounded up to the next number with a factor
 * of 4 so that padding is never added to the end of the base64 output. This
 * means the '=' padding character is never present in the output.
 *
 * Due to the reduced character set of base64 encoding, the actual amount of
 * entropy produced by this function for a given output string length is 3/4
 * (0.75) of the raw unencoded output produced with the
 * {@see crypto_generate_strong_random_string()} function.
 *
 * @param int $p_minimum_length Minimum number of characters required for the nonce.
 *
 * @return string Nonce encoded according to the base64 with URI safe alphabet
 *                approach described in RFC4648
 * @throws Exception (with PHP >= 8.2, actually \Random\RandomException)
 */
function crypto_generate_uri_safe_nonce( $p_minimum_length ) {
	$t_length_mod4 = $p_minimum_length % 4;
	$t_adjusted_length = $p_minimum_length + 4 - ($t_length_mod4 ?: 4);
	$t_raw_bytes_required = ( $t_adjusted_length / 4 ) * 3;
	$t_base64_encoded = base64_encode( random_bytes( $t_raw_bytes_required ) );

	# Note: no need to translate trailing = padding characters because our
	# length rounding ensures that padding is never required.
	return strtr( $t_base64_encoded, '+/', '-_' );
}
