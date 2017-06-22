<?php

namespace Fixtures\Maba\Bundle\TestChildBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MabaTestChildBundle extends Bundle
{
    public function getParent()
    {
        return 'MabaTestParentBundle';
    }
}
