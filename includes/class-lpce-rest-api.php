<?php
/**
 * REST API class for certificates
 *
 * @package LearnPress_Certificates_Extension
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LPCE_REST_API {
	
	/**
	 * Single instance
	 *
	 * @var LPCE_REST_API
	 */
	private static $instance = null;
	
	/**
	 * Namespace for REST API
	 *
	 * @var string
	 */
	private $namespace = 'learnpress/v1';
	
	/**
	 * Get single instance
	 *
	 * @return LPCE_REST_API
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}
	
	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		// Get current user's certificates
		register_rest_route(
			$this->namespace,
			'/certificates/my',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_my_certificates' ),
					'permission_callback' => array( $this, 'get_my_certificates_permissions_check' ),
				),
			)
		);
		
		// Get certificate by code (public verification)
		register_rest_route(
			$this->namespace,
			'/certificates/code/(?P<code>[a-zA-Z0-9]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_certificate_by_code' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'code' => array(
							'description' => __( 'Certificate verification code.', 'learnpress-certificates-extension' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
			)
		);
		
		// Get current user's subscription status
		register_rest_route(
			$this->namespace,
			'/subscriptions/my',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_my_subscription_status' ),
					'permission_callback' => array( $this, 'get_my_subscription_permissions_check' ),
				),
			)
		);
		
		// Get subscription status for a specific user
		register_rest_route(
			$this->namespace,
			'/subscriptions/user/(?P<user_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_user_subscription_status' ),
					'permission_callback' => array( $this, 'get_user_subscription_permissions_check' ),
					'args'                => array(
						'user_id' => array(
							'description' => __( 'User ID to check subscription status.', 'learnpress-certificates-extension' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
			)
		);
		
		// Get all subscription plans/packages
		register_rest_route(
			$this->namespace,
			'/subscriptions/plans',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_all_subscription_plans' ),
					'permission_callback' => array( $this, 'get_subscription_plans_permissions_check' ),
					'args'                => array(
						'only_active' => array(
							'description' => __( 'Return only active plans.', 'learnpress-certificates-extension' ),
							'type'        => 'boolean',
							'default'     => false,
						),
						'include' => array(
							'description' => __( 'Include specific plan IDs.', 'learnpress-certificates-extension' ),
							'type'        => 'array',
							'items'       => array( 'type' => 'integer' ),
							'default'     => array(),
						),
						'exclude' => array(
							'description' => __( 'Exclude specific plan IDs.', 'learnpress-certificates-extension' ),
							'type'        => 'array',
							'items'       => array( 'type' => 'integer' ),
							'default'     => array(),
						),
					),
				),
			)
		);
	}
	
	/**
	 * Check permissions for getting current user's certificates
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return bool|WP_Error
	 */
	public function get_my_certificates_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_cannot_access',
				__( 'Sorry, you must be logged in to view your certificates.', 'learnpress-certificates-extension' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		
		return true;
	}
	
	/**
	 * Get current user's certificates
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_my_certificates( $request ) {
		$user_id = get_current_user_id();
		
		if ( ! $user_id ) {
			return new WP_Error(
				'rest_cannot_access',
				__( 'User not found.', 'learnpress-certificates-extension' ),
				array( 'status' => 404 )
			);
		}
		
		$certificates = LPCE_Database::instance()->get_user_certificates( $user_id );
		
		$formatted_certificates = array();
		foreach ( $certificates as $certificate ) {
			$formatted_certificates[] = $this->format_certificate_response( $certificate, $request );
		}
		
		return rest_ensure_response( $formatted_certificates );
	}
	
	/**
	 * Get certificate by verification code (public)
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_certificate_by_code( $request ) {
		$code = sanitize_text_field( $request['code'] );
		
		if ( empty( $code ) ) {
			return new WP_Error(
				'rest_certificate_invalid',
				__( 'Certificate code is required.', 'learnpress-certificates-extension' ),
				array( 'status' => 400 )
			);
		}
		
		$certificate = LPCE_Database::instance()->get_certificate_by_code( $code );
		
		if ( ! $certificate ) {
			return new WP_Error(
				'rest_certificate_invalid',
				__( 'Invalid certificate code.', 'learnpress-certificates-extension' ),
				array( 'status' => 404 )
			);
		}
		
		$response = $this->format_certificate_response( $certificate, $request, true );
		return rest_ensure_response( $response );
	}
	
	/**
	 * Format certificate response
	 *
	 * @param object $certificate Certificate object
	 * @param WP_REST_Request $request Request object
	 * @param bool $public Whether this is a public verification
	 * @return array
	 */
	private function format_certificate_response( $certificate, $request, $public = false ) {
		$user = get_userdata( $certificate->user_id );
		$course = get_post( $certificate->course_id );
		
		$response = array(
			'id'               => (int) $certificate->id,
			'certificate_code' => $certificate->certificate_code,
			'file_url'         => $certificate->file_url,
			'status'           => $certificate->status,
		);
		
		// Add user information
		if ( $user ) {
			$response['user'] = array(
				'id'    => $user->ID,
				'name'  => $user->display_name,
				'email' => $public ? '' : $user->user_email, // Hide email in public verification
			);
		}
		
		// Add course information
		if ( $course ) {
			$response['course'] = array(
				'id'    => $course->ID,
				'title' => $course->post_title,
				'slug'  => $course->post_name,
				'url'   => get_permalink( $course->ID ),
			);
		}
		
		return $response;
	}
	
	/**
	 * Check permissions for getting current user's subscription status
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return bool|WP_Error
	 */
	public function get_my_subscription_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_cannot_access',
				__( 'Sorry, you must be logged in to view your subscription status.', 'learnpress-certificates-extension' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		
		return true;
	}
	
	/**
	 * Check permissions for getting another user's subscription status
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return bool|WP_Error
	 */
	public function get_user_subscription_permissions_check( $request ) {
		// Only allow users to check their own subscription or admins to check any user
		$user_id = isset( $request['user_id'] ) ? (int) $request['user_id'] : 0;
		$current_user_id = get_current_user_id();
		
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_cannot_access',
				__( 'Sorry, you must be logged in to view subscription status.', 'learnpress-certificates-extension' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		
		// Allow if checking own subscription or if user is admin
		if ( $user_id !== $current_user_id && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_cannot_access',
				__( 'Sorry, you do not have permission to view this user\'s subscription status.', 'learnpress-certificates-extension' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		
		return true;
	}
	
	/**
	 * Get current user's subscription status
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_my_subscription_status( $request ) {
		$user_id = get_current_user_id();
		
		if ( ! $user_id ) {
			return new WP_Error(
				'rest_cannot_access',
				__( 'User not found.', 'learnpress-certificates-extension' ),
				array( 'status' => 404 )
			);
		}
		
		return $this->get_subscription_status_for_user( $user_id );
	}
	
	/**
	 * Get subscription status for a specific user
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_user_subscription_status( $request ) {
		$user_id = isset( $request['user_id'] ) ? (int) $request['user_id'] : 0;
		
		if ( ! $user_id ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Invalid user ID.', 'learnpress-certificates-extension' ),
				array( 'status' => 400 )
			);
		}
		
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_Error(
				'rest_user_invalid',
				__( 'User not found.', 'learnpress-certificates-extension' ),
				array( 'status' => 404 )
			);
		}
		
		return $this->get_subscription_status_for_user( $user_id );
	}
	
	/**
	 * Get subscription status for a user
	 *
	 * @param int $user_id User ID
	 * @return WP_REST_Response|WP_Error
	 */
	private function get_subscription_status_for_user( $user_id ) {
		// Check if Paid Member Subscriptions is active
		if ( ! function_exists( 'pms_get_member_subscriptions' ) ) {
			return new WP_Error(
				'rest_pms_not_active',
				__( 'Paid Member Subscriptions plugin is not active.', 'learnpress-certificates-extension' ),
				array( 'status' => 503 )
			);
		}
		
		// Get user subscriptions
		$subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user_id ) );
		
		$formatted_subscriptions = array();
		$has_active_subscription = false;
		
		if ( ! empty( $subscriptions ) ) {
			// Handle both arrays and objects
			foreach ( $subscriptions as $subscription ) {
				// Convert object to array if needed
				if ( is_object( $subscription ) ) {
					$subscription = (array) $subscription;
				}
				
				// Skip if not an array
				if ( ! is_array( $subscription ) ) {
					continue;
				}
				
				$subscription_data = array(
					'id'                => isset( $subscription['id'] ) ? (int) $subscription['id'] : 0,
					'subscription_plan_id' => isset( $subscription['subscription_plan_id'] ) ? (int) $subscription['subscription_plan_id'] : 0,
					'status'            => isset( $subscription['status'] ) ? $subscription['status'] : '',
					'start_date'        => isset( $subscription['start_date'] ) ? $subscription['start_date'] : '',
					'expiration_date'   => isset( $subscription['expiration_date'] ) ? $subscription['expiration_date'] : '',
					'auto_renew'        => isset( $subscription['auto_renew'] ) ? (bool) $subscription['auto_renew'] : false,
				);
				
				// Get plan details if available
				if ( function_exists( 'pms_get_subscription_plan' ) && ! empty( $subscription_data['subscription_plan_id'] ) ) {
					$plan = pms_get_subscription_plan( $subscription_data['subscription_plan_id'] );
					if ( $plan ) {
						$subscription_data['plan'] = array(
							'id'          => isset( $plan->id ) ? $plan->id : 0,
							'name'        => isset( $plan->name ) ? $plan->name : '',
							'description' => isset( $plan->description ) ? $plan->description : '',
							'price'       => isset( $plan->price ) ? $plan->price : '',
							'duration'    => isset( $plan->duration ) ? $plan->duration : 0,
						);
					}
				}
				
				$formatted_subscriptions[] = $subscription_data;
				
				// Check if subscription is active
				if ( isset( $subscription['status'] ) && in_array( $subscription['status'], array( 'active', 'trial' ), true ) ) {
					$has_active_subscription = true;
				}
			}
		}
		
		// Check if user is member of any plan (using PMS function if available)
		$is_member = false;
		if ( function_exists( 'pms_is_member_of_plan' ) ) {
			// Get all plan IDs to check
			$all_plan_ids = array();
			foreach ( $formatted_subscriptions as $sub ) {
				if ( ! empty( $sub['subscription_plan_id'] ) ) {
					$all_plan_ids[] = $sub['subscription_plan_id'];
				}
			}
			if ( ! empty( $all_plan_ids ) ) {
				$is_member = pms_is_member_of_plan( $all_plan_ids, $user_id );
			}
		} else {
			// Fallback: check if has active subscription
			$is_member = $has_active_subscription;
		}
		
		$response = array(
			'user_id'              => (int) $user_id,
			'has_subscription'     => ! empty( $formatted_subscriptions ),
			'has_active_subscription' => $has_active_subscription,
			'is_member'            => $is_member,
			'subscriptions'        => $formatted_subscriptions,
			'subscription_count'   => count( $formatted_subscriptions ),
		);
		
		return rest_ensure_response( $response );
	}
	
	/**
	 * Check permissions for getting subscription plans
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return bool|WP_Error
	 */
	public function get_subscription_plans_permissions_check( $request ) {
		// Allow public access to view subscription plans (they're typically public information)
		// You can change this to require authentication if needed
		return true;
	}
	
	/**
	 * Get all subscription plans/packages
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_all_subscription_plans( $request ) {
		// Check if Paid Member Subscriptions is active
		if ( ! function_exists( 'pms_get_subscription_plans' ) ) {
			return new WP_Error(
				'rest_pms_not_active',
				__( 'Paid Member Subscriptions plugin is not active.', 'learnpress-certificates-extension' ),
				array( 'status' => 503 )
			);
		}
		
		// Get parameters
		$only_active = isset( $request['only_active'] ) ? (bool) $request['only_active'] : false;
		$include = isset( $request['include'] ) && is_array( $request['include'] ) ? array_map( 'intval', $request['include'] ) : array();
		$exclude = isset( $request['exclude'] ) && is_array( $request['exclude'] ) ? array_map( 'intval', $request['exclude'] ) : array();
		
		// Get subscription plans using official PMS function signature
		// Reference: https://www.cozmoslabs.com/docs/paid-member-subscriptions/developer-knowledge-base/useful-functions/
		$plans = pms_get_subscription_plans( $only_active, $include, $exclude );
		
		$formatted_plans = array();
		
		if ( ! empty( $plans ) && is_array( $plans ) ) {
			foreach ( $plans as $plan ) {
				// Handle both objects and arrays
				if ( is_object( $plan ) ) {
					// Extract all available fields from PMS_Subscription_Plan object
					// Reference: https://www.cozmoslabs.com/docs/paid-member-subscriptions/developer-knowledge-base/useful-functions/
					$plan_data = array(
						'id'                    => isset( $plan->id ) ? (int) $plan->id : 0,
						'name'                  => isset( $plan->name ) ? $plan->name : '',
						'description'           => isset( $plan->description ) ? $plan->description : '',
						'price'                 => isset( $plan->price ) ? $plan->price : '',
						'status'                => isset( $plan->status ) ? $plan->status : '',
						'duration'              => isset( $plan->duration ) ? (int) $plan->duration : 0,
						'duration_unit'         => isset( $plan->duration_unit ) ? $plan->duration_unit : '',
						'user_role'             => isset( $plan->user_role ) ? $plan->user_role : '',
						'top_parent'            => isset( $plan->top_parent ) ? (int) $plan->top_parent : 0,
						'sign_up_fee'           => isset( $plan->sign_up_fee ) ? $plan->sign_up_fee : '',
						'trial_duration'        => isset( $plan->trial_duration ) ? (int) $plan->trial_duration : 0,
						'trial_duration_unit'   => isset( $plan->trial_duration_unit ) ? $plan->trial_duration_unit : '',
						'recurring'             => isset( $plan->recurring ) ? (bool) $plan->recurring : false,
						'type'                  => isset( $plan->type ) ? $plan->type : '',
						'fixed_membership'      => isset( $plan->fixed_membership ) ? (bool) $plan->fixed_membership : false,
						'fixed_expiration_date' => isset( $plan->fixed_expiration_date ) ? $plan->fixed_expiration_date : '',
						'allow_renew'           => isset( $plan->allow_renew ) ? (bool) $plan->allow_renew : false,
					);
				} elseif ( is_array( $plan ) ) {
					$plan_data = array(
						'id'                    => isset( $plan['id'] ) ? (int) $plan['id'] : 0,
						'name'                  => isset( $plan['name'] ) ? $plan['name'] : '',
						'description'           => isset( $plan['description'] ) ? $plan['description'] : '',
						'price'                 => isset( $plan['price'] ) ? $plan['price'] : '',
						'status'                => isset( $plan['status'] ) ? $plan['status'] : '',
						'duration'              => isset( $plan['duration'] ) ? (int) $plan['duration'] : 0,
						'duration_unit'         => isset( $plan['duration_unit'] ) ? $plan['duration_unit'] : '',
						'user_role'             => isset( $plan['user_role'] ) ? $plan['user_role'] : '',
						'top_parent'            => isset( $plan['top_parent'] ) ? (int) $plan['top_parent'] : 0,
						'sign_up_fee'           => isset( $plan['sign_up_fee'] ) ? $plan['sign_up_fee'] : '',
						'trial_duration'        => isset( $plan['trial_duration'] ) ? (int) $plan['trial_duration'] : 0,
						'trial_duration_unit'   => isset( $plan['trial_duration_unit'] ) ? $plan['trial_duration_unit'] : '',
						'recurring'             => isset( $plan['recurring'] ) ? (bool) $plan['recurring'] : false,
						'type'                  => isset( $plan['type'] ) ? $plan['type'] : '',
						'fixed_membership'      => isset( $plan['fixed_membership'] ) ? (bool) $plan['fixed_membership'] : false,
						'fixed_expiration_date' => isset( $plan['fixed_expiration_date'] ) ? $plan['fixed_expiration_date'] : '',
						'allow_renew'           => isset( $plan['allow_renew'] ) ? (bool) $plan['allow_renew'] : false,
					);
				} else {
					continue;
				}
				
				$formatted_plans[] = $plan_data;
			}
		}
		
		$response = array(
			'plans'       => $formatted_plans,
			'total_plans' => count( $formatted_plans ),
		);
		
		return rest_ensure_response( $response );
	}
}
