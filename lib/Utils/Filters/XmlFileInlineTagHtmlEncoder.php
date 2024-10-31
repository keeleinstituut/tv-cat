<?php

namespace Filters;

use FilesStorage\AbstractFilesStorage;
use Log;
use RuntimeException;

class XmlFileInlineTagHtmlEncoder
{
    private $bufferSize = 512;
    private $inputResource = null;
    private $outputResource = null;
    private $resultFilePath;
    private $inputFilePath;

    public function __construct(string $inputFilePath)
    {
        if (!file_exists($inputFilePath)) {
            throw new RuntimeException('File doesn\'t exist');
        }

        $this->inputFilePath = $inputFilePath;

        $path = dirname($inputFilePath);
        $this->resultFilePath = join('', [
            tempnam($path, AbstractFilesStorage::pathinfo_fix($inputFilePath, PATHINFO_FILENAME)),
            '.', AbstractFilesStorage::pathinfo_fix($inputFilePath, PATHINFO_EXTENSION)
        ]);

        $this->inputResource = fopen($this->inputFilePath, 'r');
        if ($this->inputResource === false) {
            throw new RuntimeException('Could not open input file for reading: ' . error_get_last()['message'] ?? '');
        }

        $this->outputResource = fopen($this->resultFilePath, 'w');
        if ($this->outputResource === false) {
            throw new RuntimeException('Could not open input file for reading: ' . error_get_last()['message'] ?? '');
        }
    }

    public function getEncodedFilePath(): string
    {
        $buffer = '';
        while (!feof($this->inputResource)) {
            do {
                if (($chunk = fread($this->inputResource, $this->bufferSize)) === false) {
                    throw new RuntimeException("Error reading from input resource.");
                }

                $buffer .= $chunk;

                $closeXmlTag = strrpos($buffer, '>');
                $openXmlTag = strrpos($buffer, '<');
                $hasXmlTagInterruption = ($closeXmlTag !== false && $openXmlTag !== false && $closeXmlTag < $openXmlTag) ||
                    ($closeXmlTag === false && $openXmlTag !== false);
            } while ($hasXmlTagInterruption);

            $buffer = preg_replace('/<i>/i', '&lt;i&gt;', $buffer);
            $buffer = preg_replace('/<\/i>/i', '&lt;/i&gt;', $buffer);

            $buffer = preg_replace('/<sup>/i', '&lt;sup&gt;', $buffer);
            $buffer = preg_replace('/<\/sup>/i', '&lt;/sup&gt;', $buffer);

            $buffer = preg_replace('/<b>/i', '&lt;b&gt;', $buffer);
            $buffer = preg_replace('/<\/b>/i', '&lt;/b&gt;', $buffer);

            $buffer = preg_replace('/<sub>/i', '&lt;sub&gt;', $buffer);
            $buffer = preg_replace('/<\/sub>/i', '&lt;/sub&gt;', $buffer);

            $buffer = preg_replace('/<strong>/i', '&lt;strong&gt;', $buffer);
            $buffer = preg_replace('/<\/strong>/i', '&lt;/strong&gt;', $buffer);

            $buffer = preg_replace('/<em>/i', '&lt;em&gt;', $buffer);
            $buffer = preg_replace('/<\/em>/i', '&lt;/em&gt;', $buffer);

            if (fwrite($this->outputResource, $buffer) === false) {
                throw new RuntimeException("Error writing to output resource.");
            }

            $buffer = '';
        }

        return $this->resultFilePath;
    }

    public function __destruct()
    {
        fclose($this->inputResource);
        fclose($this->outputResource);
    }
}
