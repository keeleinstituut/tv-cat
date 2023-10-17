<?php

namespace API\App;

use AbstractControllers\IController;
use API\V2\KleinController;
use Database;
use FeatureSet;
use INIT;
use RuntimeException;

class HealthzController extends KleinController {

    public function ping()
    {
        Database::obtain()->ping();
//        if ( !touch( INIT::$ROOT . DIRECTORY_SEPARATOR . "touch" ) ) {
//            throw new RuntimeException( "Storage unavailable." );
//        }

        $this->response->json( [] );
    }
}