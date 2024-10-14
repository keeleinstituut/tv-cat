<?php

use Engines\Traits\FormatResponse;

class Engines_MTee extends Engines_AbstractEngine
{
    use FormatResponse;

    const TRANSLATION_TYPE_PLAIN_TEXT = 0;

    protected $_config = [
        'segment' => null,
        'source' => null,
        'target' => null,
        'domain' => null
    ];

    /**
     * @throws Exception
     */
    public function __construct($engineRecord)
    {
        parent::__construct($engineRecord);
        if ($this->engineRecord->type != "MT") {
            throw new Exception("Engine {$this->engineRecord->id} is not a MT engine, found {$this->engineRecord->type} -> {$this->engineRecord->class_load}");
        }

        $this->engineRecord['base_url'] = INIT::$MTEE_BASE_URL;
    }

    protected function _fixLangCode($lang)
    {
        return self::convertLanguageCode($lang);
    }

    public static function convertLanguageCode($code)
    {
        return explode(
            "-",
            strtolower(
                trim($code)
            )
        )[0];
    }

    protected function _decode($rawValue)
    {
        if (is_string($rawValue)) {
            $decoded = json_decode($rawValue, true);
        } else {
            $decoded = $rawValue;
        }

        if (isset($decoded["translations"])) {
            $all_args = func_get_args();
            $all_args[1]['text'] = $all_args[1]['text'][0];

            return $this->composeResponseAsMatch($all_args, [
                'data' => [
                    'translations' => [
                        ['translatedText' => $decoded["translations"][0]["translation"]]
                    ]
                ]
            ]);
        } elseif (isset($decoded['error']['response'])) {
            $response = json_decode($decoded['error']['response'], true);
            $result['error'] = [
                'code' => $response['error']['code'],
                'message' => $response['error']['message'],
            ];
        } else {
            return [];
        }

        return $result;
    }

    public function get($_config)
    {
        /**
         * MTee is not used since 01.10.2024
         */
        if (INIT::$MTEE_ENABLED === false) {
            return [];
        }

        if (!self::isSupportedLanguageDirection($_config['target'], $_config['source'])) {
            // {"error":{"code":404006,"message":"Language direction is not found"}} is the response from MTee
            $this->result = $this->_decode([
                'error' => [
                    'code' => 404006,
                    'message' => 'Language direction is not found'
                ]
            ]);

            return $this->result;
        }

        $this->_config['target'] = $_config['target'];
        $this->_config['source'] = $_config['source'];

        $parameters = [];
        $parameters['trgLang'] = $this->_fixLangCode($_config['target']);
        $parameters['srcLang'] = $this->_fixLangCode($_config['source']);
        $parameters['domain'] = $this->_getDomain($_config['tv_domain'] ?? '');
        $parameters['text'] = [$this->_preserveSpecialStrings($_config['segment'])];
        $parameters['textType'] = self::TRANSLATION_TYPE_PLAIN_TEXT;


        $this->_setAdditionalCurlParams([
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $this->call("translate_relative_url", $parameters, true, true);

        return $this->result;
    }

    public function set($_config)
    {
        //if engine does not implement DELETE method, exit
        return true;
    }

    public function update($_config)
    {
        //if engine does not implement DELETE method, exit
        return true;
    }

    public function delete($_config)
    {
        //if engine does not implement DELETE method, exit
        return true;
    }

    public static function getMTeeID()
    {
        return 12;
    }

    public static function isSupportedLanguageDirection($source, $target)
    {
        $externalSourceLanguage = self::convertLanguageCode($source);
        $externalTargetLanguage = self::convertLanguageCode($target);
        $supportedLanguages = self::supportedLanguages();

        return $externalSourceLanguage !== $externalTargetLanguage &&
            ($externalSourceLanguage === 'et' || $externalTargetLanguage === 'et') &&
            in_array(self::convertLanguageCode($source), $supportedLanguages) &&
            in_array(self::convertLanguageCode($source), $supportedLanguages);
    }

    protected static function supportedLanguages()
    {
        return ['en', 'et', 'ru', 'de'];
    }

    public function getName()
    {
        return 'MTee';
    }

    public function composeResponseAsMatch(array $all_args, $decoded)
    {
        $match = $this->_composeResponseAsMatch($all_args, $decoded);

        if (empty($match['source']) && !empty($this->_config['source'])) {
            $match['source'] = $this->_config['source'];
        }

        if (empty($match['target']) && !empty($this->_config['target'])) {
            $match['target'] = $this->_config['target'];
        }

        return $match;
    }

    private function _getDomain($tvDomain): ?string
    {
        //legal;general;crisis;military
        $tvDomain2MteeDomain = [
            'HAR' => 'general', // Haridus
            'TEA' => 'general', // Teadus
            'ARH' => 'general', // Arhiivindus
            'NKP' => 'legal', // Noorte- ja keelepoliitika
            'ÕIP' => 'legal', // Õiguspoliitika
            'KRP' => 'legal', // Kriminaalpoliitika
            'SET' => 'legal', // Seadusetõlked
            'JHP' => 'legal', // Justiitshalduspoliitika
            'EAP' => 'legal', // Eelarvepoliitika
            'MTP' => 'legal', // Maksu- ja tollipoliitika
            'RST' => 'general', // Riiklik statistika
            'RRP' => 'general', // Riigiraamatupidamine
            'FKP' => 'legal', // Finants- ja kindlustuspoliitika
            'KOP' => 'legal', // Kinnisvara- ja osaluspoliitika
            'ASP' => 'military', // Avalik kord ja sisejulgeolek
            'KPT' => 'crisis', // Kriisireguleerimine ja päästetööd
            'PRV' => 'military', // Piirivalve
            'KRI' => 'legal', // Kodakondsuse, rände ja identiteedihaldus
            'RPP' => 'legal', // Rahvastiku- ja perepoliitika
        ];

        return $tvDomain2MteeDomain[$tvDomain] ?? null;
    }
}