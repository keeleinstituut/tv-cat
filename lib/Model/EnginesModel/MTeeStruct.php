<?php

class EnginesModel_MTeeStruct extends EnginesModel_EngineStruct {

    /**
     * @var string
     */
    public $description = "MTee";

    /**
     * @var string
     */
    public $name = "MTee";

    /**
     * @var string
     */
    public $translate_relative_url = "translate/text";


    public $type = Constants_Engines::MT;

    /**
     * @var array
     */
    public $extra_parameters = [];

    /**
     * @var string
     */
    public $class_load = Constants_Engines::MTEE;

    /**
     * @var int
     */
    public $penalty = 0;

    /**
     * An empty struct
     * @return EnginesModel_MTeeStruct
     */
    public static function getStruct() {
        return new EnginesModel_MTeeStruct();
    }
}