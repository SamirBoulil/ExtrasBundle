<?php

namespace SamirBoulil\Bundle\AutomaticTranslationBundle\Api;

use Akeneo\Component\StorageUtils\Factory\SimpleFactoryInterface;
use Google\Cloud\Translate\TranslateClient;

/**
 * Instantiates a client API
 *
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class TranslateClientFactory implements SimpleFactoryInterface
{
    /** @var string */
    protected $apiKey;

    /** @var string */
    protected $projectId;

    public function __construct($apiKey, $projectId)
    {
        $this->apiKey = $apiKey;
        $this->projectId = $projectId;
    }

    /**
     * @return object
     */
    public function create()
    {
        if (null === $this->apiKey  || '' === $this->apiKey) {
            throw new \LogicException('API key not provided');
        }

        return new TranslateClient([
            'keyFilePath' => '/Users/Samir/Workspace/akeneo/translation/encore.json'
        ]);
    }
}
