<?php
echo '<div style="padding:5px;text-align:right;border-radius:3px;" class="'. ($object->user_fields_verified ? 'is-verified' : 'is-pending') .'">';
    echo $object->user_fields_verified_count . ' / ' . $object->user_fields_count;
echo '</div>';
