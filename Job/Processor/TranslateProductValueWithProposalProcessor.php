<?php

namespace SamirBoulil\Bundle\AutomaticTranslationBundle\Job\Processor;

use Akeneo\Component\Batch\Item\FlushableInterface;
use Akeneo\Component\Batch\Item\InitializableInterface;
use Akeneo\Component\StorageUtils\Factory\SimpleFactoryInterface;
use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Akeneo\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Google\Cloud\Translate\TranslateClient;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Pim\Bundle\EnrichBundle\Connector\Processor\AbstractProcessor;
use Pim\Component\Catalog\Model\LocaleInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Repository\ChannelRepositoryInterface;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Processor to remove product value but check if the user has right to mass edit the product (if he is the owner).
 *
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class TranslateProductValueWithProposalProcessor extends AbstractProcessor implements
    InitializableInterface,
    FlushableInterface
{
    /** @var ValidatorInterface */
    protected $validator;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var UserManager */
    protected $userManager;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var SimpleFactoryInterface */
    protected $translationFactory;

    /** @var TranslateClient */
    protected $translationClient;

    /** @var ObjectUpdaterInterface */
    protected $productUpdater;

    /** @var ChannelRepositoryInterface */
    protected $channelRepository;

    /** @var LocaleRepositoryInterface */
    protected $localeRepository;

    /** @var SaverInterface */
    protected $productSaver;

    /**
     * @param ValidatorInterface            $validator
     * @param UserManager                   $userManager
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     * @param SimpleFactoryInterface        $translationFactory
     * @param ObjectUpdaterInterface        $productUpdater
     * @param ChannelRepositoryInterface    $channelRepository
     * @param LocaleRepositoryInterface     $localeRepository
     * @param ObjectUpdaterInterface        $productUpdater
     * @param SaverInterface                $productSaver
     */
    public function __construct(
        ValidatorInterface $validator,
        UserManager $userManager,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        SimpleFactoryInterface $translationFactory,
        ObjectUpdaterInterface $productUpdater,
        ChannelRepositoryInterface $channelRepository,
        LocaleRepositoryInterface $localeRepository,
        SaverInterface $productSaver
    ) {
        $this->validator = $validator;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->userManager = $userManager;
        $this->translationFactory = $translationFactory;
        $this->productUpdater = $productUpdater;
        $this->translationClient = null;
        $this->channelRepository = $channelRepository;
        $this->localeRepository = $localeRepository;
        $this->productUpdater = $productUpdater;
        $this->productSaver = $productSaver;
    }

    /**
     * We override parent to initialize the security context
     *
     * @param ProductInterface $product
     *
     * @return mixed|null
     */
    public function process($product)
    {
//        $username = $this->stepExecution->getJobExecution()->getUser();
//        $user = $this->userManager->findUserByUsername($username);

//        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
//        $this->tokenStorage->setToken($token);

//        if ($this->authorizationChecker->isGranted(Attributes::OWN, $product)) {
//            $actions = $this->getConfiguredActions();
//            var_dump($actions);
//            $this->translateProduct($product, $actions);
//        }

        $actions = $this->getConfiguredActions();
        $this->translateProduct($product, $actions);
//        $this->stepExecution->addWarning(
//            'pim_enrich.mass_edit_action.edit_common_attributes.message.error',
//            [],
//            new DataInvalidItem($product)
//        );
//        $this->stepExecution->incrementSummaryInfo('skipped_products');

        return null;
    }

    /**
     * Custom logic on step initialization.
     */
    public function initialize()
    {
        $this->translationClient = $this->translationFactory->create();
    }

    /**
     * Custom logic on step completion.
     */
    public function flush()
    {
        $this->translationClient = null;
    }

    /**
     * @param ProductInterface $product
     * @param array            $actions
     */
    protected function translateProduct(ProductInterface $product, $actions)
    {
        $channels = $this->channelRepository->findAll();

        $channelCodes = [];
        foreach ($channels as $channel) {
            $channelCodes[] = $channel->getCode();
        }

        $channelCodes[] = null;

        $fromLocale = $this->localeRepository->findOneBy(['code' => $actions['originLocaleCode']]);
        if (null === $fromLocale) {
            throw new \InvalidArgumentException('From locale not know');
        }

        foreach ($actions['localeCodes'] as $localeCode) {
            $toLocale = $this->localeRepository->findOneBy(['code' => $localeCode]);
            $this->translateProductvalueFromToLocale(
                $product,
                $fromLocale,
                $toLocale,
                $actions['attributeCodes'],
                $channelCodes
            );
        }
    }

    /**
     * @param ProductInterface $product
     * @param LocaleInterface  $fromLocale
     * @param LocaleInterface  $toLocale
     * @param array            $attributeCodes
     * @param array            $channelCodes
     */
    protected function translateProductvalueFromToLocale(
        ProductInterface $product,
        LocaleInterface $fromLocale,
        LocaleInterface $toLocale,
        array $attributeCodes,
        array $channelCodes
    ) {
        foreach ($attributeCodes as $attributeCode) {
            foreach ($channelCodes as $channelCode) {
                $productValue = $product->getValue($attributeCode, $fromLocale->getCode(), $channelCode);

                if (null !== $productValue && (null !== $productValue->getData() || '' !== $productValue->getData())) {
                    $translation = $this->translationClient->translate(
                        $productValue->getData(),
                        [
                            'from'   => substr($fromLocale->getCode(), 0, 2),
                            'target' => substr($toLocale->getCode(), 0, 2),
                        ]
                    );

                    $this->productUpdater->update(
                        $product,
                        [
                            'values' => [
                                $attributeCode => [
                                    [
                                        'locale' => $toLocale->getCode(),
                                        'scope'  => $channelCode,
                                        'data'   => $translation['text'],
                                    ],
                                ],
                            ],
                        ]
                    );
                    $this->productSaver->save($product);
                }
            }
        }
    }

    /**
     * @param ProductInterface $product
     * @param array            $attributeCodes
     * @param                  $localeCode
     * @param                  $channelCodes
     *
     * @return array
     */
    protected function getProductValuesForLocale(
        ProductInterface $product,
        array $attributeCodes,
        $localeCode,
        array $channelCodes
    ) {
        $productValuesToLocale = [];
        foreach ($attributeCodes as $attributeCode) {
            foreach ($channelCodes as $channel) {
                $channelCode = null;
                if ($channel !== null) {
                    $channelCode = $channel->getCode();
                }
                $pv = $product->getValue($attributeCode, $localeCode, $channelCode);
                if (null !== $pv) {
                    $productValuesToLocale[] = $pv;
                }
            }
        }

        return $productValuesToLocale;
    }

    private function udpateProductsWithTranslations(ProductInterface $product, $translations)
    {
        foreach ($translations as $translation) {
            $this->productUpdater->update(
                $product,
                [
                    'values' => [
                        $translation['attributeCode'] => [
                            [
                                'locale' => $translation['locale'],
                                'scope'  => $translation['scope'],
                                'data'   => $translation['translation']['text'],
                            ],
                        ],
                    ],
                ]
            );
            $this->productSaver->save($product);
        }
    }
}
