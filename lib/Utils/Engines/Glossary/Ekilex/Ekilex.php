<?php

namespace Engines\Glossary\Ekilex;

use INIT;
use MultiCurlHandler;
use RuntimeException;

class Ekilex
{
    private string $apiBaseUrl;

    private string $apiKey;

    const MAX_SYNONYMS_PER_LEXEME = 10;
    const SEPARATOR = '; ';

    public function __construct()
    {
        $this->apiBaseUrl = INIT::$EKILEX_API_BASE_URL;
        $this->apiKey = INIT::$EKILEX_API_KEY;
    }

    public function getSynonymsInTargetLanguage($source, $sourceLanguage, $targetLanguage, $dataset): array
    {
        $matches = [
            'terms' => [],
            'blacklisted_terms' => [],
            'id_segment' => null
        ];

        try {
            $ekilexSourceLanguage = EkilexLanguageMapper::getEkilexLanguageCode($sourceLanguage);
            $ekilexTargetLanguage = EkilexLanguageMapper::getEkilexLanguageCode($targetLanguage);
        } catch (RuntimeException $e) {
            return $matches;
        }

        $wordsIds = $this->getWordsIdsByTerm($source, $ekilexSourceLanguage, $dataset);
        if (empty($wordsIds)) {
            return $matches;
        }

        $synonymsMetaMap = [];
        foreach ($this->getWordsDetailsResponses($wordsIds, $dataset) as $_ => $wordDetailsResponse) {
            foreach ($wordDetailsResponse['lexemes'] as $lexemeData) {
                $lexeme = new Lexeme($lexemeData);
                $synonymsMap = $lexeme->getSynonyms($ekilexTargetLanguage);
                if (empty($synonymsMap)) {
                    continue;
                }

                $sentences = $lexeme->getSentences();
                $definitions = $lexeme->getDefinitions();
                $notes = $lexeme->getNotes();
                $synonymsIds = array_slice(array_keys($synonymsMap), 0, self::MAX_SYNONYMS_PER_LEXEME);

                if (!empty($synonymsIds)) {
                    foreach ($this->getWordsDetailsResponses($synonymsIds, $dataset) as $synonymId => $synonymDetailsResponse) {
                        foreach ($synonymDetailsResponse['lexemes'] as $synonymLexemeData) {
                            $synonymLexeme = new Lexeme($synonymLexemeData);
                            $synonymsMetaMap[$synonymId] = [
                                'term' => $synonymsMap[$synonymId],
                                'notes' => $synonymLexeme->getNotes(),
                                'sentences' => $synonymLexeme->getSentences()
                            ];
                        }
                    }
                }


                foreach ($synonymsIds as $synonymId) {
                    if (!isset($synonymsMetaMap[$synonymId])) {
                        continue;
                    }

                    $matches['terms'][] = [
                        'id' => $synonymId,
                        'matching_words' => [$source],
                        'source' => [
                            'term' => $source,
                            'note' => join(self::SEPARATOR, $notes),
                            'sentence' => join(self::SEPARATOR, $sentences)
                        ],
                        'target' => [
                            'term' => $synonymsMetaMap[$synonymId]['term'],
                            'note' => join(self::SEPARATOR, $synonymsMetaMap[$synonymId]['notes'] ?? []),
                            'sentence' => join(self::SEPARATOR, $synonymsMetaMap[$synonymId]['sentences'] ?? [])
                        ],
                        'metadata' => [
                            'definition' => join(self::SEPARATOR, $definitions),
                            'key' => '',
                            'key_name' => 'Ekilex',
                            'domain' => $lexeme->getDatasetName(),
                            'subdomain' => '',
                            'create_date' => '',
                            'last_update_date' => ''
                        ]
                    ];
                }
            }
        }

        return $matches;
    }

    public function getDatasets()
    {
        $curlHandler = new MultiCurlHandler();
        $token = $curlHandler->createResource($this->apiBaseUrl . '/datasets', [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'ekilex-api-key: ' . $this->apiKey
            ],
            CURLOPT_CONNECTTIMEOUT => 10
        ]);

        $curlHandler->multiExec();

        /** @var array $response */
        $response = $curlHandler->getSingleContent($token, function ($response) {
            return json_decode($response, true);
        });

        return array_map(function ($dataset) {
            return [
                'id' => $dataset['code'],
                'name' => $dataset['name']
            ];
        }, array_values(
                array_filter($response, function ($dataset) {
                    return $dataset['visible'];
                })
            )
        );
    }

    private function getWordsDetailsResponses($wordsIds, $dataset): array
    {
        $multiCurl = new MultiCurlHandler();
        foreach ($wordsIds as $wordId) {
            $url = rtrim(
                join('/', [
                    $this->apiBaseUrl,
                    'word/details',
                    $wordId,
                    $dataset
                ]),
                '/'
            );

            $multiCurl->createResource($url, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'ekilex-api-key: ' . $this->apiKey
                ],
                CURLOPT_CONNECTTIMEOUT => 10
            ], $wordId);
        }
        $multiCurl->multiExec();
        $multiCurl->multiCurlCloseAll();

        return $multiCurl->getAllContents(function ($response) {
            return json_decode($response, true);
        });
    }

    private function getWordsIdsByTerm($term, $lang, $dataset = ''): array
    {
        $curlHandler = new MultiCurlHandler();
        $url = rtrim(
            join('/', [
                $this->apiBaseUrl,
                'word/search',
                urlencode($term),
                $dataset
            ]),
            '/'
        );

        $token = $curlHandler->createResource($url, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'ekilex-api-key: ' . $this->apiKey
            ],
            CURLOPT_CONNECTTIMEOUT => 10
        ]);

        $curlHandler->multiExec();

        $response = $curlHandler->getSingleContent($token, function ($response) {
            return empty($response) ? [] : json_decode($response, true);
        });

        if (!isset($response['words'])) {
            return [];
        }

        $wordsIds = array_column(
            array_filter($response['words'], function ($wordData) use ($lang) {
                return $wordData['lang'] === $lang;
            }), 'wordId'
        );

        if (empty($wordsIds)) {
            return [];
        }

        return $wordsIds;
    }
}