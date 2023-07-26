<?php

namespace API\App;

use AbstractControllers\IController;
use Database;
use FeatureSet;
use INIT;
use RuntimeException;

class HealthzController implements IController {

    protected $request;
    protected $response;
    protected $service;
    protected $app;

    public function __construct( $request, $response, $service, $app ) {
        $this->request  = $request;
        $this->response = $response;
        $this->service  = $service;
        $this->app      = $app;
    }

    public function ping()
    {
        Database::obtain()->ping();
        if ( !touch( INIT::$ROOT . DIRECTORY_SEPARATOR . "touch" ) ) {
            throw new RuntimeException( "Storage unavailable." );
        }

        $this->response->json( [] );
    }

    public function getUser()
    {
        return null;
    }

    public function userIsLogged()
    {
        return false;
    }

    public function getFeatureSet()
    {
        return [];
    }

    public function setFeatureSet(FeatureSet $features)
    {
    }
}