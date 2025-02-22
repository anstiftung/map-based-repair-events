<?php
declare(strict_types=1);

echo '<b>' . $object->workshop->name . '</b><br />';
echo 'Owner: ' . $object->owner_user->name . '<br />';
if ($object->fundingsupporter->name != '') {
    echo 'Träger: ' . $object->fundingsupporter->name . '<br />';
}