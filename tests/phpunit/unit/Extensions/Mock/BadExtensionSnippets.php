<?php
namespace Bolt\Tests\Extensions\Mock;

use Bolt\Application;
use Bolt\Assets\Target;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class BadExtensionSnippets extends Extension
{
    public function __construct(Application $app)
    {
        $app['assets.queue.snippet']->add(Target::END_OF_HEAD, [$this, 'badSnippetCallBack']);
    }

    public function getSnippets()
    {
//         throw new \Exception("BadExtensionSnippets", 1);
    }

    public function getName()
    {
        return "badextensionsnippets";
    }
}
