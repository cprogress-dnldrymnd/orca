<?php
function _learndash_course_progress()
{
    return do_shortcode('[learndash_course_progress course_id="' . get_the_ID() . '"]');
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


function _learndash_status_bubble() {
    $course_status = learndash_course_status(get_the_ID());
    return learndash_status_bubble($course_status);
}
add_shortcode('_learndash_status_bubble', '_learndash_status_bubble');
