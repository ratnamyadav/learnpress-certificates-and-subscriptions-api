<?php
/**
 * Database handler class
 *
 * @package LearnPress_Certificates_Extension
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LPCE_Database {
	
	/**
	 * Single instance
	 *
	 * @var LPCE_Database
	 */
	private static $instance = null;
	
	/**
	 * Prefix for certificate options
	 *
	 * @var string
	 */
	private $certificate_option_prefix = 'user_cert_';
	
	/**
	 * Get single instance
	 *
	 * @return LPCE_Database
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor
	 */
	private function __construct() {
		// Constructor
	}
	
	/**
	 * Get certificate option prefix
	 *
	 * @return string
	 */
	private function get_certificate_option_prefix() {
		return $this->certificate_option_prefix;
	}
	
	/**
	 * Get user certificates from options table
	 *
	 * @param int $user_id User ID
	 * @return array Array of certificate objects
	 */
	public function get_user_certificates( $user_id ) {
		global $wpdb;
		
		if ( ! $user_id ) {
			return array();
		}
		
		$prefix = $this->get_certificate_option_prefix();
		
		// Get all certificate options for this user
		$options = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_id, option_name, option_value 
				FROM {$wpdb->options} 
				WHERE option_name LIKE %s 
				AND option_value LIKE %s
				ORDER BY option_id DESC",
				$wpdb->esc_like( $prefix ) . '%',
				'%' . sprintf( 's:7:"user_id";i:%d;', absint( $user_id ) ) . '%'
			)
		);
		
		$certificates = array();
		foreach ( $options as $option ) {
			$certificate = $this->format_certificate_from_option( $option );
			if ( $certificate ) {
				$certificates[] = $certificate;
			}
		}
		
		return $certificates;
	}
	
	/**
	 * Get certificate by code (hash)
	 *
	 * @param string $code Certificate code/hash
	 * @return object|null Certificate object or null
	 */
	public function get_certificate_by_code( $code ) {
		global $wpdb;
		
		if ( empty( $code ) ) {
			return null;
		}
		
		$option_name = $this->sanitize_certificate_option_name( $code );
		
		$option = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT option_id, option_name, option_value 
				FROM {$wpdb->options} 
				WHERE option_name = %s 
				LIMIT 1",
				$option_name
			)
		);
		
		if ( ! $option ) {
			return null;
		}
		
		return $this->format_certificate_from_option( $option );
	}
	
	/**
	 * Format certificate data from options table row
	 *
	 * @param object $option_row Option row from database
	 * @return object|null Certificate object or null
	 */
	private function format_certificate_from_option( $option_row ) {
		if ( empty( $option_row ) ) {
			return null;
		}
		
		$data = maybe_unserialize( $option_row->option_value );
		if ( empty( $data ) || ! is_array( $data ) ) {
			return null;
		}
		
		// Extract certificate code (hash) from option name
		$prefix = $this->get_certificate_option_prefix();
		$code = str_replace( $prefix, '', $option_row->option_name );
		
		// Build certificate file URL
		$upload_dir = wp_upload_dir();
		$base_url = isset( $upload_dir['baseurl'] ) ? trailingslashit( $upload_dir['baseurl'] ) : '';
		$file_url = $base_url ? $base_url . 'learn-press-cert/' . $code . '.png' : '';
		
		return (object) array(
			'id'               => isset( $data['cert_id'] ) ? (int) $data['cert_id'] : (int) $option_row->option_id,
			'user_id'          => isset( $data['user_id'] ) ? (int) $data['user_id'] : 0,
			'course_id'        => isset( $data['course_id'] ) ? (int) $data['course_id'] : 0,
			'certificate_code' => $code,
			'issued_date'      => '',
			'file_url'         => $file_url,
			'status'           => 'active',
		);
	}
	
	/**
	 * Sanitize certificate option name
	 *
	 * @param string $code Certificate code/hash
	 * @return string
	 */
	private function sanitize_certificate_option_name( $code ) {
		$code = sanitize_text_field( $code );
		$prefix = $this->get_certificate_option_prefix();
		
		// If code already has prefix, return as is
		if ( strpos( $code, $prefix ) === 0 ) {
			return $code;
		}
		
		// Otherwise, add prefix
		return $prefix . $code;
	}
}
