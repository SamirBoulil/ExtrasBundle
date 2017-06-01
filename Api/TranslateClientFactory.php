<?php

namespace SamirBoulil\Bundle\AutomaticTranslationBundle\Api;

use Akeneo\Component\StorageUtils\Factory\SimpleFactoryInterface;
use Google\Cloud\Translate\TranslateClient;

/**
 * Instantiates a client API to talk with
 *
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class TranslateClientFactory implements SimpleFactoryInterface
{
    /** @var string */
    protected $apiKeyFilePath;

    /**
     * They key is a file generated from the Google webservices.
     *
     * @param $apiKeyFilePath
     */
    public function __construct($apiKeyFilePath)
    {
        $this->apiKeyFilePath = $apiKeyFilePath;
    }

    /**
     * @return TranslateClient
     */
    public function create()
    {
        if (null === $this->apiKeyFilePath  || '' === $this->apiKeyFilePath) {
            throw new \LogicException('API key not provided');
        }

        return new TranslateClient([
            'keyFilePath' => $this->apiKeyFilePath,
//            'keyFilePath' => '/Users/Samir/Workspace/akeneo/translation/encore.json'
        ]);
    }
}
