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

    public function getDatasets(): array
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

        $notSortedDatasets = array_map(function ($dataset) {
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

        $notSortedDatasetsRuntimeMap = [];
        foreach ($notSortedDatasets as $dataset) {
            $notSortedDatasetsRuntimeMap[mb_strtolower(trim($dataset['name']))] = $dataset;
        }

        $sortedDatasets = [];
        foreach ($this->getDatasetsOrder() as $datasetName) {
            if (isset($notSortedDatasetsRuntimeMap[$datasetName])) {
                $sortedDatasets[] = $notSortedDatasetsRuntimeMap[$datasetName];
                unset($notSortedDatasetsRuntimeMap[$datasetName]);
            }
        }

        return array_merge($sortedDatasets, array_values($notSortedDatasetsRuntimeMap));
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

    private function getDatasetsOrder(): array
    {
        return [
            'eki ühendsõnastik 2024',
            'eki terminibaas esterm',
            'eki ühendterminibaas esterm 2',
            'riigi teataja terminisõnastik',
            'andmeanalüüsi ja statistika oskussõnastik',
            'eesti-vene töötervishoiu ja -ohutuse terminibaas',
            'eesti-vene-eesti õigusterminoloogiabaas',
            'inglise vasted',
            'keemiaterminite baas',
            'militerm | sõjanduse, julgeoleku- ja kaitsepoliitika terminibaas',
            'poliitika ja valitsemise sõnastik',
            'tervise arengu instituudi tervisesõnastik',
            'tollialased terminid',
            'akadeemilise väljendusoskuse terminibaas',
            'arhitektuuri oskussõnastik',
            'biokeemiasõnastik',
            'botaanika terminibaas',
            'e-õppe terminid / e-learning terms',
            'eesti e-tervise sa terminibaas',
            'eesti keele morfoloogia andmebaas',
            'euroopa keeleõppe raamdokumendi terminid',
            'geneetika terminibaas',
            'kognitiivse keeleteaduse terminibaas',
            'koolisõnastikud 2005–2010',
            'kriisinõustamise terminibaas',
            'küberfüüsikalise süsteemitehnika terminibaas',
            'linguae,  maailma keeled, kirjad  ja rahvad',
            'materjalitehnika',
            'meditsiinifüüsika terminibaas',
            'metalliaabits',
            'montessori pedagoogika terminisõnastik',
            'raamatukogusõnastik',
            'robootika terminibaas',
            'tallinna linnavalitsuse terminibaas',
            'teenuste valdkonna terminibaas',
            'tekstiilmaterjalide terminibaas',
            'tervishoiu terminibaas',
            'ususõnastik',
            'vene keel eestis',
            'õendus- ja ämmaemandusterminite kogu',
            'logopeedia terminibaas',
            'sisekaitse terminibaas',
            '17.-18. sajandi ametite ja tegevusalade esindajate sõnavara',
            'aianduse terminibaas',
            'arheoloogia terminibaas',
            'betoonkonstruktsioonide terminibaas',
            'eesti rahvatantsu oskussõnastik',
            'eesti viipekeele it terminid',
            'eesti viipekeele meditsiiniterminid',
            'eesti-vene-inglise spaaterminid',
            'ehitiste projekteerimise terminibaas',
            'elektrotehnika',
            'entomoloogia terminibaas',
            'etenduskunstide terminibaas',
            'etümoloogia',
            'filmikunsti terminibaas filmterm',
            'filosoofia terminibaas',
            'folkloorsete uskumusolendite sõnastik',
            'foneetika sõnastik',
            'galeegi-eesti sõnaraamat',
            'geoloogia terminibaas',
            'geomorfoloogia terminibaas',
            'geriaatria terminibaas',
            'hambatehnika terminibaas',
            'hümnoloogia terminibaas',
            'ida mõtteloo leksikon',
            'ihtüoloogia terminibaas',
            'immunoloogia terminibaas',
            'katsebaas',
            'kaugseire terminibaas',
            'kokanduse terminibaas',
            'kooliinformaatika terminibaas',
            'kosmosetehnoloogia',
            'kriisijuhtimise terminibaas',
            'käsitööteaduse oskussõnad',
            'köite ja konserveerimise terminibaas',
            'limnoloogia sõnastik',
            'loomakasvatuse terminibaas',
            'loomanimetuste terminibaas',
            'loomaparasiitide nimistu',
            'loomaparasiitide terminibaas',
            'loomi kaasavate organisatsioonide terminibaas',
            'läti-eesti sõnastik 2015',
            'mesindusleksikon',
            'meteoroloogia ja klimatoloogia terminibaas',
            'metroloogia terminibaas',
            'muuseumitöö terminibaas',
            'muusikateraapia seletav sõnastik',
            'nahkhiirte terminibaas',
            'neurofüsioloogia terminibaas',
            'norra-eesti meditsiinisõnastik',
            'norra-eesti/eesti-norra sõnaraamat',
            'nüüdismuusika terminibaas',
            'onomastika oskussõnastik',
            'organisatsioonikäitumise terminibaas',
            'parasitoloogia terminibaas',
            'patsiendiohutuse terminibaas',
            'projektijuhtimise terminibaas',
            'purjetamise terminibaas',
            'põllumajandusloomade tõugude terminibaas',
            'rahvatervishoiu sõnastik',
            'rakubioloogia terminibaas',
            'ruumilise keskkonna planeerimise terminibaas',
            'semiootika terminibaas',
            'sisearhitektuuri terminibaas',
            'skeemiteraapia terminisõnastik',
            'supervisiooni terminibaas',
            'teatriterminite baas',
            'teehoolde terminibaas',
            'tegevusteraapia terminibaas',
            'terminivõrgustik',
            'toiduohutuse, loomatervise ja loomade heaolu terminibaas',
            'toiduteadus ja -tehnoloogia',
            'tootmistehnika ja -süsteemide terminibaas',
            'tsöliaakia ja gluteenivaba toitumise terminid',
            'turismi terminibaas',
            'tuumaenergia ja kiirguskaitse terminibaas',
            'tänapäevafolkloori terminibaas',
            'usundiloo terminibaas',
            'vaikimisi sõnakogu',
            'valgustehnika terminibaas',
            'veterinaarmeditsiini ja loomakasvatuse terminibaas',
            'vibulaskmise terminibaas',
            'p3m_vana',
        ];
    }
}