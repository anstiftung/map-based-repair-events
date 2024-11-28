<?php

echo '<div class="workshop-wrapper">';
    echo $this->Html->link(
        $workshop->name,
        $this->Html->urlWorkshopDetail($workshop->url),
        [
            'class' => 'heading',
        ],
    );
    echo '<div class="table">';

        $classes = ['button'];
        $buttonHref = $this->Html->urlFundingsEdit($workshop->uid);
        if ($workshop->funding_created_by_different_owner) {
            $classes[] = 'disabled';
            $buttonHref = 'javascript:void(0);';
        }
        $isSubmitted = $workshop->funding_exists && $workshop->workshop_funding->is_submitted;
        if ($isSubmitted) {
            $classes[] = 'disabled';
            $buttonHref = 'javascript:void(0);';
        }
        echo $this->Html->link(
            $workshop->funding_exists ? 'Förderantrag bearbeiten' : 'Förderantrag erstellen',
            $buttonHref,
            [
                'class' => implode(' ', $classes),
                'disabled' => $isSubmitted,
            ],
        );

        echo '<div>';
            if ($workshop->funding_exists) {
                echo '<div>UID: ' . $workshop->workshop_funding->uid . '</div>';
            }
            echo $this->element('funding/owner', ['funding' => $workshop->workshop_funding]);
            echo $this->element('funding/orgaTeam', ['orgaTeam' => $workshop->orga_team]);
            if (!$isSubmitted) {
                echo $this->element('funding/activityProof', ['workshop' => $workshop]);
                echo $this->element('funding/freistellungsbescheid', ['workshop' => $workshop]);
            } else {
                echo $this->element('funding/submitInfo', ['workshop' => $workshop]);
           }
            if ($workshop->funding_exists && !$workshop->workshop_funding->is_submitted) {
                echo $this->element('funding/delete', ['funding' => $workshop->workshop_funding]);
            }
        echo '</div>';

    echo '</div>';
echo '</div>';

echo '<div class="dotted-line"></div>';