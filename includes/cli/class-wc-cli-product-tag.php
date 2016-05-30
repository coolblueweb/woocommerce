<?php

/**
 * Manage Product Categories.
 *
 * @since    2.8.0
 * @package  WooCommerce/CLI
 * @category CLI
 * @author   WooThemes
 */
class WC_CLI_Product_Tag extends WC_CLI_Command {

	/**
	 * Update an existing product tag 
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the product category being updated
	 *
	 * [--name=<name>]
	 * : Assign a new name to the tag.
	 *
	 * [--alias_of=<id>]
	 * : Assign an alias to this tag using the target tag's ID
	 *
	 * [--description=<string>]
	 * : Assign a description to the new tag
	 *
	 * [--slug=<string>]
	 * : Assign a slug for the new tag
	 *
	 * [--<field>=<value>]
	 * : Assign any number of assocative metadata key=>value pairs.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc product tag update 75
	 *
	 *     wp wc product tag update 75 --name="new_name" --description="New Category Field" --slug=new-tag-name
	 *
	 * @subcommand update
	 * @since      2.8.0
	 */
	public function update( $args, $assoc_args ) {

		try {
			// Ensure the term exists before starting.
			$this->assert_term_exists( $args[0] );

			// Load the category (taxonomy term)
			$term = get_term( $args[0], 'product_cat', ARRAY_A );

			// Merge the CLI arguments with the original term (product category) arguments
			$this->assert_no_wp_error( $term );
			$updated_term_values = array_merge( $term, $assoc_args );
			$this->assert_no_wp_error( $term );

			// Update the term (product category)
			$updated_term = wp_update_term( $term[ 'term_id' ], 'product_cat', $updated_term_values );
			$this->assert_no_wp_error( $updated_term );

			// Filter out the core term values, then use remaining values to update metafields
			$this->filter_insert_keys_from_assoc_args( $updated_term_values );

			foreach( $updated_term_values as $meta_key => $meta_value ) {
				update_term_meta( $updated_term_values[ 'term_id' ], $meta_key, $meta_value );
			}

			// Reload the category (taxonomy term) to ensure that we're relating about persistent data
			$term = get_term( $args[0], 'product_cat', ARRAY_A );

			WP_CLI::success( sprintf( __('Product Category "%s" was updated successfully.', 'woocommerce' ),
				$term['name'] ) );
		}
		catch ( WC_CLI_Exception $ex ) {

			WP_CLI::error( $ex->getErrorCode() );
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
}
