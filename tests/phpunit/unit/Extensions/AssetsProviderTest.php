<?php
namespace Bolt\Tests\Extensions;

use Bolt\Extensions;
use Bolt\Assets\Target;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test correct operation and locations of assets provider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AssetsProviderTest extends BoltUnitTest
{
    public $template = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public $expectedCss = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="testfile.css?v=5e544598b8d78644071a6f25fd8bba82" media="screen">
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public $expectedLateCss = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
<link rel="stylesheet" href="testfile.css?v=5e544598b8d78644071a6f25fd8bba82" media="screen">
</body>
</html>
HTML;

    public $expectedJs = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
<script src="testfile.js?v=289fc946f38fee1a3e947eca1d6208b6"></script>
</body>
</html>
HTML;

    public $expectedLateJs = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
<script src="testfile.js?v=289fc946f38fee1a3e947eca1d6208b6"></script>
</body>
</html>
HTML;

    public $expectedStartOfHead = <<<HTML
<html>
<head>
<meta name="test-snippet" />
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public $expectedEndOfHead = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
<meta name="test-snippet" />
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public $expectedStartOfBody = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<p class="test-snippet"></p>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public $expectedEndOfHtml = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
</body>
<p class="test-snippet"></p>
</html>
HTML;

    public $expectedBeforeCss = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<meta name="test-snippet" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public $expectedAfterCss = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
<meta name="test-snippet" />
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public $expectedAfterMeta = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<meta name="test-snippet" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public function getApp()
    {
        $app = parent::getApp();
        $app['assets.file.hash'] = $app->protect(function ($fileName) {
            return md5($fileName);
        });

        return $app;
    }

    public function testBadExtensionSnippets()
    {
        $app = $this->getApp();
        $app['logger.system'] = new Mock\Logger();
        $app['extensions']->register(new Mock\BadExtensionSnippets($app));
        $html = $app['assets.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->template), $this->html($html));

//         $this->assertEquals(
//             'Snippet loading failed for badextensionsnippets: BadExtensionSnippets',
//             $app['logger.system']->lastLog()
//         );
    }

    public function testAddCss()
    {
        $app = $this->getApp();
        $app['assets.queue.file']->add('stylesheet', 'testfile.css');
        $assets = $app['assets.queue.file']->getQueue();
        $this->assertEquals(1, count($assets['stylesheet']));
    }

    public function testAddJs()
    {
        $app = $this->getApp();
        $app['assets.queue.file']->add('javascript','testfile.js');
        $assets = $app['assets.queue.file']->getQueue();
        $this->assertEquals(1, count($assets['javascript']));
    }

    public function testEmptyProcessAssetsFile()
    {
        $app = $this->getApp();
        $html = $app['assets.queue.file']->process('html');
        $this->assertEquals('html', $html);
    }

    public function testEmptyProcessAssetsSnippets()
    {
        $app = $this->getApp();
        $html = $app['assets.queue.snippet']->process('html');
        $this->assertEquals('html', $html);
    }

    public function testJsProcessAssets()
    {
        $app = $this->getApp();
        $app['assets.queue.file']->add('javascript', 'testfile.js');
        $html = $app['assets.queue.file']->process($this->template);
        $this->assertEquals($this->html($this->expectedJs), $this->html($html));
    }

    public function testLateJs()
    {
        $app = $this->getApp();
        $app['assets.queue.file']->add('javascript', 'testfile.js', ['late' => true]);
        $html = $app['assets.queue.file']->process($this->template);
        $this->assertEquals($this->html($this->expectedLateJs),  $this->html($html));
    }

    public function testCssProcessAssets()
    {
        $app = $this->getApp();
        $app['assets.queue.file']->add('stylesheet', 'testfile.css');
        $html = $app['assets.queue.file']->process($this->template);
        $this->assertEquals($this->html($this->expectedCss), $this->html($html));
    }

    public function testLateCss()
    {
        $app = $this->getApp();
        $app['assets.queue.file']->add('stylesheet', 'testfile.css', ['late' => true]);
        $html = $app['assets.queue.file']->process($this->template);
        $this->assertEquals($this->html($this->expectedLateCss), $this->html($html));
    }

    // This method normalises the html so that differeing whitespace doesn't effect the strings.
    protected function html($string)
    {
        $doc = new \DOMDocument();

        // Here for PHP 5.3 compatibility where the constants aren't available
        if (!defined('LIBXML_HTML_NOIMPLIED')) {
            $doc->loadHTML($string);
        } else {
            $doc->loadHTML($string, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        }
        $doc->preserveWhiteSpace = false;
        $html = $doc->saveHTML();
        $html = str_replace("\t", '', $html);
        $html = str_replace("\n", '', $html);

        return $html;
    }

    public function testSnippet()
    {
        $app = $this->getApp();

        // Test snippet inserts at top of <head>
        $app['assets.queue.snippet']->add(Target::START_OF_HEAD, '<meta name="test-snippet" />');

        $html = $app['assets.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedStartOfHead), $this->html($html));

        // Test snippet inserts at end of <head>
        $app['assets.queue.snippet']->clear();
        $app['assets.queue.snippet']->add(Target::END_OF_HEAD, '<meta name="test-snippet" />');
        $html = $app['assets.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedEndOfHead), $this->html($html));

        // Test snippet inserts at end of body
        $app['assets.queue.snippet']->clear();
        $app['assets.queue.snippet']->add(Target::START_OF_BODY, '<p class="test-snippet"></p>');
        $html = $app['assets.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedStartOfBody), $this->html($html));

        // Test snippet inserts at end of </html>
        $app['assets.queue.snippet']->clear();
        $app['assets.queue.snippet']->add(Target::END_OF_HTML, '<p class="test-snippet"></p>');
        $html = $app['assets.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedEndOfHtml), $this->html($html));

        // Test snippet inserts before existing css
        $app['assets.queue.snippet']->clear();
        $app['assets.queue.snippet']->add(Target::BEFORE_CSS, '<meta name="test-snippet" />');
        $html = $app['assets.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedBeforeCss), $this->html($html));

        // Test snippet inserts after existing css
        $app['assets.queue.snippet']->clear();
        $app['assets.queue.snippet']->add(Target::AFTER_CSS, '<meta name="test-snippet" />');
        $html = $app['assets.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedAfterCss), $this->html($html));

        // Test snippet inserts after existing meta tags
        $app['assets.queue.snippet']->clear();
        $app['assets.queue.snippet']->add(Target::AFTER_META, '<meta name="test-snippet" />');
        $html = $app['assets.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedAfterMeta), $this->html($html));
    }

    public function testSnippetsWithCallback()
    {
        $app = $this->getApp();
        $app['extensions']->register(new Mock\SnippetCallbackExtension($app));

        // Test snippet inserts at top of <head>
        $html = $app['assets.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedStartOfHead), $this->html($html));
    }

    public function testSnippetsWithGlobalCallback()
    {
        $app = $this->getApp();
        $app['assets.queue.snippet']->add(
            Target::AFTER_META,
            '\Bolt\Tests\Extensions\globalSnippet',
            'core',
            ["\n"]
        );

        // Test snippet inserts at top of <head>
        $html = $app['assets.queue.snippet']->process('<html></html>');
        $this->assertEquals('<html></html><br />'.PHP_EOL.PHP_EOL, $html);
    }

    public function testExtensionSnippets()
    {
        $app = $this->getApp();
        $app['extensions']->register(new Mock\Extension($app));
        $html = $app['assets.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedEndOfHead), $this->html($html));
    }

    public function testAddJquery()
    {
        $app = $this->makeApp();
        $app->initialize();

        $app = $this->getApp();
        $app['config']->set('general/add_jquery', true);
        $html = $app['assets.queue.snippet']->process($this->template);
        $this->assertContains('js/jquery', $html);

        $app['config']->set('general/add_jquery', false);
        $html = $app['assets.queue.snippet']->process($this->template);
        $this->assertNotContains('js/jquery', $html);
    }

    public function testAddJqueryOnlyOnce()
    {
        $app = $this->getApp();
        $app->initialize();
        $app['config']->set('general/add_jquery', true);
        $html = $app['assets.queue.snippet']->process($this->template);
        $html = $app['assets.queue.snippet']->process($html);
    }

    public function testSnippetsWorkWithBadHtml()
    {
        $locations = [
            Target::START_OF_HEAD,
            Target::START_OF_BODY,
            Target::END_OF_BODY,
            Target::END_OF_HTML,
            Target::AFTER_META,
            Target::AFTER_CSS,
            Target::BEFORE_CSS,
            Target::BEFORE_JS,
            Target::AFTER_CSS,
            Target::AFTER_JS,
            'madeuplocation'
        ];
        foreach ($locations as $location) {
            $app = $this->getApp();
            $template = "<invalid></invalid>";
            $snip = '<meta name="test-snippet" />';
            $app['assets.queue.snippet']->add($location, $snip);
            $html = $app['assets.queue.snippet']->process($template);
            $this->assertEquals($template.$snip.PHP_EOL, $html);
        }
    }

    public function testInsertWidget()
    {
        $app = $this->getApp();
        $app['extensions']->insertWidget('test', Target::START_OF_BODY, "", "testext", "", false);
        $this->expectOutputString("<section><div class='widget' id='widget-dacf7046' data-key='dacf7046'></div></section>");
        $app['extensions']->renderWidgetHolder('test', Target::START_OF_BODY);
    }

    public function testWidgetCaches()
    {
        $app = $this->getApp();
        $app['cache'] = new Mock\Cache();
        $app['extensions']->register(new Mock\SnippetCallbackExtension($app));
        $this->assertFalse($app['cache']->fetch('5e4c97cb'));
        $app['extensions']->insertWidget('test', Target::AFTER_JS, "snippetCallBack", "snippetcallback", "", false);

        // Double call to ensure second one hits cache
        $html = $app['extensions']->renderWidget('5e4c97cb');
        $this->assertEquals($html, $app['cache']->fetch('widget_5e4c97cb'));
    }

    public function testInvalidWidget()
    {
        $app = $this->getApp();
        $app['extensions']->insertWidget('test', Target::START_OF_BODY, "", "testext", "", false);
        $result = $app['extensions']->renderWidget('fakekey');
        $this->assertEquals("Invalid key 'fakekey'. No widget found.", $result);
    }

    public function testWidgetWithCallback()
    {
        $app = $this->getApp();
        $app['extensions']->register(new Mock\SnippetCallbackExtension($app));

        $app['extensions']->insertWidget('test', Target::AFTER_JS, "snippetCallBack", "snippetcallback", "", false);
        $html = $app['extensions']->renderWidget('5e4c97cb');
        $this->assertEquals('<meta name="test-snippet" />', $html);
    }

    public function testWidgetWithGlobalCallback()
    {
        $app = $this->getApp();
        $app['extensions']->register(new Mock\SnippetCallbackExtension($app));

        $app['extensions']->insertWidget(
            'testglobal',
            Target::START_OF_BODY,
            "\Bolt\Tests\Extensions\globalWidget",
            "snippetcallback",
            "",
            false
        );
        $html = $app['extensions']->renderWidget('7e2b9a48');
        $this->assertEquals('<meta name="test-widget" />', $html);
    }
}

// function globalSnippet($app, $string)
// {
//     return nl2br($string);
// }

// function globalWidget()
// {
//     return '<meta name="test-widget" />';
// }
