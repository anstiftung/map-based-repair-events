<?php
declare(strict_types=1);

echo '<div class="workshop-wrapper">';
    echo $this->Html->link(
        $workshop->name,
        $this->Html->urlWorkshopDetail($workshop->url),
        [
            'class' => 'heading',
        ],
    );
    echo '<div class="table">';

        $isSubmitted = $workshop->funding_exists && $workshop->workshop_funding->is_submitted;
        if (!$workshop->funding_exists || !$isSubmitted) {
            $classes = ['button'];
            $buttonHref = $this->Html->urlFundingsEdit($workshop->uid);
            $fundingFinished = $this->Time->isFundingFinished();
            if ($workshop->funding_created_by_different_owner || $fundingFinished) {
                $classes[] = 'disabled';
                $buttonHref = 'javascript:void(0);';
            }
            echo $this->Html->link(
                $workshop->funding_exists ? 'Förderantrag bearbeiten' : 'Förderantrag erstellen',
                $buttonHref,
                [
                    'class' => implode(' ', $classes),
                ],
            );
        }

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
                echo $this->element('funding/submitInfo', ['funding' => $workshop->workshop_funding]);
                echo $this->element('funding/moneyTransferInfo', ['funding' => $workshop->workshop_funding]);
                echo $this->element('funding/zuwendungsbestaetigungInfo', ['funding' => $workshop->workshop_funding]);
                echo $this->element('funding/usageproofInfo', ['funding' => $workshop->workshop_funding]);
           }
            if ($workshop->funding_exists && !$workshop->workshop_funding->is_submitted) {
                echo $this->element('funding/delete', ['funding' => $workshop->workshop_funding]);
            }
        echo '</div>';

    echo '</div>';
echo '</div>';

echo '<div class="dotted-line"></div>';