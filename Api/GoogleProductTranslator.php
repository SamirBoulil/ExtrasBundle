<?php

namespace SamirBoulil\Bundle\AutomaticTranslationBundle\Api;

use Google\Cloud\Translate\TranslateClient;

/**
 * Automatically translates product information
 *
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class GoogleProductTranslator implements ProductTranslatorInterface
{
    /** @var TranslateClient * */
    protected $translateClient;

    /**
     * @param TranslateClient $translateClient
     **/
    public function __construct(TranslateClient $translateClient)
    {
        $this->translateClient = $translateClient;
    }

    /**
     * {@inheritdoc}
     */
    public function translateProductValues(
        ProductValueCollection $productValueCollection,
        $fromLocaleCode,
        array $toLocaleCodes,
        array $channelCodes
    ) {
        $translatedValues = [];
        foreach ($productValueCollection as $productValue) {
            foreach ($channelCodes as $channelCode) {
                foreach ($toLocaleCodes as $toLocaleCode) {
                    // Todo: Check for locale specific attributes here

                    if (null !== $productValue &&
                       (null !== $productValue->getData() || '' !== $productValue->getData())
                    ) {
                        $translation = $this->translateClient->translate(
                            $productValue->getData(),
                            [
                                'from'   => substr($fromLocaleCode, 0, 2),
                                'target' => substr($toLocaleCode, 0, 2),
                            ]
                        );

                        // Todo: validation here

                        //  Could also return a product value collection
                        //  But it's easier to return standard format for update
                        $attributeCode = $productValue->getAttributeCode();
                        $translatedValues['values'][$attributeCode][] = [
                            'locale' => $toLocaleCode,
                            'scope'  => $channelCode,
                            'data'   => $translation['text'],
                        ];
                    }
                }
            }
        }

        return $translatedValues;
    }
}
