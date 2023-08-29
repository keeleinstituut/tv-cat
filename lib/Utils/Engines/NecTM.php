<?php


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

        $this->engineRecord['base_url'] = INIT::$NEC_TM_BASE_URL;
    }

    protected function _decode($rawValue)
    {
        $function = func_get_args()[2] ?? 'translate_relative_url';
        if ($function !== 'translate_relative_url') {
            return isset($rawValue['error']);
        }

        $dataRefMap = $this->_config['dataRefMap'] ?? [];

        if (is_string($rawValue)) {
            $decoded = json_decode($rawValue, true);
        } else {
            $decoded = $rawValue;
        }

        $results = [];
        if (isset($decoded['results']) && !empty($decoded['results'])) {
            $matches = array_values(
                array_filter($decoded['results'], function ($result) {
                    return !empty(trim($result['tu']['target_text'] ?? '')) &&
                        !empty(trim($result['tu']['source_text'] ?? ''));
                })
            );

            $results['matches'] = array_map(function ($data) {
                return [
                    'match' => $data['match'],
                    'last-update-date' => $data['update_date'],
                    'created-by' => $data['username'],
                    'segment' => $data['tu']['source_text'],
                    'translation' => $data['tu']['target_text'],
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
            'aut_trans' => $_config['get_mt'],
            'concordance' => boolval($_config['isConcordance'] ?? false)
        ];

        if (!empty($_config['id_user'])) {
            if (!is_array($_config['id_user'])) {
                $_config['id_user'] = [$_config['id_user']];
            }

            $parameters['tag'] = implode(",", $_config['id_user']);
        }

        $this->call('translate_relative_url', $parameters);

        return $this->result;
    }

    public function update($_config): bool
    {
        $parameters = [
            'stext' => preg_replace("/^(-?@-?)/", "", $_config['segment']),
            'ttext' => preg_replace("/^(-?@-?)/", "", $_config['translation']),
            'slang' => $this->_fixLangCode($_config[ 'source' ]),
            'tlang' => $this->_fixLangCode($_config[ 'target' ]),
        ];

        if (!empty($_config['id_user'])) {
            if (!is_array($_config['id_user'])) {
                $_config['id_user'] = [$_config['id_user']];
            }
            $parameters['tag'] = implode(",", $_config['id_user']);
        }

        $this->call("update_relative_url", $parameters, true);

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
}