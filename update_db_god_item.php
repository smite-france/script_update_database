<?php

setlocale(LC_ALL, 'fra');

function pr($o){
	echo "<pre>";
	print_r($o);
	echo "</pre>";
}

function toAscii($str, $replace=array(), $delimiter='-') {
	if( !empty($replace) ) {
		$str = str_replace((array)$replace, ' ', $str);
	}

	$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
	$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
	$clean = strtolower(trim($clean, '-'));
	$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

	return $clean;
}

// Including SmiteAPIHelper
require_once("classes/SmiteAPIHelper.php");
require_once("classes/medoo.min.php");
require_once("/home/smitefr/public_html/wp-config.php");

//
// SMITEAPIHELPER INITIALIZATION
//

SmiteAPIHelper::setCredentials(1412,'EDBD27A6E5EE4359B7F1377F1697A266');
SmiteAPIHelper::$_format = SmiteAPIHelper::SMITE_API_FORMAT_JSON;


//
// SMITE API CALLS
//
$item = null;
$god = null;

$item = SmiteAPIHelper::getItems(3);
$item = json_decode($item, true);

$god = SmiteAPIHelper::getGods(3);
$god = json_decode($god, true);

/*
pr($item);
pr($god);
*/


$database = new medoo([
	'database_type' => 'mysql',
	'database_name' => DB_NAME,
	'server' => DB_HOST,
	'username' => DB_USER,
	'password' => DB_PASSWORD,
	'charset' => 'utf8'
]);




foreach($item as $k => $v){
	
	$datas = $database->select($table_prefix.'items','ItemId',['ItemId' => $v['ItemId']]);
	
	if(!empty($datas)){
		$database->update($table_prefix.'items', [
			'ItemDescription' => serialize ($v['ItemDescription']),
			'ChildItemId' => $v['ChildItemId'],
			'DeviceName' => $v['DeviceName'],
			'IconId' => $v['IconId'],
			'ItemTier' => $v['ItemTier'],
			'Price' => $v['Price'],
			'RootItemId' => $v['RootItemId'],
			'ShortDesc' => $v['ShortDesc'],
			'StartingItem' => $v['StartingItem'],
			'Type' => $v['Type'],
			'itemIcon_URL' => $v['itemIcon_URL'],
			'Slug' => toAscii($v['DeviceName']),
		],[
			'ItemId' => $v['ItemId'],
		]
		);
	}else{
		$database->insert($table_prefix.'items', [
			'ItemDescription' => serialize ($v['ItemDescription']),
			'ChildItemId' => $v['ChildItemId'],
			'DeviceName' => $v['DeviceName'],
			'IconId' => $v['IconId'],
			'ItemTier' => $v['ItemTier'],
			'Price' => $v['Price'],
			'RootItemId' => $v['RootItemId'],
			'ShortDesc' => $v['ShortDesc'],
			'StartingItem' => $v['StartingItem'],
			'Type' => $v['Type'],
			'itemIcon_URL' => $v['itemIcon_URL'],
			'Slug' => toAscii($v['DeviceName']),
			'ItemId' => $v['ItemId'],
		]);
	}
	// echo "OK for => ".$v['DeviceName']."<br>";
}


pr($god);

foreach($god as $h => $j){
	
	$datas = $database->select($table_prefix.'gods','id',['id' => $j['id']]);
	
	if(!empty($datas)){
		$database->update($table_prefix.'gods', [
			'Ability_1' => serialize ($j['Ability_1']),
			'Ability_2' => serialize ($j['Ability_2']),
			'Ability_3' => serialize ($j['Ability_3']),
			'Ability_4' => serialize ($j['Ability_4']),
			'Ability_5' => serialize ($j['Ability_5']),
			'AttackSpeed' => $j['AttackSpeed'],
			'AttackSpeedPerLevel' => $j['AttackSpeedPerLevel'],
			'Cons'  => $j['Cons'],
			'HP5PerLevel'  => $j['HP5PerLevel'],
			'Health'  => $j['Health'],
			'HealthPerFive'  => $j['HealthPerFive'],
			'HealthPerLevel'  => $j['HealthPerLevel'],
			'Lore' => $j['Lore'],
			'MP5PerLevel' => $j['MP5PerLevel'],
			'MagicProtection' => $j['MagicProtection'],
			'MagicProtectionPerLevel' => $j['MagicProtectionPerLevel'],
			'MagicalPower' => $j['MagicalPower'],
			'MagicalPowerPerLevel' => $j['MagicalPowerPerLevel'],
			'Mana' => $j['Mana'],
			'ManaPerFive' => $j['ManaPerFive'],
			'ManaPerLevel'=> $j['ManaPerLevel'],
			'OnFreeRotation'=> $j['OnFreeRotation'],
			'Pantheon'=> $j['Pantheon'],
			'PhysicalPower'=> $j['PhysicalPower'],
			'PhysicalPowerPerLevel'=> $j['PhysicalPowerPerLevel'],
			'PhysicalProtection'=> $j['PhysicalProtection'],
			'PhysicalProtectionPerLevel'=> $j['PhysicalProtectionPerLevel'],
			'Pros'=> $j['Pros'],
			'Roles'=> $j['Roles'],
			'Speed'=> $j['Speed'],
			'Title'=> $j['Title'],
			'Type'=> $j['Type'],
			'godCard_URL'=> $j['godCard_URL'],
			'godIcon_URL'=> $j['godIcon_URL'],
			'latestGod'=> $j['latestGod'],
		],[
			'id' => $j['id'],
		]
		);
	}else{
		$database->insert($table_prefix.'gods', [
			'id' => $j['id'],
			'Slug' => toAscii($j['Name']),
			'Name' => $j['Name'],
			'Ability_1' => serialize ($j['Ability_1']),
			'Ability_2' => serialize ($j['Ability_2']),
			'Ability_3' => serialize ($j['Ability_3']),
			'Ability_4' => serialize ($j['Ability_4']),
			'Ability_5' => serialize ($j['Ability_5']),
			'AttackSpeed' => $j['AttackSpeed'],
			'AttackSpeedPerLevel' => $j['AttackSpeedPerLevel'],
			'Cons'  => $j['Cons'],
			'HP5PerLevel'  => $j['HP5PerLevel'],
			'Health'  => $j['Health'],
			'HealthPerFive'  => $j['HealthPerFive'],
			'HealthPerLevel'  => $j['HealthPerLevel'],
			'Lore' => $j['Lore'],
			'MP5PerLevel' => $j['MP5PerLevel'],
			'MagicProtection' => $j['MagicProtection'],
			'MagicProtectionPerLevel' => $j['MagicProtectionPerLevel'],
			'MagicalPower' => $j['MagicalPower'],
			'MagicalPowerPerLevel' => $j['MagicalPowerPerLevel'],
			'Mana' => $j['Mana'],
			'ManaPerFive' => $j['ManaPerFive'],
			'ManaPerLevel'=> $j['ManaPerLevel'],
			'OnFreeRotation'=> $j['OnFreeRotation'],
			'Pantheon'=> $j['Pantheon'],
			'PhysicalPower'=> $j['PhysicalPower'],
			'PhysicalPowerPerLevel'=> $j['PhysicalPowerPerLevel'],
			'PhysicalProtection'=> $j['PhysicalProtection'],
			'PhysicalProtectionPerLevel'=> $j['PhysicalProtectionPerLevel'],
			'Pros'=> $j['Pros'],
			'Roles'=> $j['Roles'],
			'Speed'=> $j['Speed'],
			'Title'=> $j['Title'],
			'Type'=> $j['Type'],
			'godCard_URL'=> $j['godCard_URL'],
			'godIcon_URL'=> $j['godIcon_URL'],
			'latestGod'=> $j['latestGod'],
		]);
	}
	$database->last_query();
	// echo "OK for =>".$j['Name']."<br>";
}