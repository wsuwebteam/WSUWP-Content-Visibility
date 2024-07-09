<?php namespace WSUWP\Plugin\ContentVisibility;

class Content_Visibility_Settings {

	private static $page_slug = 'content_visibility_settings';

	private static $option_title = 'content_visibility_settings';

    private static $default_groups = array(
        array( 
            'id' => 'wsu-nid', 
            'name' => 'WSU Network ID (NID)'
        ),
        array( 
            'id' => 'wsu-student', 
            'name' => 'WSU Students',
            'ad_groups' => array(
                'Students.Enrolled.Undergraduate',
                'Students.Enrolled.Graduate',
                'Students.Enrolled.IALC',
                'Students.Enrolled.Professional',
                'Students.Enrolled.NonDegreeSeeking',
                'Students.Enrolled.NonWSU',
            )
        ),
        array( 
            'id' => 'wsu-student-global-campus', 
            'name' => 'WSU Global Campus Students',
            'ad_groups' => array(
                'Global.Students',
                'Global.Students.Admitted',
                'Global.Students.Admitted.Graduate',
                'Global.Students.Admitted.NonDegreeSeeking',
                'Global.Students.Admitted.Professional',
                'Global.Students.Admitted.Undergraduate',
                'Global.Students.Applied',
                'Global.Students.Applied.Graduate',
                'Global.Students.Applied.NonDegreeSeeking',
                'Global.Students.Applied.Professional',
                'Global.Students.Applied.Undergraduate',
                'Global.Students.Enrolled',
                'Global.Students.Enrolled.Graduate',
                'Global.Students.Enrolled.NonDegreeSeeking',
                'Global.Students.Enrolled.Professional',
                'Global.Students.Enrolled.Undergraduate',
                'Global.Students.Matriculated',
                'Global.Students.Matriculated.Graduate',
                'Global.Students.Matriculated.NonDegreeSeeking',
                'Global.Students.Matriculated.Professional',
                'Global.Students.Matriculated.Undergraduate',
            )
        ),
        array( 
            'id' => 'wsu-employee', 
            'name' => 'WSU Employees',
            'ad_groups' => array(
                'Employees.Active.Courtesy',
                'Employees.Active.Assistantship',
                'Employees.Active.AdministrativeProfessional',
                'Employees.Active.Faculty',
                'Employees.Active.Hourly',
                'Employees.Active.CivilService',
            )
        ),
        array( 
            'id' => 'wsu-faculty', 
            'name' => 'WSU Faculty',
            'ad_groups' => array(
                'Employees.Active.Faculty',
            )
        ),
    );


    public static function add_settings_page() {

		add_options_page(
			'Content Visibility Settings',
			'Content Visibility Settings',
			'manage_options',
			self::$page_slug,
			__CLASS__ . '::content_visibility_page_content'
		);

	}


	public static function enqueue_assets( $hook ) {

		if ( 'settings_page_content_visibility_settings' === $hook ) {
			wp_enqueue_script( 'wsuwp-plugin-content-visibility-settings-page-scripts' );
			wp_enqueue_style( 'wsuwp-plugin-content-visibility-settings-page-styles' );
		}

	}    


    private static function update_settings( $post_data ) {

		update_option( self::$option_title, $post_data, false );

	}


    public static function content_visibility_page_content(){

        // check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
            return; 
		}

        if ( isset( $_POST['save_settings'] ) && check_admin_referer( self::$page_slug . '_nonce' ) && isset( $_POST['content_visibility_settings'] ) ) {
			self::update_settings( $_POST['content_visibility_settings'] );
			echo '<div class="notice notice-success"><p>Changes Saved</p></div>';
		}

		$content_visibility_settings = get_option( self::$option_title, array() );
        ?>
		<div class="wrap wsuwp-content-visibility-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <form method="post" action="options-general.php?page=<?php echo esc_attr( self::$page_slug ); ?>">
            <?php
                submit_button( 'Save Settings' );
                wp_nonce_field( self::$page_slug . '_nonce' );
                echo '<input type="hidden" value="true" name="save_settings" />';
            
                echo '<h2>Enabled Groups</h2>';
                foreach (self::$default_groups as $group) {
                    $group_checked = empty( $content_visibility_settings ) || isset( $content_visibility_settings[$group['id']]['enabled'] ) ? 'checked' : '';
                    echo '<div class="wsuwp-content-visibility-settings__group">';
                        echo '<ul class="wsuwp-content-visibility-settings__group-list">';
                            echo '<li class="wsuwp-content-visibility-settings__group-list-item">';
                                echo '<label><input ' . $group_checked . ' class="wsuwp-content-visibility-settings__group-input" type="checkbox" name="content_visibility_settings['.$group['id'].'][enabled]" value="true" />' . $group['name'] . '</label>';
                                if( isset($group['ad_groups']) ){
                                    $disabled = $group_checked === '' ? ' data-readonly' : '';
                                    echo '<ul class="wsuwp-content-visibility-settings__sub-group-list"' . $disabled . '>';
                                    foreach ( $group['ad_groups'] as $ad_group ) {
                                        $ad_group_checked = empty( $content_visibility_settings ) || 
                                            ( ! empty( $content_visibility_settings[$group['id']]['ad_groups' ]) 
                                            && in_array( $ad_group, $content_visibility_settings[$group['id']]['ad_groups'] ) ) ? 'checked' : '';
                                        echo '<li class="wsuwp-content-visibility-settings__sub-group-list-item">';
                                            echo '<label><input ' . $ad_group_checked . ' class="wsuwp-content-visibility-settings__ad-group-input" type="checkbox" name="content_visibility_settings[' . $group['id'] . '][ad_groups][]" value="' . $ad_group . '" />' . $ad_group . '</label>';
                                        echo '</li>';
                                    }
                                    echo '</ul>';
                                }
                            echo '</li>';                          
                        echo '</ul>';
                    echo '</div>';
                }          
            
                submit_button( 'Save Settings' );
            ?>
			</form>
		</div>
		<?php
	}
    


    public static function set_default_content_visibility_groups( $current_groups ){
        
        $content_visibility_settings = get_option( self::$option_title, array() );

        if( ! empty( $content_visibility_settings) ) {
            $enabled_groups = array();

            foreach (self::$default_groups as $group) {
                if( isset($content_visibility_settings[$group['id']]['enabled']) ) {
                    $enabled_group = array_merge($group);

                    if( ! empty( $group['ad_groups']) && ! empty( $content_visibility_settings[$group['id']]['ad_groups'] )  ) {
                        $enabled_group['ad_groups'] =  array_intersect( $group['ad_groups'], $content_visibility_settings[$group['id']]['ad_groups'] );
                    }

                    array_push( $enabled_groups, $enabled_group );
                }
            }

            return array_merge( $current_groups, $enabled_groups );
        }

        return array_merge( $current_groups, self::$default_groups );

    }


    public static function process_content_visibility_permissions( $allowed, $user_id, $allowed_groups ) {
        
        $user_ad_data = get_user_meta( $user_id, '_wsuwp_ad_data', true );
        $users_groups = $user_ad_data['memberof'];        

        if( is_array( $user_ad_data ) && isset( $user_ad_data['user_type'] ) && $user_ad_data['user_type'] === 'nid' ) {
            
            // WSU NIDs are allowed and this user has a WSU NID.
            if ( in_array( 'wsu-nid', $allowed_groups, true ) ) {
                return true;
            }
            
            // Return current state of $allowed if user does not have any groups compare
            if( empty($users_groups) ) {
                return $allowed;
            }
            
            // Return true if the user belongs to any of the allowed AD groups
            $default_group_ids = array_column( self::$default_groups, 'id' );
            $content_visibility_settings = get_option( self::$option_title, array() );           
            
            foreach ($allowed_groups as $group_id) {

                $ad_groups = array();             
                
                if( isset($content_visibility_settings) ){ 
                    $is_group_enabled = isset( $content_visibility_settings[$group_id]['enabled'] );
                    $has_ad_groups = ! empty( $content_visibility_settings[$group_id]['ad_groups'] );

                    if( $is_group_enabled && $has_ad_groups ){
                        $ad_groups = $content_visibility_settings[$group_id]['ad_groups'];
                    }
                }else{                    
                    // fallback implementatiton if CV groups has not been configured
                    $found_key = array_search( $group_id, $default_group_ids);

                    if( $found_key && isset( self::$default_groups[$found_key]['ad_groups'] ) ) {
                        $ad_groups = self::$default_groups[$found_key]['ad_groups'];
                    }        
                }

                $intersected_groups = array_intersect( $users_groups, $ad_groups );                        
                
                if( ! empty ($intersected_groups ) ) {
                    return true;
                }

            }

        }

        return $allowed;

    }


    public static function init(){

        add_action( 'admin_menu', __CLASS__ . '::add_settings_page' );
        add_action( 'admin_enqueue_scripts', __CLASS__ . '::enqueue_assets', 10, 1 );
        add_filter( 'content_visibility_default_groups', array( __CLASS__, 'set_default_content_visibility_groups' ), 1, 1 );
        add_filter( 'user_in_content_visibility_groups', array( __CLASS__, 'process_content_visibility_permissions' ), 10, 3 );

    }


}

Content_Visibility_Settings::init();
