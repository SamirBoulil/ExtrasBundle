<?php

namespace spec\SamirBoulil\Bundle\AutomaticTranslationBundle\Job\Processor;

use Akeneo\Component\Batch\Item\FlushableInterface;
use Akeneo\Component\Batch\Item\InitializableInterface;
use Akeneo\Component\Batch\Item\ItemProcessorInterface;
use Akeneo\Component\Batch\Job\JobParameters;
use Akeneo\Component\StorageUtils\Factory\SimpleFactoryInterface;
use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Akeneo\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Oro\Bundle\UserBundle\Entity\UserManager;
use PhpSpec\ObjectBehavior;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Repository\ChannelRepositoryInterface;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;
use SamirBoulil\Bundle\AutomaticTranslationBundle\Job\Processor\TranslateProductValueWithProposalProcessor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TranslateProductValueWithProposalProcessorSpec extends ObjectBehavior
{
    function let(
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
        $this->beConstructedWith(
            $validator,
            $userManager,
            $authorizationChecker,
            $tokenStorage,
            $translationFactory,
            $productUpdater,
            $channelRepository,
            $localeRepository,
            $productSaver
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(TranslateProductValueWithProposalProcessor::class);
    }

    function it_is_an_initializable_and_flushable_processor()
    {
        $this->shouldImplement(ItemProcessorInterface::class);
        $this->shouldImplement(InitializableInterface::class);
        $this->shouldImplement(FlushableInterface::class);
    }

    function it_should_process_a_product(ProductInterface $product, JobParameters $jobParameters)
    {
//        $jobParameters = $jobParameters->get('actions')->willReturn([
//            'actions'
//        ]);
//        $this->stepExecution->getJobParameters()->willReturn($jobParameters);
    }

    function it_does_not_translate_a_product(){}
}
