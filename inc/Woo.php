<?php
namespace WC_Fabric_Mockups;

use WC_Product_Variable;
use WC_Product_Variation;

class Woo {
    const ATTRIBUTE_SLUG = 'tkanina';
    const ATTRIBUTE_NAME = 'Tkanina';

    public static function init() {}

    /**
     * Create or update variation with generated images
     */
    public static function create_variation( $product_id, $fabric_name, $image_ids ) {
        $product = wc_get_product( $product_id );
        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            return;
        }

        self::ensure_attribute( $product, $fabric_name );

        $variation = new WC_Product_Variation();
        $variation->set_parent_id( $product_id );
        $variation->set_attributes( [ self::ATTRIBUTE_SLUG => sanitize_title( $fabric_name ) ] );
        $variation->set_image_id( $image_ids[0] );
        if ( count( $image_ids ) > 1 ) {
            $variation->set_gallery_image_ids( array_slice( $image_ids, 1 ) );
        }
        $variation->save();

        Logger::info( sprintf( 'Variation created for product %d fabric "%s"', $product_id, $fabric_name ) );
    }

    protected static function ensure_attribute( WC_Product_Variable $product, $fabric_name ) {
        Logger::info( 'Ensuring attribute for product ' . $product->get_id() );
        $attributes = $product->get_attributes();
        if ( ! isset( $attributes[ self::ATTRIBUTE_SLUG ] ) ) {
            $taxonomy = wc_sanitize_taxonomy_name( self::ATTRIBUTE_SLUG );
            if ( ! taxonomy_exists( $taxonomy ) ) {
                register_taxonomy( $taxonomy, [ 'product' ], [
                    'hierarchical' => false,
                    'label'        => self::ATTRIBUTE_NAME,
                    'query_var'    => true,
                ] );
            }
            $attribute = new \WC_Product_Attribute();
            $attribute->set_name( $taxonomy );
            $attribute->set_options( [] );
            $attribute->set_visible( true );
            $attribute->set_variation( true );
            $attributes[ self::ATTRIBUTE_SLUG ] = $attribute;
            $product->set_attributes( $attributes );
            $product->save();
        }

        if ( ! term_exists( $fabric_name, self::ATTRIBUTE_SLUG ) ) {
            wp_insert_term( $fabric_name, self::ATTRIBUTE_SLUG );
        }

        // Ensure term assigned to product
        $terms = wp_get_object_terms( $product->get_id(), self::ATTRIBUTE_SLUG, [ 'fields' => 'ids' ] );
        $term = get_term_by( 'name', $fabric_name, self::ATTRIBUTE_SLUG );
        if ( $term && ! in_array( $term->term_id, $terms, true ) ) {
            wp_set_object_terms( $product->get_id(), $term->term_id, self::ATTRIBUTE_SLUG, true );
        }
        Logger::info( 'Attribute ensured for product ' . $product->get_id() );
    }
}
