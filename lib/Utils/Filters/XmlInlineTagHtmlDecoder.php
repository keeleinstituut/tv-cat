<?php

namespace Filters;

class XmlInlineTagHtmlDecoder
{
    public static function decode(&$content): void
    {
        if (empty($content)) {
            return;
        }

        $content = str_replace('&lt;i&gt;', '<i>', $content);
        $content = str_replace('<i&gt;', '<i>', $content);
        $content = str_replace('&lt;i>', '<i>', $content);
        $content = str_replace('&lt;/i&gt;', '</i>', $content);
        $content = str_replace('</i&gt;', '</i>', $content);
        $content = str_replace('&lt;/i>', '</i>', $content);

        $content = str_replace('&lt;sup&gt;', '<sup>', $content);
        $content = str_replace('<sup&gt;', '<sup>', $content);
        $content = str_replace('&lt;sup>', '<sup>', $content);
        $content = str_replace('&lt;/sup&gt;', '</sup>', $content);
        $content = str_replace('</sup&gt;', '</sup>', $content);
        $content = str_replace('&lt;/sup>', '</sup>', $content);

        $content = str_replace('&lt;b&gt;', '<b>', $content);
        $content = str_replace('<b&gt;', '<b>', $content);
        $content = str_replace('&lt;b>', '<b>', $content);
        $content = str_replace('&lt;/b&gt;', '</b>', $content);
        $content = str_replace('</b&gt;', '</b>', $content);
        $content = str_replace('&lt;/b>', '</b>', $content);

        $content = str_replace('&lt;sub&gt;', '<sub>', $content);
        $content = str_replace('<sub&gt;', '<sub>', $content);
        $content = str_replace('&lt;sub>', '<sub>', $content);
        $content = str_replace('&lt;/sub&gt;', '</sub>', $content);
        $content = str_replace('</sub&gt;', '</sub>', $content);
        $content = str_replace('&lt;/sub>', '</sub>', $content);

        $content = str_replace('&lt;strong&gt;', '<strong>', $content);
        $content = str_replace('<strong&gt;', '<strong>', $content);
        $content = str_replace('&lt;strong>', '<strong>', $content);
        $content = str_replace('&lt;/strong&gt;', '</strong>', $content);
        $content = str_replace('</strong&gt;', '</strong>', $content);
        $content = str_replace('&lt;/strong>', '</strong>', $content);

        $content = str_replace('&lt;em&gt;', '<em>', $content);
        $content = str_replace('<em&gt;', '<em>', $content);
        $content = str_replace('&lt;em>', '<em>', $content);
        $content = str_replace('&lt;/em&gt;', '</em>', $content);
        $content = str_replace('</em&gt;', '</em>', $content);
        $content = str_replace('&lt;/em>', '</em>', $content);
    }
}