<?php

namespace APP\plugins\generic\repec\tests;

use APP\plugins\generic\repec\classes\RepecFormatter;
use APP\plugins\generic\repec\classes\RepecLegacyHandleMap;
use APP\plugins\generic\repec\classes\RepecSettingsForm;
use APP\plugins\generic\repec\pages\RepecHandler;
use PKP\tests\PKPTestCase;
use ReflectionClass;
use ReflectionMethod;

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

    public function testArchiveTemplateContainsRequiredContractFields()
    {
        $formatter = new RepecFormatter();
        $output = $formatter->formatArchive(array(
            'archiveCode' => 'abc',
            'archiveName' => 'Archive Name',
            'maintainerEmail' => 'repec@example.org',
            'archiveDescription' => 'Published articles',
            'archiveUrl' => 'https://example.org/repec/abc/',
        ));

        $this->assertStringContainsString("Template-Type: ReDIF-Archive 1.0\n", $output);
        $this->assertStringContainsString("Handle: RePEc:abc\n", $output);
        $this->assertStringContainsString("Name: Archive Name\n", $output);
        $this->assertStringContainsString("Maintainer-Email: repec@example.org\n", $output);
        $this->assertStringContainsString("URL: https://example.org/repec/abc/\n", $output);
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

    public function testArticleTemplateOmitsEmptyOptionalFieldsAndHtmlEscapedUrls()
    {
        $formatter = new RepecFormatter();
        $output = $formatter->formatArticles(array(array(
            'authors' => array(array('name' => 'Ana Silva', 'affiliation' => '')),
            'title' => 'Economia regional',
            'abstract' => '',
            'keywords' => array(),
            'journal' => 'Revista Exemplo',
            'pages' => '',
            'volume' => '',
            'issue' => '',
            'year' => '2026',
            'month' => '',
            'doi' => '',
            'files' => array(array(
                'function' => 'Abstract page',
                'url' => 'https://example.org/index.php/journal/article/view/10?foo=1&bar=2',
                'format' => 'text/html',
            )),
            'handle' => 'RePEc:abc:journl:id:10',
        )));

        $this->assertStringContainsString("Template-Type: ReDIF-Article 1.0\n", $output);
        $this->assertStringContainsString("Author-Name: Ana Silva\n", $output);
        $this->assertStringContainsString("Title: Economia regional\n", $output);
        $this->assertStringContainsString("Journal: Revista Exemplo\n", $output);
        $this->assertStringContainsString("Year: 2026\n", $output);
        $this->assertStringContainsString("File-URL: https://example.org/index.php/journal/article/view/10?foo=1&bar=2\nFile-Function: Abstract page\nFile-Format: text/html\n", $output);
        $this->assertStringContainsString("Handle: RePEc:abc:journl:id:10\n", $output);
        $this->assertStringNotContainsString('DOI:', $output);
        $this->assertStringNotContainsString('&amp;', $output);
    }

    public function testArticleTemplateContainsRequiredContractFields()
    {
        $formatter = new RepecFormatter();
        $output = $formatter->formatArticles(array(array(
            'authors' => array(array('name' => 'Ana Silva', 'affiliation' => '')),
            'title' => 'Economia regional',
            'abstract' => '',
            'keywords' => array(),
            'journal' => 'Revista Exemplo',
            'pages' => '',
            'volume' => '',
            'issue' => '',
            'year' => '2026',
            'month' => '',
            'doi' => '',
            'files' => array(),
            'handle' => 'RePEc:abc:journl:id:10',
        )));

        $this->assertStringContainsString("Template-Type: ReDIF-Article 1.0\n", $output);
        $this->assertStringContainsString("Author-Name: Ana Silva\n", $output);
        $this->assertStringContainsString("Title: Economia regional\n", $output);
        $this->assertStringContainsString("Journal: Revista Exemplo\n", $output);
        $this->assertStringContainsString("Year: 2026\n", $output);
        $this->assertStringContainsString("Handle: RePEc:abc:journl:id:10\n", $output);
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

    public function testSeriesTemplateContainsRequiredContractFields()
    {
        $formatter = new RepecFormatter();
        $output = $formatter->formatSeries(array(
            'archiveCode' => 'abc',
            'seriesCode' => 'journ1',
            'seriesName' => 'Journal One',
            'archiveDescription' => '',
            'providerName' => 'Publisher One',
            'providerHomepage' => 'https://example.org/journ1',
            'providerInstitution' => '',
            'issn' => '',
            'maintainerName' => 'Maintainer',
            'maintainerEmail' => 'repec@example.org',
        ));

        $this->assertStringContainsString("Template-Type: ReDIF-Series 1.0\n", $output);
        $this->assertStringContainsString("Name: Journal One\n", $output);
        $this->assertStringContainsString("Provider-Name: Publisher One\n", $output);
        $this->assertStringContainsString("Maintainer-Name: Maintainer\n", $output);
        $this->assertStringContainsString("Maintainer-Email: repec@example.org\n", $output);
        $this->assertStringContainsString("Handle: RePEc:abc:journ1\n", $output);
    }

    public function testParsesLegacyHandleMapJson()
    {
        $parser = new RepecLegacyHandleMap();
        list($handles, $error) = $parser->parseJson('{"456":"RePEc:abc:journl:a:old456","123":"RePEc:abc:journl:v:30:y:2010:i:3:p:364?380,:id:old123"}');

        $this->assertNull($error);
        $this->assertSame(array(
            '123' => 'RePEc:abc:journl:v:30:y:2010:i:3:p:364?380,:id:old123',
            '456' => 'RePEc:abc:journl:a:old456',
        ), $handles);
    }

    public function testRejectsInvalidLegacyHandleMapJson()
    {
        $parser = new RepecLegacyHandleMap();

        $this->assertNotNull($parser->parseJson('[]')[1]);
        $this->assertNotNull($parser->parseJson('{"abc":"RePEc:abc:journl:a:old"}')[1]);
        $this->assertNotNull($parser->parseJson('{"123":"abc:journl:a:old"}')[1]);
        $this->assertNotNull($parser->parseJson('{}')[1]);
    }

    public function testArticleHandleUsesLegacyHandleWhenConfigured()
    {
        $handler = new RepecHandler();
        $method = new ReflectionMethod($handler, 'getArticleHandle');
        $method->setAccessible(true);
        $submission = new class () {
            public function getId()
            {
                return 123;
            }
        };

        $handle = $method->invoke($handler, array(
            'archiveCode' => 'abc',
            'seriesCode' => 'journ1',
            'legacyHandles' => array('123' => 'RePEc:abc:journ1:a:old123'),
        ), $submission, null, '2026');

        $this->assertSame('RePEc:abc:journ1:a:old123', $handle);
    }

    public function testArticleHandleGeneratedFromIssueMetadataIsStable()
    {
        $handler = new RepecHandler();
        $method = new ReflectionMethod($handler, 'getArticleHandle');
        $method->setAccessible(true);
        $submission = new class () {
            public function getId()
            {
                return 123;
            }
        };
        $issue = new class () {
            public function getVolume()
            {
                return '42';
            }

            public function getNumber()
            {
                return '2';
            }
        };

        $handle = $method->invoke($handler, array(
            'archiveCode' => 'abc',
            'seriesCode' => 'journ1',
            'legacyHandles' => array(),
        ), $submission, $issue, '2026');

        $this->assertSame('RePEc:abc:journ1:v:42:y:2026:i:2:id:123', $handle);
    }

    public function testArticleHandleIgnoresEmptyIssueMetadata()
    {
        $handler = new RepecHandler();
        $method = new ReflectionMethod($handler, 'getArticleHandle');
        $method->setAccessible(true);
        $submission = new class () {
            public function getId()
            {
                return 123;
            }
        };

        $handle = $method->invoke($handler, array(
            'archiveCode' => 'abc',
            'seriesCode' => 'journ1',
            'legacyHandles' => array(),
        ), $submission, null, '');

        $this->assertSame('RePEc:abc:journ1:id:123', $handle);
    }

    public function testArchiveUrlIsKeptAsDirectoryForPathInfoUrls()
    {
        $handler = new RepecHandler();
        $method = new ReflectionMethod($handler, 'ensureDirectoryUrl');
        $method->setAccessible(true);

        $this->assertSame('https://example.org/repec/abc/', $method->invoke($handler, 'https://example.org/repec/abc'));
        $this->assertSame('https://example.org/repec/abc/', $method->invoke($handler, 'https://example.org/repec/abc/'));
        $this->assertSame('https://example.org/index.php?journal=index&page=repec&op=abc', $method->invoke($handler, 'https://example.org/index.php?journal=index&page=repec&op=abc'));
    }

    public function testEncodesEmptyLegacyHandleMapAsJsonObject()
    {
        $parser = new RepecLegacyHandleMap();

        $this->assertSame("{}\n", $parser->encode(array()));
    }

    public function testStoresLegacyHandleMapCompressed()
    {
        $parser = new RepecLegacyHandleMap();
        $handles = array(
            '123' => 'RePEc:abc:journl:v:30:y:2010:i:3:p:364?380,:id:old123',
            '456' => 'RePEc:abc:journl:a:old456',
        );
        $stored = $parser->encodeForStorage($handles);

        if (function_exists('gzcompress')) {
            $this->assertStringStartsWith('gz64:', $stored);
        }
        $this->assertSame($handles, $parser->decode($stored));
    }

    public function testDecodesLegacyHandleMapStoredByPreviousVersion()
    {
        $parser = new RepecLegacyHandleMap();
        $handles = array('123' => 'RePEc:abc:journl:a:old123');
        $json = $parser->encode($handles);

        $this->assertSame($handles, $parser->decode($json));
    }

    public function testValidatesArchiveAndSeriesCodes()
    {
        $form = $this->getSettingsFormWithoutConstructor();

        $this->assertTrue($form->validateArchiveCode('abc'));
        $this->assertTrue($form->validateArchiveCode('ABC'));
        $this->assertFalse($form->validateArchiveCode('ab1'));
        $this->assertFalse($form->validateArchiveCode('abcd'));

        $this->assertTrue($form->validateSeriesCode('journ1'));
        $this->assertTrue($form->validateSeriesCode('ABC123'));
        $this->assertFalse($form->validateSeriesCode('abc12'));
        $this->assertFalse($form->validateSeriesCode('abc-12'));
    }

    public function testGeneratesSeriesCodeFromContextPathIssnAndName()
    {
        $form = $this->getSettingsFormWithoutConstructor();
        $method = new ReflectionMethod($form, 'generateSeriesCodeForContext');
        $method->setAccessible(true);

        $this->assertSame('revist', $method->invoke($form, $this->getContextStub('revista-exemplo', '', '', 'Journal Example')));
        $this->assertSame('123456', $method->invoke($form, $this->getContextStub('', '1234-5678', '', 'Journal Example')));
        $this->assertSame('revist', $method->invoke($form, $this->getContextStub('', '', '', 'Revista Árvore')));
        $this->assertSame('abc000', $method->invoke($form, $this->getContextStub('abc', '', '', 'Journal Example')));
    }

    private function getSettingsFormWithoutConstructor()
    {
        return (new ReflectionClass(RepecSettingsForm::class))->newInstanceWithoutConstructor();
    }

    private function getContextStub($path, $onlineIssn, $printIssn, $name)
    {
        return new class ($path, $onlineIssn, $printIssn, $name) {
            private $path;
            private $onlineIssn;
            private $printIssn;
            private $name;

            public function __construct($path, $onlineIssn, $printIssn, $name)
            {
                $this->path = $path;
                $this->onlineIssn = $onlineIssn;
                $this->printIssn = $printIssn;
                $this->name = $name;
            }

            public function getPath()
            {
                return $this->path;
            }

            public function getData($key)
            {
                if ($key === 'onlineIssn') {
                    return $this->onlineIssn;
                }
                if ($key === 'printIssn') {
                    return $this->printIssn;
                }
                return '';
            }

            public function getLocalizedName()
            {
                return $this->name;
            }
        };
    }
}
