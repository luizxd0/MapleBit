<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/coordinates.php';

$name = trim((string) ($_GET['name'] ?? ''));
if (!preg_match('/^[A-Za-z0-9_-]{1,13}$/', $name)) {
    http_response_code(400);
    exit;
}

$characterStatement = $mysqli->prepare(
    'SELECT id, skincolor, gender, hair, face FROM characters WHERE name = ? LIMIT 1'
);
$characterStatement->bind_param('s', $name);
$characterStatement->execute();
$character = $characterStatement->get_result()->fetch_assoc();
if (!$character) {
    http_response_code(404);
    exit;
}

$equipmentStatement = $mysqli->prepare(
    'SELECT itemid, position FROM inventoryitems '
    . 'WHERE characterid = ? AND inventorytype = -1 ORDER BY position DESC'
);
$characterId = (int) $character['id'];
$equipmentStatement->bind_param('i', $characterId);
$equipmentStatement->execute();
$equipment = $equipmentStatement->get_result()->fetch_all(MYSQLI_ASSOC);

$image = new Character();
$cacheImage = "Characters/{$name}.png";
$cacheFingerprint = "Characters/{$name}.sha256";
$equippedByPosition = [];
$cap = $mask = $eyes = $ears = $coat = $pants = $shoes = $glove = null;
$cape = $shield = $weapon = null;

foreach ($equipment as $item) {
    $itemId = (int) $item['itemid'];
    $position = (int) $item['position'];
    $equippedByPosition[(string) $position] = $itemId;

    switch ($position) {
        case -1:
        case -101:
            $cap = $itemId;
            break;
        case -2:
        case -102:
            $mask = $itemId;
            break;
        case -3:
        case -103:
            $eyes = $itemId;
            break;
        case -4:
        case -104:
            $ears = $itemId;
            break;
        case -5:
        case -105:
            $coat = $itemId;
            break;
        case -6:
        case -106:
            $pants = $itemId;
            break;
        case -7:
        case -107:
            $shoes = $itemId;
            break;
        case -8:
        case -108:
            $glove = $itemId;
            break;
        case -9:
        case -109:
            $cape = $itemId;
            break;
        case -10:
        case -110:
            $shield = $itemId;
            break;
        case -11:
        case -111:
            $weapon = $itemId;
            break;
    }
}

if ($weapon === null) {
    $weapon = 1;
}
$image->setWepInfo($weapon);

$fingerprint = hash('sha256', json_encode([
    'skin' => (int) $character['skincolor'],
    'gender' => (int) $character['gender'],
    'hair' => (int) $character['hair'],
    'face' => (int) $character['face'],
    'equipment' => $equippedByPosition,
], JSON_THROW_ON_ERROR));
$cachedFingerprint = is_file($cacheFingerprint)
    ? trim((string) file_get_contents($cacheFingerprint))
    : '';

if (is_file($cacheImage) && hash_equals($fingerprint, $cachedFingerprint)) {
    $image->charType('use', $name);
    exit;
}

$image->setVaribles([
    'Skin' => (int) $character['skincolor'],
    'Gender' => (int) $character['gender'],
    'Hair' => (int) $character['hair'],
    'Face' => (int) $character['face'],
    'Cap' => $cap,
    'Mask' => $mask,
    'Eyes' => $eyes,
    'Ears' => $ears,
    'Coat' => $coat,
    'Pants' => $pants,
    'Shoes' => $shoes,
    'Glove' => $glove,
    'Cape' => $cape,
    'Shield' => $shield,
    'Weapon' => $weapon,
]);
$image->setWeapon('weaponBelowBody');
$image->setCap('capeBelowBody');
$image->setCap('capBelowHead');
$image->setCap('capAccessoryBelowBody');
$image->setCape('cape');
$image->setCape('backWing');
$image->setCap('backCap');
$image->setCape('capeBelowBody');
$image->setCap('capeBelowBody');
$image->setShield();
$image->setHair('hairBelowBody');
$image->setShoes('capAccessoryBelowBody');
$image->setWeapon('weaponOverArmBelowHead');
$image->createBody('body');
$image->setShoes('shoes');
$image->setShoes('weaponOverBody');
$image->setGlove('l', 1);
$image->setWeapon('weaponOverBody');
$image->setPants();
$image->setCoat('mail');
$image->setShoes('shoesTop');
$image->setShoes('shoesOverPants');
$image->setShoes('pantsOverMailChest');
$image->setShoes('gloveWristBelowMailArm');
$image->setWeapon('armBelowHeadOverMailChest');
$image->setHair('hairBelowHead');
$image->setCap('capBelowHead');
$image->createBody('head');
$image->setAccessory('Ears', 'accessoryEar');
$image->setCap('backHairOverCape');
$image->setCap('backHair');
$image->setHair('hairShade');
$image->setCap('capAccessoryBelowAccFace');
$image->setAccessory('Mask', 'accessoryFaceBelowFace');
$image->setAccessory('Eyes', 'accessoryEyeBelowFace');
$image->setFace();
$image->setAccessory('Mask', 'accessoryFace');
$image->setCap('accessoryEyeOverCap');
$image->setAccessory('Eyes', 'accessoryEye');
$image->setCap('accessoryEar');
$image->setHair('hair');
$image->setHair('hairOverHead');
$image->setAccessory('Ears', 'accessoryEarOverHair');
$image->setAccessory('Eyes', 'accessoryOverHair');
$image->setAccessory('Eyes', 'hairOverHead');
$image->setCap('capBelowAccessory');
$image->setCap('0');
$image->setCap('cap');
$image->setCap('body');
$image->setCap('capOverHair');
$image->setAccessory('Mask', 'capOverHair');
$image->setAccessory('Eyes', 'accessoryEyeOverCap');
$image->setAccessory('Mask', 'capeOverHead');
$image->setCape('capeOverHead');
$image->setCape('capOverHair');
$image->setWeapon('weapon');
$image->createBody('arm');
$image->setShield('weaponOverArmBelowHead');
$image->setWeapon('weaponBelowArm');
$image->setCoat('mailArm');
$image->setCape('capeArm');
$image->setWeapon('weaponOverArm');
$image->createBody('hand');
$image->setGlove('l', 2);
$image->setGlove('r');
$image->setWeapon('weaponOverHand');
$image->setWeapon('weaponOverGlove');
$image->setWeapon('weaponWristOverGlove');
$image->setWeapon('emotionOverBody');
$image->setWeapon('characterEnd');

$image->charType('create', $name);
file_put_contents($cacheFingerprint, $fingerprint, LOCK_EX);
