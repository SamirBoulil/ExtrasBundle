<?php

namespace SamirBoulil\Bundle\AutomaticTranslationBundle\Job\Processor;

use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Akeneo\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Pim\Bundle\EnrichBundle\Connector\Processor\AbstractProcessor;
use Pim\Component\Api\Repository\AttributeRepositoryInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Repository\ChannelRepositoryInterface;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;
use SamirBoulil\Bundle\AutomaticTranslationBundle\Api\ProductTranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Processor to remove product value but check if the user has right to mass edit the product (if he is the owner).
 *
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class TranslateProductValueCollectionProcessor extends AbstractProcessor
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var UserManager */
    protected $userManager;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var ChannelRepositoryInterface */
    protected $channelRepository;

    /** @var LocaleRepositoryInterface */
    protected $localeRepository;

    /** @var AttributeRepositoryInterface */
    protected $attributeRepository;

    /** @var ProductTranslatorInterface * */
    protected $productTranslator;

    /** @var ObjectUpdaterInterface */
    protected $productUpdater;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var SaverInterface */
    protected $productSaver;

    /**
     * @param UserManager $userManager
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     * @param ChannelRepositoryInterface $channelRepository
     * @param LocaleRepositoryInterface $localeRepository
     * @param AttributeRepositoryInterface $attributeRepository
     * @param ProductTranslatorInterface $productTranslator
     * @param ObjectUpdaterInterface $productUpdater
     * @param ValidatorInterface $validator
     * @param SaverInterface $productSaver
     */
    public function __construct(
        UserManager $userManager,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        ChannelRepositoryInterface $channelRepository,
        LocaleRepositoryInterface $localeRepository,
        AttributeRepositoryInterface $attributeRepository,
        ProductTranslatorInterface $productTranslator,
        ObjectUpdaterInterface $productUpdater,
        ValidatorInterface $validator,
        SaverInterface $productSaver
    ) {
        $this->userManager = $userManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->channelRepository = $channelRepository;
        $this->localeRepository = $localeRepository;
        $this->attributeRepository = $attributeRepository;
        $this->productTranslator = $productTranslator;
        $this->productUpdater = $productUpdater;
        $this->validator = $validator;
        $this->productSaver = $productSaver;
    }

    /**
     * We override parent to initialize the security context
     *
     * @param ProductInterface $product
     *
     * @throws
     *
     * @return mixed|null
     */
    public function process($product)
    {
        // Todo: Check for user permissions
//      $username = $this->stepExecution->getJobExecution()->getUser();
//      $user = $this->userManager->findUserByUsername($username);
//      $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
//      $this->tokenStorage->setToken($token);

        $actions = $this->getConfiguredActions();
        $fromLocaleCode = $actions['from_locale'];
        $toLocaleCodes = $actions['to_locales'];
        $channelCodes = $actions['channel_codes'];
        $attributeCodes = $actions['attribute_codes'];

        $this->CheckLocaleCodes([$fromLocaleCode]);
        $this->CheckLocaleCodes($toLocaleCodes);
        $this->checkChannelCodes($channelCodes);
        $this->checkAttributeCodes($attributeCodes);

        $toTranslateProductValueCollection = $this->getProductValuesToTranslate($product->getValues());

        $standardTranslatedValues = $this->productTranslator->translateProductValues(
            $toTranslateProductValueCollection,
            $fromLocaleCode,
            $toLocaleCodes,
            $channelCodes
        );

        $this->productUpdater->update($product, $standardTranslatedValues);

        return $product;
    }

    /**
     * Checks that a locale exists and is activated for the given code.
     *
     * @param array $localeCodes
     *
     * @throws
     */
    private function checkLocaleCodes(array $localeCodes)
    {
        foreach ($localeCodes as $localeCode) {
            $locale = $this->localeRepository->findOneBy(['code' => $localeCode])
            if (null === $locale) {
                // Todo: Throw error
            }
            if (false === $locale->isActivated()) {
                // Todo: Throw error
            }
        }
    }

    /**
     * Checks the given channels exists and are activated.
     *
     * @param array $channelCodes
     *
     * @throws
     */
    private function checkChannelCodes(array $channelCodes)
    {
        foreach ($channelCodes as $channelCode) {
            $channel = $this->channelRepository->findOneByCode($channelCode);
            if (null === $channel) {
                // Todo: Throw error
            }
            if (false === $channel->isActivated()) {
                // Todo: Throw error
            }
        }
    }

    /**
     * Checks the given attributes exists and are localizable.
     *
     * @param array $attributeCodes
     *
     * @throws
     */
    private function checkAttributeCodes(array $attributeCodes)
    {
        foreach ($attributeCodes as $attributeCode) {
            $attribute = $this->attributeRepository->findOneByCode($attributeCode);
            if (null === $attribute) {
                // Todo: Throw error
            }

            if (false === $attribute->isLocalizable()) {
                // Todo: Throw error
            }
        }
    }

    /**
     * Returns a subset of the product value collection containing only product values
     * that should be translated
     *
     * @param ProductValueCollectionInterface $ProductValueCollection
     * @param array                           $attributecodes
     **/
    private function getProductValuesToTranslate(
        ProductValueCollectionInterface $productValueCollection,
        array $attributeCodes
    ) {
        $toTranslateProductValue = [];

        foreach ($productValueCollection as $productValue) {
            $attribute = $productValue->getAttribute();
            if (!in_array($attribute->getCode(), $attributeCodes)) {
                $toTranslateProductValue[] = $productValue;
            }
        }

        return $toTranslateProductValue;
    }
}
