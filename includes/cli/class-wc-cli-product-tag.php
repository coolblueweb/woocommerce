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
	 * Get product tag.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Product tag ID.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole product category fields, returns the value of a single fields.
	 *
	 * [--fields=<fields>]
	 * : Get a specific subset of the product category's fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * * id
	 * * name
	 * * slug
	 * * description
	 * * count
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc product tag get 123
	 *
	 * @since 2.5.0
	 */
	public function get( $args, $assoc_args ) {
		try {
			$product_category = $this->get_product_tag( $args[0] );

			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_item( $product_category );
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}


	/**
	 * List of product tags.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter products based on product property.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each product.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific product fields.
	 *
	 * [--format=<format>]
	 * : Acceptec values: table, csv, json, count, ids. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * * id
	 * * name
	 * * slug
	 * * description
	 * * count
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc product tag list
	 *
	 *     wp wc product tag list --fields=id,name --format=json
	 *
	 * @subcommand list
	 * @since      2.5.0
	 */
	public function list_( $__, $assoc_args ) {
		try {
			$product_categories = array();
			$terms              = get_terms( 'product_tag', array( 'hide_empty' => false, 'fields' => 'ids' ) );

			foreach ( $terms as $term_id ) {
				$product_categories[] = $this->get_product_tag( $term_id );
			}

			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_items( $product_categories );

		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

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
			// Ensure the product tag (taxonomy term) exists before starting.
			$this->assert_term_exists( $args[0] );

			// Load the product tag (taxonomy term)
			$term = get_term( $args[0], 'product_tag', ARRAY_A );
			
			// Merge the CLI arguments with the original product tag arguments
			$this->assert_no_wp_error( $term );
			$updated_term_values = array_merge( $term, $assoc_args );
			$this->assert_no_wp_error( $term );

			// Update the product tag
			$updated_term = wp_update_term( $term[ 'term_id' ], 'product_tag', $updated_term_values );
			$this->assert_no_wp_error( $updated_term );

			// Filter out the core term values, then use remaining values to update metafields
			$this->filter_insert_keys_from_assoc_args( $assoc_args );
			
			// Update metadata
			foreach( $assoc_args as $meta_key => $meta_value ) {
				update_term_meta( $updated_term_values[ 'term_id' ], $meta_key, $meta_value );
			}

			// Reload the product tag (taxonomy term) to ensure that we're reporting with persistent data
			$term = get_term( $args[0], 'product_tag', ARRAY_A );

			WP_CLI::success( sprintf( __('Product Tag "%s" was updated successfully.', 'woocommerce' ),
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
		$term = get_term( $term_id, 'product_tag', ARRAY_A );

		if ( ! $term ) {

			throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product_tag_id',
				sprintf( __( 'Invalid product tag ID "%s"', 'woocommerce' ), $term_id ) );
		}
	}

	/**
	 * Pass in the assoc_args array to filter out the key->value pairs which are applied directly to the new term.
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
	
	/**
	 * Get product tag properties from given term ID.
	 *
	 * @since  2.5.0
	 * @param  int $term_id Product tag term ID
	 * @return array
	 * @throws WC_CLI_Exception
	 */
	protected function get_product_tag( $term_id ) {
		$term_id = absint( $term_id );
		$term    = get_term( $term_id, 'product_tag' );

		if ( is_wp_error( $term ) || is_null( $term ) ) {
			throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product_tag_id', sprintf( __( 'Invalid product tag ID "%s"', 'woocommerce' ), $term_id ) );
		}

		$term_id = intval( $term->term_id );

		return array(
			'id'          => $term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'count'       => intval( $term->count ),
			'description' => $term->description
		);
	}

	/**
	 * Get default format fields that will be used in `list` and `get` subcommands.
	 *
	 * @since  2.5.0
	 * @return string
	 */
	protected function get_default_format_fields() {
		return 'id,name,slug,description,count';
	}
}
