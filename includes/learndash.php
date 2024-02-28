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


function _learndash_linked_product()
{
    ob_start();
    $args = array(
        'post_type'  => 'product',
        'meta_query' => array(
            array(
                'key'   => '_related_course',
                'value' => serialize(intval(get_the_ID())),
                'compare' => 'LIKE'
            )
        )
    );
    $postslist = get_posts($args);


    ?>
    <pre>
        <?php var_dump($postslist) ?>
        <?php var_dump(get_post_meta(498)) ?>
    </pre>
<?php return ob_get_clean();
}

add_shortcode('_learndash_linked_product', '_learndash_linked_product');
