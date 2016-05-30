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
	 * [--parent=<id>]
	 * : Assign a parent tag using the parent category using the parent category's ID
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
	 * [--order=<sortorder>]
	 * : Assign the sort order of this category, relative to a parent
	 *
	 * [--display_type=<value>]
	 * : Display type for the Product Category.  default, products, subcategories, both
	 *
	 * [--<field>=<value>]
	 * : Assign any number of assocative metadata key=>value pairs.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc product category update 75
	 *
	 *     wp wc product category update 75 --name="new_name" --parent=50 --description="New Category Field" --order=2 --slug=new-cat
	 *
	 * @subcommand update
	 * @since      2.6.0
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
}
