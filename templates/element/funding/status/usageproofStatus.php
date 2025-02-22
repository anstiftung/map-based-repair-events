<?php
declare(strict_types=1);

echo '<div class="verification-wrapper ' . $funding->usageproof_status_css_class . '">';
    echo '<p>' . $additionalTextBefore . $funding->usageproof_status_human_readable . '</p>';
    if ($funding->usageproof_comment != '') {
        echo '<p class="comment">' . h($funding->usageproof_comment) . '</p>';
    }
echo '</div>';
