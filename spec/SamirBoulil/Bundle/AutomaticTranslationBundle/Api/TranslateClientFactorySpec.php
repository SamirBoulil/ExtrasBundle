<?php

namespace spec\SamirBoulil\Bundle\AutomaticTranslationBundle\Api;

use PhpSpec\ObjectBehavior;
use SamirBoulil\Bundle\AutomaticTranslationBundle\Api\TranslateClientFactory;

class TranslateClientFactorySpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('projectFilePath');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(TranslateClientFactory::class);
    }
}
