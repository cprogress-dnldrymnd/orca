<?php
function _user_has_access()
{
    return sfwd_lms_has_access_fn(get_the_ID(), get_current_user_id());
}

function _learndash_course_progress()
{
    if (_user_has_access()) {

        return do_shortcode('[learndash_course_progress course_id="' . get_the_ID() . '"]');
    }
}

add_shortcode('_learndash_course_progress', '_learndash_course_progress');


function _learndash_course_meta()
{
    $html = '<div class="course-meta">';
    $html .= '<p><strong>Duration:</strong> 2 weeks</p>';
    $html .= '<p><strong>Certification:</strong> ORCA Certified</p>';
    $html .= '</div>';

    return $html;
}
add_shortcode('_learndash_course_meta', '_learndash_course_meta');


function _learndash_status_bubble()
{

    //return var_dump(learndash_user_get_enrolled_courses(get_current_user_id()));
    if (_user_has_access()) {
        $course_status = learndash_course_status(get_the_ID(), get_current_user_id());
        return learndash_status_bubble($course_status);
    } else {
        return '<span class="ld-status ld-status-waiting ld-tertiary-background" data-ld-tooltip="Enroll in this course to get access" data-ld-tooltip-id="52073"> Not Enrolled</span>';
    }
}
add_shortcode('_learndash_status_bubble', '_learndash_status_bubble');


function learndash_wp_footer()
{
    if (get_post_type() == 'sfwd-courses') {
?>
        <script>
            jQuery(document).ready(function() {
                jQuery('.ld-progress-steps').appendTo('#course-progress .learndash-wrapper');
            });
        </script>
<?php
    }
}

add_action('wp_footer', 'learndash_wp_footer');



/**
 * Example usage for learndash_settings_fields filter.
 */
add_filter(
    'learndash_settings_fields',
    function ( $setting_option_fields = array(), $settings_metabox_key = '' ) {
        // Check the metabox includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php line 23 where
        // settings_metabox_key is set. Each metabox or section has a unique settings key.
        if ( 'learndash-course-access-settings' === $settings_metabox_key ) {
 
            // Add field here.
            $post_id           = get_the_ID();
            $my_settings_value = get_post_meta( $post_id, 'my_meta_key', true );
            if ( empty( $my_settings_value ) ) {
                        $my_settings_value = 'default value';
            }
 
            if ( ! isset( $setting_option_fields['my-custom-field'] ) ) {
                $setting_option_fields['my-custom'] = array(
                    'name'      => 'my-custom-field',
                    'label'     => sprintf(
                        // translators: placeholder: Course.
                        esc_html_x( '%s Field Label', 'placeholder: Course', 'learndash' ),
                        learndash_get_custom_label( 'course' )
                    ),
                    // Check the LD fields ligrary under incldues/settings/settings-fields/
                    'type'      => 'text',
                    'class'     => '-medium',
                    'value'     => $my_settings_value,
                    'default'   => '',
                    'help_text' => sprintf(
                        // translators: placeholder: course.
                        esc_html_x( 'Some help text for %s.', 'placeholder: course.', 'learndash' ),
                        learndash_get_custom_label_lower( 'course' )
                    ),
                );
            }
        }
 
        // Always return $setting_option_fields
        return $setting_option_fields;
    },
    30,
    2
);
 
// You have to save your own field. This is no longer handled by LD. This is on purpose.
add_action(
    'save_post',
    function( $post_id = 0, $post = null, $update = false ) {
        // All the metabox fields are in sections. Here we are grabbing the post data
        // within the settings key array where the added the custom field.
        if ( isset( $_POST['learndash-course-access-settings']['my-custom-field'] ) ) {
            $my_settings_value = esc_attr( $_POST['learndash-course-access-settings']['my-custom-field'] );
            // Then update the post meta
            update_post_meta( $post_id, 'my_meta_key', $my_settings_value );
        }
 
    },
    30,
    3
);