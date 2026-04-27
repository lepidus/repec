<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.repec.classes.RepecFormatter');

class RepecFormatterTest extends PKPTestCase
{
    public function testFormatsArchiveTemplate()
    {
        $formatter = new RepecFormatter();
        $output = $formatter->formatArchive(array(
            'archiveCode' => 'abc',
            'archiveName' => 'Department of Economics',
            'maintainerEmail' => 'repec@example.org',
            'archiveDescription' => 'Published articles',
            'archiveUrl' => 'https://example.org/index.php/journal/repec/abc/',
        ));

        $this->assertStringContainsString("Template-Type: ReDIF-Archive 1.0\n", $output);
        $this->assertStringContainsString("Handle: RePEc:abc\n", $output);
        $this->assertStringContainsString("URL: https://example.org/index.php/journal/repec/abc/\n", $output);
    }

    public function testFormatsArticleTemplateWithAuthorClusters()
    {
        $formatter = new RepecFormatter();
        $output = $formatter->formatArticles(array(array(
            'authors' => array(
                array('name' => 'Ana Silva', 'affiliation' => 'Universidade Exemplo'),
                array('name' => 'Bruno Souza', 'affiliation' => ''),
            ),
            'title' => 'Economia regional',
            'abstract' => 'Resumo com <strong>HTML</strong>.',
            'keywords' => array('economia', 'região'),
            'journal' => 'Revista Exemplo',
            'pages' => '1-10',
            'volume' => '2',
            'issue' => '1',
            'year' => '2026',
            'month' => 'April',
            'doi' => '10.1234/example',
            'files' => array(
                array('function' => 'Abstract page', 'url' => 'https://example.org/article/view/10', 'format' => 'text/html'),
                array('function' => 'Full text', 'url' => 'https://example.org/article/download/10/1', 'format' => 'application/pdf'),
            ),
            'handle' => 'RePEc:abc:journl:a:10',
        )));

        $this->assertStringContainsString("Author-Name: Ana Silva\nAuthor-Workplace-Name: Universidade Exemplo\n", $output);
        $this->assertStringContainsString("Author-Name: Bruno Souza\n", $output);
        $this->assertStringContainsString("Publication-Status: Published\n", $output);
        $this->assertStringContainsString("Abstract: Resumo com HTML.\n", $output);
        $this->assertStringContainsString("Keywords: economia; região\n", $output);
        $this->assertStringContainsString("File-URL: https://example.org/article/view/10\nFile-Function: Abstract page\nFile-Format: text/html\n", $output);
        $this->assertStringContainsString("File-URL: https://example.org/article/download/10/1\nFile-Function: Full text\nFile-Format: application/pdf\n", $output);
        $this->assertStringContainsString("Handle: RePEc:abc:journl:a:10\n", $output);
    }

    public function testFormatsMultilineValuesAsContinuationLines()
    {
        $formatter = new RepecFormatter();
        $output = $formatter->formatSeries(array(
            'archiveCode' => 'abc',
            'seriesCode' => 'journl',
            'seriesName' => "Journal\nwith subtitle",
            'archiveDescription' => '',
            'providerName' => 'Provider',
            'providerHomepage' => 'https://example.org',
            'providerInstitution' => '',
            'issn' => '1234-5678',
            'maintainerName' => 'Maintainer',
            'maintainerEmail' => 'repec@example.org',
        ));

        $this->assertStringContainsString("Name: Journal\n with subtitle\n", $output);
        $this->assertStringContainsString("ISSN: 1234-5678\n", $output);
        $this->assertStringNotContainsString("Description:", $output);
    }

    public function testFormatsMultipleSeriesTemplatesInOneFile()
    {
        $formatter = new RepecFormatter();
        $output = $formatter->formatSeriesList(array(
            array(
                'archiveCode' => 'abc',
                'seriesCode' => 'journ1',
                'seriesName' => 'Journal One',
                'archiveDescription' => '',
                'providerName' => 'Publisher One',
                'providerHomepage' => 'https://example.org/journ1',
                'providerInstitution' => '',
                'issn' => '1234-5678',
                'maintainerName' => 'Maintainer',
                'maintainerEmail' => 'repec@example.org',
            ),
            array(
                'archiveCode' => 'abc',
                'seriesCode' => 'journ2',
                'seriesName' => 'Journal Two',
                'archiveDescription' => '',
                'providerName' => 'Publisher Two',
                'providerHomepage' => 'https://example.org/journ2',
                'providerInstitution' => '',
                'issn' => '',
                'maintainerName' => 'Maintainer',
                'maintainerEmail' => 'repec@example.org',
            ),
        ));

        $this->assertSame(2, substr_count($output, "Template-Type: ReDIF-Series 1.0\n"));
        $this->assertStringContainsString("Handle: RePEc:abc:journ1\n", $output);
        $this->assertStringContainsString("Handle: RePEc:abc:journ2\n", $output);
    }
}
