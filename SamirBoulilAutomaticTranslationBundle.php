<?php

namespace SamirBoulil\Bundle\AutomaticTranslationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SamirBoulilAutomaticTranslationBundle extends Bundle
{

    public function getParent()
    {
        return 'PimEnrichBundle';
    }
}
