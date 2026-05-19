<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.repec.classes.RepecFormatter');
import('plugins.generic.repec.classes.RepecLegacyHandleMap');
import('plugins.generic.repec.pages.RepecHandler');

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

    public function testArticleHandleUsesCustomPatternWhenConfigured()
    {
        $handler = new RepecHandler();
        $method = new ReflectionMethod($handler, 'getArticleHandle');
        $method->setAccessible(true);

        $handle = $method->invoke($handler, array(
            'archiveCode' => 'fgv',
            'seriesCode' => 'eaerae',
            'articleHandlePattern' => 'v:%v:y:%Y:i:%i:a:%a',
            'legacyHandles' => array(),
        ), $this->getSubmissionStub(59960), $this->getIssueStub('35', '3'), '1995');

        $this->assertSame('RePEc:fgv:eaerae:v:35:y:1995:i:3:a:59960', $handle);
    }

    public function testArticleHandleFallsBackToCurrentPatternWhenNotConfigured()
    {
        $handler = new RepecHandler();
        $method = new ReflectionMethod($handler, 'getArticleHandle');
        $method->setAccessible(true);

        $handle = $method->invoke($handler, array(
            'archiveCode' => 'abc',
            'seriesCode' => 'journ1',
            'articleHandlePattern' => '',
            'legacyHandles' => array(),
        ), $this->getSubmissionStub(123), $this->getIssueStub('35', '3'), '1995');

        $this->assertSame('RePEc:abc:journ1:v:35:y:1995:i:3:id:123', $handle);
    }

    public function testArticleHandleFallbackOmitsMissingIssueParts()
    {
        $handler = new RepecHandler();
        $method = new ReflectionMethod($handler, 'getArticleHandle');
        $method->setAccessible(true);

        $handle = $method->invoke($handler, array(
            'archiveCode' => 'abc',
            'seriesCode' => 'journ1',
            'articleHandlePattern' => '',
            'legacyHandles' => array(),
        ), $this->getSubmissionStub(123), null, '');

        $this->assertSame('RePEc:abc:journ1:id:123', $handle);
    }

    public function testArticleHandlePatternKeepsLegacyHandlePriority()
    {
        $handler = new RepecHandler();
        $method = new ReflectionMethod($handler, 'getArticleHandle');
        $method->setAccessible(true);

        $handle = $method->invoke($handler, array(
            'archiveCode' => 'fgv',
            'seriesCode' => 'eaerae',
            'articleHandlePattern' => 'v:%v:y:%Y:i:%i:a:%a',
            'legacyHandles' => array('59960' => 'RePEc:fgv:eaerae:a:legacy59960'),
        ), $this->getSubmissionStub(59960), $this->getIssueStub('35', '3'), '1995');

        $this->assertSame('RePEc:fgv:eaerae:a:legacy59960', $handle);
    }

    public function testInitDataDoesNotAutofillNewSeriesCode()
    {
        $form = $this->getSettingsFormStub(array());

        $form->initData();

        $this->assertSame('', $form->getData('seriesCode'));
    }

    public function testSeriesCodeSuggestionIsStillCalculated()
    {
        $form = $this->getSettingsFormStub(array());
        $method = new ReflectionMethod($form, 'generateSeriesCodeForContext');
        $method->setAccessible(true);

        $code = $method->invoke($form, new class () {
            public function getPath()
            {
                return 'Revista de Economia Aplicada';
            }

            public function getData($name)
            {
                return '';
            }

            public function getLocalizedName()
            {
                return 'Revista Exemplo';
            }
        });

        $this->assertSame('revist', $code);
    }

    public function testSavedArticleHandlePatternCannotBeChangedByFormStorage()
    {
        $form = $this->getSettingsFormStub(array(
            'articleHandlePattern' => 'v:%v:y:%Y:i:%i:a:%a',
        ));
        $method = new ReflectionMethod($form, 'getArticleHandlePatternValueForStorage');
        $method->setAccessible(true);

        $value = $method->invoke($form, 'id:%a');

        $this->assertSame('v:%v:y:%Y:i:%i:a:%a', $value);
    }

    public function testArticleHandlePatternValidationRejectsFullRepecHandle()
    {
        $form = $this->getSettingsFormStub(array());

        $this->assertTrue($form->validateArticleHandlePattern('v:%v:y:%Y:i:%i:a:%a'));
        $this->assertFalse($form->validateArticleHandlePattern('RePEc:fgv:eaerae:v:%v:y:%Y:i:%i:a:%a'));
    }

    public function testArticleHandlePatternValidationRequiresSubmissionToken()
    {
        $form = $this->getSettingsFormStub(array());

        $this->assertTrue($form->validateArticleHandlePattern('v:%v:y:%Y:i:%i:a:%a'));
        $this->assertFalse($form->validateArticleHandlePattern('v:%v:y:%Y:i:%i'));
    }

    public function testArticleHandlePatternPreviewUsesGlobalArchiveCodes()
    {
        $form = $this->getSettingsFormStub(array(
            'archiveCode' => 'fgv',
            'globalJournals' => '{"1":"eaerae"}',
        ));
        $form->setData('archiveCode', '');
        $form->setData('seriesCode', '');

        $archiveCodeMethod = new ReflectionMethod($form, 'getArticleHandlePatternPreviewArchiveCode');
        $archiveCodeMethod->setAccessible(true);
        $seriesCodeMethod = new ReflectionMethod($form, 'getArticleHandlePatternPreviewSeriesCode');
        $seriesCodeMethod->setAccessible(true);
        $previewMethod = new ReflectionMethod($form, 'buildArticleHandlePatternPreview');
        $previewMethod->setAccessible(true);

        $preview = $previewMethod->invoke(
            $form,
            $archiveCodeMethod->invoke($form),
            $seriesCodeMethod->invoke($form),
            'v:%v:y:%Y:i:%i:a:%a'
        );

        $this->assertSame('RePEc:fgv:eaerae:v:35:y:1995:i:3:a:59960', $preview);
    }

    public function testRemoveIndividualSettingsClearsArchiveAndSeriesCodesOnly()
    {
        $form = $this->getSettingsFormStub(array());
        $form->setData('removeIndividualRepecSettings', '1');
        $form->setData('archiveCode', 'ABC');
        $form->setData('seriesCode', 'JOURN1');
        $form->setData('maintainerEmail', 'repec@example.org');

        $method = new ReflectionMethod($form, 'getSettingValueForStorage');
        $method->setAccessible(true);

        $this->assertSame('', $method->invoke($form, 'archiveCode'));
        $this->assertSame('', $method->invoke($form, 'seriesCode'));
        $this->assertSame('repec@example.org', $method->invoke($form, 'maintainerEmail'));
    }

    public function testRemoveIndividualSettingsOptionOnlyEnabledWhenThereAreCodesToRemove()
    {
        $form = $this->getSettingsFormStub(array());
        $method = new ReflectionMethod($form, 'hasIndividualRepecSettingsToRemove');
        $method->setAccessible(true);

        $form->setData('archiveCode', '');
        $form->setData('seriesCode', '');
        $this->assertFalse($method->invoke($form));

        $form->setData('archiveCode', 'abc');
        $this->assertTrue($method->invoke($form));

        $form->setData('archiveCode', '');
        $form->setData('seriesCode', 'journ1');
        $this->assertTrue($method->invoke($form));
    }

    public function testSettingsTemplateSerializesTranslatedJavascriptStringsAsJson()
    {
        $template = file_get_contents(dirname(__DIR__) . '/templates/settingsForm.tpl');

        $this->assertStringNotContainsString("'{translate", $template);
        $this->assertStringNotContainsString('"{translate', $template);
        $this->assertStringContainsString(
            '{translate|json_encode key="plugins.generic.repec.settings.articleHandlePatternConfirm"}',
            $template
        );
        $this->assertStringContainsString(
            '{translate|json_encode key="plugins.generic.repec.settings.removeIndividualSettingsConfirm"}',
            $template
        );
    }

    public function testSeriesCodeGeneratorTargetsInputsByName()
    {
        $template = file_get_contents(dirname(__DIR__) . '/templates/settingsForm.tpl');

        $this->assertStringContainsString("data-target-name=\"seriesCode\"", $template);
        $this->assertStringContainsString('data-target-name="globalSeriesCodes[{$journal.id|escape}]"', $template);
        $this->assertStringNotContainsString('data-target="seriesCode"', $template);
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

    private function getSubmissionStub($id)
    {
        return new class ($id) {
            private $id;

            public function __construct($id)
            {
                $this->id = $id;
            }

            public function getId()
            {
                return $this->id;
            }
        };
    }

    private function getIssueStub($volume, $number)
    {
        return new class ($volume, $number) {
            private $volume;
            private $number;

            public function __construct($volume, $number)
            {
                $this->volume = $volume;
                $this->number = $number;
            }

            public function getVolume()
            {
                return $this->volume;
            }

            public function getNumber()
            {
                return $this->number;
            }
        };
    }

    private function getSettingsFormStub($settings)
    {
        $reflection = new ReflectionClass('RepecSettingsForm');
        $form = $reflection->newInstanceWithoutConstructor();
        $form->plugin = new class ($settings) {
            private $settings;

            public function __construct($settings)
            {
                $this->settings = $settings;
            }

            public function getSetting($contextId, $name)
            {
                return isset($this->settings[$name]) ? $this->settings[$name] : '';
            }
        };
        $form->contextId = 1;

        return $form;
    }
}
