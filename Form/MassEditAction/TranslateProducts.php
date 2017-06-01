<?php

namespace SamirBoulil\Bundle\AutomaticTranslationBundle\Form\MassEditAction;

use Pim\Bundle\EnrichBundle\Doctrine\ORM\Repository\LocaleRepository;
use Pim\Bundle\EnrichBundle\MassEditAction\Operation\AbstractMassEditOperation;
use Pim\Component\Catalog\AttributeTypes;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Model\LocaleInterface;
use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;

/**
 * Translate products
 *
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class TranslateProducts extends AbstractMassEditOperation
{
    /** @var AttributeRepositoryInterface */
    protected $attributeRepository;

    /** @var LocaleRepository */
    protected $localeRepository;

    /** @var array */
    protected $localizableAttributes;

    /** @var array */
    protected $locales;

    /** @var string */
    protected $originLocale;

    /**
     * @param string                       $jobInstanceCode
     * @param AttributeRepositoryInterface $attributeRepository
     * @param LocaleRepository             $localeRepository
     */
    public function __construct(
        $jobInstanceCode,
        AttributeRepositoryInterface $attributeRepository,
        LocaleRepository $localeRepository
    ) {
        parent::__construct($jobInstanceCode);

        $this->localeRepository = $localeRepository;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperationAlias()
    {
        return 'translate_products';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return 'samirboulil_extras_mass_translate_products';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getActions()
    {
        return [
            'originLocaleCode' => $this->originLocale,
            'localeCodes'      => $this->locales,
            'attributeCodes'   => $this->localizableAttributes,
        ];
    }

    /**
     * Returns all activated locale codes
     *
     * @return array
     */
    public function getLocalesCode()
    {
        return $this->localeRepository->findBy(['activated' => true]);
    }

    /**
     * Find every localizableAttributes
     *
     * @return AttributeInterface
     */
    public function getLocalizableAttributes()
    {
        $attributes = $this->attributeRepository->findBy(['localizable' => true]);

        $attributeList = [];
        foreach ($attributes as $attribute) {
            if ($attribute->getType() === AttributeTypes::TEXT) {
                $attributeList[] = $attribute;
            }
        }

        return $attributeList;
    }

    /**
     * @param array $localizableAttributes
     *
     * @return TranslateProducts
     */
    public function setLocalizableAttributes(array $localizableAttributes)
    {
        $localizableAttributes = $localizableAttributes['attributes'];

        $this->localizableAttributes = [];
        foreach ($localizableAttributes as $localizableAttribute) {
            $this->localizableAttributes[] = $localizableAttribute->getCode();
        }

        return $this;
    }

    /**
     * @param array $locales
     *
     * @return TranslateProducts
     */
    public function setLocales($locales)
    {
        $this->locales = [];

        foreach ($locales as $locale) {
            $this->locales[] = $locale->getCode();
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * @return string
     */
    public function getOriginLocale()
    {
        return $this->originLocale;
    }

    /**
     * @param LocaleInterface $originLocale
     */
    public function setOriginLocale(LocaleInterface $originLocale)
    {
        $this->originLocale = $originLocale->getCode();
    }

}
