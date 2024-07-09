<?php namespace WSUWP\Plugin\ContentVisibility;

class Guest_User {

    private static $username_prefix = 'guest.';
     
    public static function login_as_guest($user, $user_claim) {       
        
        if ( ! isset($user->errors['cannot-authorize']) || ! self::is_redirect_request() ) {
            return $user;
        }        

        $tmp = explode( '@', self::$username_prefix . ($user_claim['preferred_username'] ?? $user_claim['email']));        
        $username = $tmp[0];

        if( ! username_exists( $username ) ) {
            self::create_user($username, $user_claim);
        }

        $user = get_user_by('login', $username);

        return $user;
    
    }


    private static function is_redirect_request(){

        $state = $_GET['state'];
		$state_object = get_transient( 'openid-connect-generic-state--' . $state );
        
		// Get the redirect URL stored with the corresponding authentication request state.
		if ( ! empty( $state_object ) && ! empty( $state_object[ $state ] ) && ! empty( $state_object[ $state ]['redirect_to'] ) ) {
            $redirect_url = $state_object[ $state ]['redirect_to'];
		}

        if( empty($redirect_url) || str_contains( $redirect_url, 'wp-login' ) || str_contains( $redirect_url, 'wp-admin' )){
            return false;
        }

        return true;

    }


    private static function create_user($username, $user_claim){

        $user_data_version = 'r2701';
        $userdata = array(
            'user_login' =>  $username,
            'user_pass'  =>  wp_generate_password( 10, true, true ),
            'first_name' => $user_claim['given_name'] ?? '',
            'last_name' => $user_claim['family_name'] ?? '',
            'show_admin_bar_front' => 'false',
            'rich_editing' => 'false',
            'meta_input' => array(
                '_wsuwp_ad_data' => array(
                    'wsuaffiliation' => 'NA',
                    'memberof' => $user_claim['umc_wp.groups'] ?? array(),
                    'user_type' => 'nid',
                    'version' => $user_data_version,
                    'last_refresh' => time()
                )
            )
        );
        
        wp_insert_user( $userdata );

    }


    public static function add_user_cleanup_cron_interval($schedules) {

        $schedules['three_hours'] = array(
            'interval' => 3 * 60 * 60,
            'display' => esc_html__( 'Every Three Hours' ),
        );
           
        return $schedules;

    }


    public static function schedule_user_cleanup_cron(){

        if ( ! wp_next_scheduled ( 'run_user_cleanup' ) ) {
            wp_schedule_event( time(), 'three_hours', 'run_user_cleanup' );
        }
        
    }


    public static function cleanup_users(){

        $args = array(
            'blog_id' => 0,
            'search'         => 'guest.*',
            'search_columns' => array( 'user_login' ),
            'role' => 'Subscriber',
            'number' => 20,
            'date_query' => array( 
                array( 'before' => '2 hours ago', 'inclusive' => true )  
            ),
            'orderby' => 'user_registered', 
            'order' => 'ASC',
        );
        
        $user_query = new \WP_User_Query( $args );
        $users = $user_query->get_results();        
        
        if ( ! empty( $users ) ) {
            foreach ( $users as $user ) {
                error_log("Deleting guest user $user->user_login");
                wpmu_delete_user( $user->ID );
            }
        }

    }


	public static function init() {
		
        add_action( 'init', __CLASS__ . '::schedule_user_cleanup_cron' );        
        add_action( 'run_user_cleanup', __CLASS__ . '::cleanup_users' );
        add_filter('openid-connect-generic-new-user',  __CLASS__ . '::login_as_guest', 10, 2 );
        add_filter('cron_schedules',  __CLASS__ . '::add_user_cleanup_cron_interval', 10, 1 );        

    }    
}

Guest_User::init();
