<?php
/**
 * @package WPSEO\Admin\Import\External
 */

/**
 * Class WPSEO_Import_HeadSpace
 *
 * Class with functionality to import Yoast SEO settings from other plugins
 */
class WPSEO_Import_HeadSpace implements WPSEO_External_Importer {
	/**
	 * @var wpdb Holds the WPDB instance.
	 */
	protected $db;

	/**
	 * WPSEO_Import_HeadSpace constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->db = $wpdb;
	}

	/**
	 * Returns the plugin name.
	 *
	 * @return string
	 */
	public function plugin_name() {
		Return 'HeadSpace SEO';
	}

	/**
	 * Import HeadSpace SEO settings.
	 *
	 * @return WPSEO_Import_Status
	 */
	public function import() {
		$status = new WPSEO_Import_Status( 'import', false );

		$affected_rows = $this->db->query( "SELECT COUNT(*) FROM $this->db->postmeta WHERE meta_key LIKE '_headspace_%'" );
		if ( $affected_rows === 0 ) {
			return $status;
		}

		WPSEO_Meta::replace_meta( '_headspace_description', WPSEO_Meta::$meta_prefix . 'metadesc', false );
		WPSEO_Meta::replace_meta( '_headspace_keywords', WPSEO_Meta::$meta_prefix . 'metakeywords', false );
		WPSEO_Meta::replace_meta( '_headspace_page_title', WPSEO_Meta::$meta_prefix . 'title', false );

		/**
		 * @todo [JRF => whomever] verify how headspace sets these metas ( 'noindex', 'nofollow', 'noarchive', 'noodp', 'noydir' )
		 * and if the values saved are concurrent with the ones we use (i.e. 0/1/2)
		 */
		WPSEO_Meta::replace_meta( '_headspace_noindex', WPSEO_Meta::$meta_prefix . 'meta-robots-noindex', false );
		WPSEO_Meta::replace_meta( '_headspace_nofollow', WPSEO_Meta::$meta_prefix . 'meta-robots-nofollow', false );

		/*
		 * @todo - [JRF => whomever] check if this can be done more efficiently by querying only the meta table
		 * possibly directly changing it using concat on the existing values
		 */
		$posts = $this->db->get_results( "SELECT ID FROM $this->db->posts" );
		if ( is_array( $posts ) && $posts !== array() ) {
			foreach ( $posts as $post ) {
				$custom         = get_post_custom( $post->ID );
				$robotsmeta_adv = '';
				if ( isset( $custom['_headspace_noarchive'] ) ) {
					$robotsmeta_adv .= 'noarchive,';
				}
				$robotsmeta_adv = preg_replace( '`,$`', '', $robotsmeta_adv );
				WPSEO_Meta::set_value( 'meta-robots-adv', $robotsmeta_adv, $post->ID );
			}
		}

		$status->set_status( true );
		return $status;
	}

	/**
	 * Removes the HeadSpace data from the database.
	 *
	 * @return WPSEO_Import_Status
	 */
	public function cleanup() {
		$status = new WPSEO_Import_Status( 'cleanup', false );
		$affected_rows = $this->db->query( "DELETE FROM $this->db->postmeta WHERE meta_key LIKE '_headspace_%'" );
		if ( $affected_rows > 0 ) {
			return $status;
		}

		$status->set_status( true );
		return $status;
	}
}
