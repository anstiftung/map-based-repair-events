<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class ProvincesTable extends Table
{

    public function initialize(array $config): void
    {
        $this->belongsTo('Countries', [
            'foreignKey' => 'country_code',
            'sort' => [
                'Countries.name' => 'ASC'
            ]
        ]);
    }

    public function getForDropdown($provinceCountsMap)
    {
        $provinces = $this->find('all',
            order: [
                'Countries.name_de' => 'ASC',
                'Provinces.name' => 'ASC'
            ],
            contain: [
                'Countries',
            ],
        );

        $preparedProvinces = [];
        foreach($provinces as $province) {
            if (!isset($provinceCountsMap[$province->id])) {
                continue;
            }
            $preparedProvinces[$province->country->name_de][$province->id] = $province->name . ' (' . $provinceCountsMap[$province->id] . ')';
        }
        return $preparedProvinces;
    }
}

?>