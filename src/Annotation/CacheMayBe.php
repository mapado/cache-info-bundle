<?php

namespace Mapado\CacheInfoBundle\Annotation;

/**
 * Class CacheMayBe
 * @author Julien Deniau <julien.deniau@mapado.com>
 *
 * @Annotation
 * @Target("METHOD")
 */
class CacheMayBe
{
    /**
     * values
     *
     * @var array<Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache>
     * @access public
     */
    public $values;

    public function isPublic()
    {
        return array_map(
            function ($cache) {
                return $cache->isPublic();
            },
            $this->values
        );
    }

    public function getSMaxAge()
    {
        return array_map(
            function ($cache) {
                return $cache->getSMaxAge();
            },
            $this->values
        );
    }
}
