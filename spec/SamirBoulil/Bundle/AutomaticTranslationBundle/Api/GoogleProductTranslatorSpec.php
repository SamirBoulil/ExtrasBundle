<?php

namespace spec\SamirBoulil\Bundle\AutomaticTranslationBundle\Api;

use Google\Cloud\Translate\TranslateClient;
use SamirBoulil\Bundle\AutomaticTranslationBundle\Api\GoogleProductTranslator;
use PhpSpec\ObjectBehavior;
use SamirBoulil\Bundle\AutomaticTranslationBundle\Api\ProductTranslatorInterface;

class GoogleProductTranslatorSpec extends ObjectBehavior
{
    function let(TranslateClient $translateClient)
    {
        $this->beConstructedWith($translateClient);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(GoogleProductTranslator::class);
    }

    function it_is_a_product_translator()
    {
        $this->shouldImplement(ProductTranslatorInterface::class);
    }

    function it_translates_a_product_value_collection(
        $translateClient,
        ProductValueCollection $productValueCollection
    ) {

    }

    function it_does_not_translate_an_empty_collection(
        ProductValueCollection $productValueCollection
    ) {
    }
}
