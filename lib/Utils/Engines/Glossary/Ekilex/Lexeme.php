<?php

namespace Engines\Glossary\Ekilex;

class Lexeme
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getSentences(): array
    {
        return array_merge(
            array_column( $this->data['usages'] ?? [], 'value'),
            array_column( $this->data['forums'] ?? [], 'value'),
        );
    }

    public function getDefinitions(): array
    {
        return array_map(function ($definition) {
            return strip_tags($definition['value']);
        }, $this->data['meaning']['definitions']);
    }

    public function getSynonyms(string $targetLanguage): array
    {
        $synonyms = [];
        foreach ($this->data['synonymLangGroups'] as $synonymLangGroup) {
            if ($synonymLangGroup['lang'] !== $targetLanguage) {
                continue;
            }

            foreach ($synonymLangGroup['synonyms'] as $wordSynonym) {
                foreach ($wordSynonym['words'] as $synonymWord) {
                    $synonyms[$synonymWord['wordId']] = $synonymWord['wordValue'];
                }
            }
        }

        return $synonyms;
    }

    public function getDatasetName()
    {
        return $this->data['datasetName'] ?? '';
    }

    public function getNotes(): array
    {
        $notes = [];
        $lexemeNotesData = $this->data['lexemeNoteLangGroups'] ?? [];
        foreach ($lexemeNotesData as $lexemeNotes) {
            foreach ($lexemeNotes['notes'] as $note) {
                $notes[] = $note['valueText'];
            }
        }

        return $notes;
    }
}