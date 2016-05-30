<?php 

/**
 * Taxonomy command
 * 
 * Base class which should be extended to make WooCommerce taxonomy commands
 * 
 * @class    WC_CLI_Taxonomy_Command
 * @since    2.5.0
 * @package  WooCommerce/CLI
 * @category CLI
 * @author   CoolBlueWeb 
 */

abstract class WP_CLI_Taxonomy_Command extends WC_CLI_Command {
	
	abstract public function list_();
	abstract public function get();
	abstract public function create();
	abstract public function update();
	abstract public function delete();
	
	/**
	 * Ensure that a the given term_id is associated with an existing term.  Throws an error if not found.
	 *
	 * @param int $term_id
	 * @throws WC_CLI_Exception
	 */
	protected function assert_term_exists( $term_id ) {

		// Load the category (taxonomy term)
		$term = get_term( $term_id, 'product_cat', ARRAY_A );

		if ( ! $term ) {

			throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product_category_id',
				sprintf( __( 'Invalid product category ID "%s"', 'woocommerce' ), $term_id ) );
		}
	}

	/**
	 * Ensure that a given term did not error our during loading our processing.  Throws exception if error
	 * is found.
	 *
	 * @pre  The $term must be an associative array to utilize this method.
	 *
	 * @param array $term
	 * @throws WC_CLI_Exception
	 */
	protected function assert_no_wp_error( $term ) {

		// Validate the Product Category (term)
		if( is_object( $term ) && ( 'WP_Error' == get_class( $term ) ) ) {
			$error = array_pop( $term->errors );
			throw new WC_CLI_Exception( $error[0], key( $error ) );
		}
	}

	/**
	 * Pass in the assoc_args array to filter out the key->value pairs which are applied directly to the new term.
	 *
	 * @mutator
	 *
	 * @param $assoc_args  The array of associated args to filter
	 */
	protected function filter_insert_keys_from_assoc_args( &$assoc_args ) {

		unset( $assoc_args[ 'name' ] );
		unset( $assoc_args[ 'slug' ] );
		unset( $assoc_args[ 'parent' ] );
		unset( $assoc_args[ 'description' ] );
		unset( $assoc_args[ 'alias_of' ] );
	}
}