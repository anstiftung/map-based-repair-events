<?php

use App\Model\Entity\Funding;

if (!$workshop->funding_activity_proof_necessary) {
    return;
}
$classes = [];
$text = 'Aktivitätsnachweis: ';
if (!empty($workshop->funding)) {
    $classes[] = $workshop->funding->activity_proof_status_css_class;
    $text .= $workshop->funding->activity_proof_status_human_readable;
} else {
    $classes = ['is-pending'];
    $text .= Funding::STATUS_MAPPING[Funding::STATUS_PENDING];
}
echo '<div style="padding:10px;margin-top:10px;border-radius:3px;" class="' . implode(' ', $classes) . '">';
    echo $text;
echo '</div>';
