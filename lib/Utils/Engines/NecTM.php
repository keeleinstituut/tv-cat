<?php

use Engines\NecTM\Auth\ApiRealmJwkRetriever;
use Engines\NecTM\Auth\CachedKeycloakServiceAccountJwtRetriever;
use Engines\NecTM\Auth\CachedRealmJwkRetriever;
use Engines\NecTM\Auth\JwtTokenDecoder;
use Engines\NecTM\Auth\KeycloakServiceAccountJwtRetriever;
use Engines\NecTM\Auth\ServiceAccountJwtRetrieverInterface;

class Engines_NecTM extends Engines_AbstractEngine
{
    /**
     * @var string
     */
    protected $content_type = 'json';

    /**
     * @var array
     */
    protected $_config = [
        'dataRefMap' => [],
        'segment' => null,
        'source' => null,
        'target' => null,
        'get_mt' => 1,
        'id_user' => null,
        'num_result' => 3,
        'mt_only' => false,
        'isConcordance' => false,
    ];

    /**
     * @param $engineRecord
     * @throws Exception
     */
    public function __construct($engineRecord)
    {
        parent::__construct($engineRecord);
        if ($this->engineRecord->type != "TM") {
            throw new Exception("Engine {$this->engineRecord->id} is not a TMS engine, found {$this->engineRecord->type} -> {$this->engineRecord->class_load}");
        }

        $this->engineRecord['base_url'] = rtrim(INIT::$NEC_TM_BASE_URL, '/');
    }

    protected function _decode($rawValue)
    {
        $function = func_get_args()[2] ?? 'translate_relative_url';
        if ($function !== 'translate_relative_url') {
            return !isset($rawValue['error']);
        }

        $dataRefMap = $this->_config['dataRefMap'] ?? [];

        if (is_string($rawValue)) {
            $decoded = json_decode($rawValue, true);
        } else {
            $decoded = $rawValue;
        }

        $results = [];
        if (!empty($decoded['results'])) {
            $matches = array_values(
                array_filter($decoded['results'], function ($result) {
                    return !empty(trim($result['tu']['target_text'] ?? '')) &&
                        !empty(trim($result['tu']['source_text'] ?? ''));
                })
            );

            $results['matches'] = array_map(function ($data) {
                return [
                    'quality' => $data['match'],
                    'match' => $data['match'] / 100,
                    'last-update-date' => $data['update_date'],
                    'created-by' => 'NecTM',
                    'segment' => $data['tu']['source_text'],
                    'translation' => $data['tu']['target_text']
                ];
            }, $matches);

        } elseif (isset($decoded['message'])) {
            $results['error'] = [
                'code' => 0,
                'message' => $decoded['message'],
            ];
        }

        return Engines_Results_MyMemory_TMS::getInstance($results, $this->featureSet, $dataRefMap);
    }

    public function get($_config)
    {
        $_config['segment'] = $this->_preserveSpecialStrings($_config['segment']);

        $parameters = [
            'q' => $_config['segment'],
            'slang' => $this->_fixLangCode($_config['source']),
            'tlang' => $this->_fixLangCode($_config['target']),
            'limit' => $_config['num_result'],
            'aut_trans' => false,
            'concordance' => boolval($_config['isConcordance'] ?? false)
        ];

        if (!empty($tags = $this->getTagsAsString($_config))) {
            $parameters['tag'] = $tags;
        }

        Log::doJsonLog(['params' => $parameters], 'nectm.log');

        $this->_setAdditionalCurlParams([
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->getServiceAccountJwtRetriever()->getJwt(),
            ]
        ]);

        $this->call('translate_relative_url', $parameters);

        return $this->result;
    }

    public function update($_config)
    {
        $parameters = [
            'stext' => preg_replace("/^(-?@-?)/", "", $_config['segment']),
            'ttext' => preg_replace("/^(-?@-?)/", "", $_config['translation']),
            'slang' => $this->_fixLangCode($_config['source']),
            'tlang' => $this->_fixLangCode($_config['target'])
        ];

        if (!empty($tags = $this->getTagsAsString($_config))) {
            $parameters['tag'] = $tags;
        } else {
            return [];
        }

        $jwt = $this->getServiceAccountJwtRetriever()->getJwt();
        $this->_setAdditionalCurlParams([
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $jwt,
                'Content-Type: application/json'
            ]
        ]);

        $this->call("update_relative_url", $parameters, true, true);
        return $this->result;
    }

    public function set($_config)
    {
    }

    public function delete($_config)
    {
    }

    protected function _fixLangCode($lang)
    {
        $l = explode("-", strtolower(trim($lang)));
        return $l[0];
    }

    /**
     * @param $tmKeys
     * @return array|array[] list of errors
     */
    public function validateTmKeys($tmKeys): array
    {
        $errors = [];

        // TODO: remove when NecTM keycloack service account will get the permission to retrieve tags by ids.
        return $errors;
        $tagsData = $this->retrieveTags(array_column($tmKeys, 'key'));

        $tagsMap = array_combine(
            array_column($tagsData, 'id'),
            $tagsData
        );

        foreach ($tmKeys as $tmKey) {
            $key = $tmKey['key'];
            if (!isset($tagsMap[$key])) {
                $errors[][] = [
                    'message' => "TM key $key not found",
                    'tm_key' => $key,
                ];
            }
        }

        return $errors;
    }

    private function retrieveTags($ids)
    {
        $url = join('/', [
            $this->engineRecord['base_url'],
            "tags?id=" . join('&id=', $ids)
        ]);
        $jwt = $this->getServiceAccountJwtRetriever()->getJwt();

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $jwt
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            throw new RuntimeException("Translation memory service is not available please try again later", $httpCode);
        }

        return json_decode($response, true)['tags'] ?? [];
    }

    private function getServiceAccountJwtRetriever(): ServiceAccountJwtRetrieverInterface
    {
        return new CachedKeycloakServiceAccountJwtRetriever(
            new KeycloakServiceAccountJwtRetriever(),
            new JwtTokenDecoder(
                new CachedRealmJwkRetriever(
                    new ApiRealmJwkRetriever()
                )
            )
        );
    }

    private function getTagsAsString($config): ?string
    {
        if (!empty($config['id_user'])) {
            if (!is_array($config['id_user'])) {
                $config['id_user'] = [$config['id_user']];
            }
            return implode(",", $config['id_user']);
        }

        return null;
    }

    public static function getID(): int
    {
        return 13;
    }
}