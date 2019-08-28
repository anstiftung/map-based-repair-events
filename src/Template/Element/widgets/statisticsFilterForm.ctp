<?php

    $this->element('addScript', ['script' =>
        JS_NAMESPACE.".WidgetStatistics.initFilterForm();
    "]);
    
    echo $this->Form->create(null, [
        'novalidate' => 'novalidate',
        'type' => 'get',
        'url' => $this->request->getAttribute('here')
    ]);
        if (isset($showDonutChart)) {
            echo $this->Form->hidden('showDonutChart', ['value' => $showDonutChart]);
        }
        if (isset($showBarChart)) {
            echo $this->Form->hidden('showBarChart', ['value' => $showBarChart]);
        }
        if (isset($showWorkshopName)) {
            echo $this->Form->hidden('showWorkshopName', ['value' => $showWorkshopName]);
        }
        echo $this->Form->hidden('backgroundColorOk', ['value' => $backgroundColorOk]);
        echo $this->Form->hidden('backgroundColorNotOk', ['value' => $backgroundColorNotOk]);
        echo $this->Form->hidden('borderColorOk', ['value' => $borderColorOk]);
        echo $this->Form->hidden('borderColorNotOk', ['value' => $borderColorNotOk]);
        echo $this->Form->hidden('today', ['value' => date('d.m.Y')]);
        if (isset($defaultDataSource)) {
            echo $this->Form->hidden('defaultDataSource', ['value' => $defaultDataSource]);
        }
        
        if (isset($year)) {
            echo '<div class="input select">';
            echo $this->Form->year('year', ['val' => $year, 'empty' => 'Jahr: alle', 'minYear' => 2010, 'maxYear' => date('Y')]);
            echo '</div>';
        }
        if (isset($month)) {
            echo '<div class="input select">';
                echo $this->Form->month('month', ['val' => $month, 'empty' => 'Monat: alle']);
            echo '</div>';
        }
        
        if (isset($dateFrom) && isset($dateTo)) {
            echo $this->element('datepicker', ['fields' => ['datefrom', 'dateto']]);
            if (!$this->request->getSession()->read('isMobile')) {
                echo '<span>Zeitraum: </span>';
            }
            echo $this->Form->control('dateFrom',  ['type' => 'text', 'label' => '', 'value' => $dateFrom]);
            echo '<span>bis</span>';
            echo $this->Form->control('dateTo',  ['type' => 'text', 'label' => '', 'value' => $dateTo]);
        }
        if (!empty($dataSources)) {
            echo $this->Form->control('dataSource',  ['type' => 'select', 'label' => '', 'options' => $dataSources, 'value' => $dataSource]);
        }
        echo '<button id="reset" class="button gray">Zurücksetzen</button>';
    echo $this->Form->end();
    
?>