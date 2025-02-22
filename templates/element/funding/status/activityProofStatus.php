<?php
declare(strict_types=1);

echo '<div class="verification-wrapper ' . $funding->activity_proof_status_css_class . '">';
    echo '<p>' . $funding->activity_proof_status_human_readable . '</p>';
    if ($funding->activity_proof_comment != '') {
        echo '<p class="comment">' . h($funding->activity_proof_comment) . '</p>';
    }
echo '</div>';
