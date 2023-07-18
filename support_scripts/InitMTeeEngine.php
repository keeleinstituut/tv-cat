<?php


$root = realpath(dirname(__FILE__) . '/..');
include_once $root . "/inc/Bootstrap.php";
Bootstrap::start();

$db = Database::obtain(INIT::$DB_SERVER, INIT::$DB_USER, INIT::$DB_PASS, INIT::$DB_DATABASE);
$db->connect();

$dao = new EnginesModel_EngineDAO( $db );
/** @var EnginesModel_MTeeStruct $result */
$result = $dao->create(new EnginesModel_MTeeStruct);
if ($result->id !== Engines_MTee::getMTeeID()) {
    $db->update($dao::TABLE, ['id' => -1], ['id' => $result->id]);
    $db->update($dao::TABLE, ['id' => $result->id], ['id' => Engines_MTee::getMTeeID()]);
    $db->update($dao::TABLE, ['id' => Engines_MTee::getMTeeID()], ['id' => -1]);
}
