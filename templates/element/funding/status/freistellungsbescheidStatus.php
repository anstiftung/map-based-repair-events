<?php
declare(strict_types=1);

echo '<div class="verification-wrapper ' . $funding->freistellungsbescheid_status_css_class . '">';
    echo '<p>' . $funding->freistellungsbescheid_status_human_readable . '</p>';
    if ($funding->freistellungsbescheid_comment != '') {
        echo '<p class="comment">' . h($funding->freistellungsbescheid_comment) . '</p>';
    }
echo '</div>';
