<?php

namespace SamirBoulil\Bundle\AutomaticTranslationBundle\Api;

/**
 * Interface for a product translator
 **/
interface ProductTranslatorInterface
{
    /**
     * Translates a product attributes from the given locale to the given locales with the given TranslateClient.
     *
     * Returns the translated product value collection in the standard format
     *
     * @param ProductValueCollectionInterface $productValueCollection
     * @param LocaleInterface                 $fromLocale
     * @param LocaleInterface                 $toLocales
     * @param array                           $attributeCodes
     * @param array                           $channelCodes
     *
     * @return mixed
     **/
    public function translateProductValues(
        ProductValueCollection $productValueCollection,
        $fromLocaleCode,
        array $toLocaleCodes,
        array $channelCodes
    );
}
