<?php

namespace SamirBoulil\Bundle\AutomaticTranslationBundle\Form\Type;

use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type of the Translation of products
 *
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class TranslateProductsType extends AbstractType
{
    /** @var string */
    protected $dataClass;

    /** @var string */
    protected $localeClass;

    /** @var LocaleRepositoryInterface */
    protected $localeRepository;

    /**
     * @param LocaleRepositoryInterface $localeRepository
     * @param string                    $dataClass
     * @param string                    $localeClass
     */
    public function __construct(LocaleRepositoryInterface $localeRepository, $dataClass, $localeClass)
    {
        $this->localeRepository = $localeRepository;
        $this->dataClass = $dataClass;
        $this->localeClass = $localeClass;
    }

    /**
     * Builds a form having a Locale Selector and attributes selector
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'originLocale',
            'entity',
            [
                'required'      => true,
                'multiple'      => false,
                'select2'       => true,
                'class'         => $this->localeClass,
                'query_builder' => function (LocaleRepositoryInterface $repository) {
                    return $repository->getActivatedLocalesQB();
                },
            ]
        );

        $builder->add(
            'locales',
            'entity',
            [
                'required'      => true,
                'multiple'      => true,
                'select2'       => true,
                'class'         => $this->localeClass,
                'query_builder' => function (LocaleRepositoryInterface $repository) {
                    return $repository->getActivatedLocalesQB();
                },
            ]
        );

        // TODO: How does this form type works to pass onto it a custom attribute list ?
        $builder->add(
            'localizableAttributes',
            'pim_available_attributes',
            [
                'data_class' => null,
            ]
        );
    }


    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'samirboulil_extras_mass_translate_products';
    }
}
