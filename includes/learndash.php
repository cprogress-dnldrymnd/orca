<?php
function _learndash_course_progress()
{
    return '[learndash_course_progress course_id="' . get_the_ID() . '"]';
}

add_shortcode('_learndash_course_progress', 'learndash_course_progress');