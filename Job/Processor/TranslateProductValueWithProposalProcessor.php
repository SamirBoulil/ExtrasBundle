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

        $actions = $this->getConfiguredActions();

        $channelCodes = $this->getAllChannelCodes();
        $channelCodes[] = null;

        $fromLocale = $this->localeRepository->findOneBy(['code' => $actions['fromLocaleCode']]);
        if (null === $fromLocale) {
            // Skip locale not known
            throw new \InvalidArgumentException('From locale not know');
        }

        foreach ($actions['toLocaleCodes'] as $localeCode) {
            $toLocale = $this->localeRepository->findOneBy(['code' => $localeCode]);
            if (null !== $toLocale) {
                $product = $this->translateProductvaluesFromToLocale(
                    $product,
                    $fromLocale,
                    $toLocale,
                    $actions['attributeCodes'],
                    $channelCodes
                );

                $violations = $this->validator->validate($product);
                if (0 !== count($violations)) {
//        $this->stepExecution->addWarning(
//            'pim_enrich.mass_edit_action.edit_common_attributes.message.error',
//            [],
//            new DataInvalidItem($product)
//        );
//        $this->stepExecution->incrementSummaryInfo('skipped_products');

                }

                $this->productSaver->save($product);

            }
        }

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
     * @param LocaleInterface  $fromLocale
     * @param LocaleInterface  $toLocale
     * @param array            $attributeCodes
     * @param array            $channelCodes
     *
     * @return ProductInterface
     */
    private function translateProductvaluesFromToLocale(
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
                }
            }
        }
    }

    /**
     * @return array
     *
     */
    private function getAllChannelCodes()
    {
        $channels = $this->channelRepository->findAll();

        $channelCodes = [];
        foreach ($channels as $channel) {
            $channelCodes[] = $channel->getCode();
        }

        return $channelCodes;
    }
}
