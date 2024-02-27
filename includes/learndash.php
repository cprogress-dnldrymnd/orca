<?php
function _learndash_course_progress()
{
    return do_shortcode('[learndash_course_progress course_id="' . get_the_ID() . '"]');
}

add_shortcode('_learndash_course_progress', '_learndash_course_progress');
