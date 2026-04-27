<?php

/**
 * @file plugins/generic/repec/classes/RepecFormatter.inc.php
 *
 * @class RepecFormatter
 * @ingroup plugins_generic_repec
 *
 * @brief Formats archive, series and article metadata as ReDIF text.
 */

class RepecFormatter
{
    public function formatArchive($data)
    {
        $lines = array(
            array('Template-Type', 'ReDIF-Archive 1.0'),
            array('Handle', 'RePEc:' . $data['archiveCode']),
            array('Name', $data['archiveName']),
            array('Maintainer-Email', $data['maintainerEmail']),
            array('Description', $data['archiveDescription']),
            array('URL', $data['archiveUrl']),
        );
        return $this->formatTemplate($lines);
    }

    public function formatSeries($data)
    {
        $lines = array(
            array('Template-Type', 'ReDIF-Series 1.0'),
            array('Name', $data['seriesName']),
            array('Description', $data['archiveDescription']),
            array('Provider-Name', $data['providerName']),
            array('Provider-Homepage', $data['providerHomepage']),
            array('Provider-Institution', $data['providerInstitution']),
            array('ISSN', $data['issn']),
            array('Maintainer-Name', $data['maintainerName']),
            array('Maintainer-Email', $data['maintainerEmail']),
            array('Type', 'ReDIF-Article'),
            array('Handle', 'RePEc:' . $data['archiveCode'] . ':' . $data['seriesCode']),
        );
        return $this->formatTemplate($lines);
    }

    public function formatSeriesList($seriesList)
    {
        $templates = array();
        foreach ($seriesList as $series) {
            $templates[] = $this->formatSeries($series);
        }
        return implode("\n", $templates);
    }

    public function formatArticles($articles)
    {
        $templates = array();
        foreach ($articles as $article) {
            $lines = array(array('Template-Type', 'ReDIF-Article 1.0'));

            foreach ($article['authors'] as $author) {
                $lines[] = array('Author-Name', $author['name']);
                if (!empty($author['affiliation'])) {
                    $lines[] = array('Author-Workplace-Name', $author['affiliation']);
                }
            }

            $lines = array_merge($lines, array(
                array('Title', $article['title']),
                array('Publication-Status', 'Published'),
                array('Abstract', $article['abstract']),
                array('Keywords', $article['keywords']),
                array('Journal', $article['journal']),
                array('Pages', $article['pages']),
                array('Volume', $article['volume']),
                array('Issue', $article['issue']),
                array('Year', $article['year']),
                array('Month', $article['month']),
                array('DOI', $article['doi']),
            ));

            if (!empty($article['files'])) {
                foreach ($article['files'] as $file) {
                    $lines[] = array('File-URL', $file['url']);
                    if (!empty($file['function'])) {
                        $lines[] = array('File-Function', $file['function']);
                    }
                    $lines[] = array('File-Format', $file['format']);
                }
            } elseif (!empty($article['fileUrl'])) {
                $lines[] = array('File-URL', $article['fileUrl']);
                $lines[] = array('File-Format', $article['fileFormat']);
            }

            $lines[] = array('Handle', $article['handle']);
            $templates[] = $this->formatTemplate($lines);
        }
        return implode("\n", $templates);
    }

    private function formatTemplate($lines)
    {
        $output = array();
        foreach ($lines as $line) {
            list($field, $value) = $line;
            $value = $this->cleanValue($value, $field);
            if ($value === '') {
                continue;
            }
            $output[] = $this->formatField($field, $value);
        }
        return implode("\n", $output) . "\n";
    }

    private function formatField($field, $value)
    {
        $parts = preg_split('/\r\n|\r|\n/', $value);
        $first = array_shift($parts);
        $line = $field . ': ' . $first;
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $line .= "\n " . $part;
            }
        }
        return $line;
    }

    private function cleanValue($value, $field = null)
    {
        if (is_array($value)) {
            $separator = $field === 'Keywords' ? '; ' : ', ';
            $value = implode($separator, array_filter(array_map('trim', $value)));
        }
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
        $value = preg_replace('/[ \t]+/', ' ', $value);
        $value = preg_replace('/ *(\r\n|\r|\n) */', "\n", $value);
        return trim($value);
    }
}
