<?php

namespace Mapado\CacheInfoBundle\Command;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class HttpCacheListCommand
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class HttpCacheListCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mapado:http-cache:list')
            ->setDescription('List http cache informations')
            ->addOption('only-public', null, InputOption::VALUE_NONE, 'Show only public cache routes')
            ->addArgument('route', InputArgument::OPTIONAL, 'The route name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<comment>Watch out: only @Cache annotation and cache defined in routing are showned here.\nIf you set manually the cache via \$response->setPublic(true), it will appear here as private</comment>");
        $output->writeln("<comment>If so, you may want to use the @CacheMayBe annotation</comment>");

        $routes = $this->getContainer()->get('router')->getRouteCollection();

        $table = new Table($output);
        $table->setHeaders(['route + pattern', 'private', 'ttl']);

        $rows = [];
        $first = true;
        foreach ($routes as $route => $routeInfo) {
            $this->route = $route;
            $isPublic = $this->isRoutePublic($routeInfo);
            $hidePrivate = $input->getOption('only-public');
            $forcedRoute = $input->getArgument('route');
            $sameAsForcedRoute = $input->getArgument('route') === $route;
            if ($forcedRoute && $sameAsForcedRoute || !$forcedRoute && ($isPublic || !$hidePrivate)) {
                if ($first) {
                    $first = false;
                } else {
                    $rows[] = new TableSeparator();
                }

                $rows[] = [
                    '<info>' . $route. "</info>\n" .
                    $routeInfo->getPattern(),
                    $this->routePublicText($routeInfo),
                    $this->formatTtl($this->getTtl($routeInfo)),
                ];
            }
        }
        $table->setRows($rows);
        $table->render();
    }

    /**
     * isRoutePublic
     *
     * @param mixed $routeInfo
     * @access private
     * @return boolean
     */
    private function isRoutePublic($routeInfo)
    {
        if ($routeInfo->getDefault('private') === false) {
            return true;
        }
        $cacheInfo = $this->getCacheAnnotation($routeInfo);

        if ($cacheInfo) {
            return $cacheInfo->isPublic();
        }
    }

    /**
     * routePublicText
     *
     * @param Route $routeInfo
     * @access private
     * @return string
     */
    private function routePublicText($routeInfo)
    {
        $publicInfo = $this->isRoutePublic($routeInfo);
        if (is_array($publicInfo)) {
            $out = [];
            if (!empty(array_filter($publicInfo))) {
                $out[] = 'public';
            }
            if (!empty(array_filter($publicInfo, function ($item) {
                return !$item;
            }))) {
                $out[] = 'private';
            }

            return implode('|', $out);
        }

        return $publicInfo ? 'public' : 'private';
    }

    private function getTtl($routeInfo)
    {
        if ($routeInfo->getDefault('sharedAge')) {
            return $routeInfo->getDefault('sharedAge');
        }

        $cacheInfo = $this->getCacheAnnotation($routeInfo);

        if ($cacheInfo) {
            return $cacheInfo->getSMaxAge();
        }
    }

    private function getCacheAnnotation($routeInfo)
    {
        try {
            if (strpos($routeInfo->getDefault('_controller'), '::') !== false) {
                list($controller, $method) = explode('::', $routeInfo->getDefault('_controller'));

                $reader = new \Doctrine\Common\Annotations\AnnotationReader();
                $reflClass = new \ReflectionMethod($controller, $method);
                return $reader->getMethodAnnotation($reflClass, 'Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache') ?:
                    $reader->getMethodAnnotation($reflClass, 'Mapado\CacheInfoBundle\Annotation\CacheMayBe');
            }
        } catch (\InvalidArgumentException $e) {
        }
    }

    /**
     * formatTtl
     *
     * @param int $ttl
     * @access private
     * @return string
     */
    private function formatTtl($ttl)
    {
        if (is_array($ttl)) {
            return implode('|', array_map([$this, 'formatTtl'], $ttl));
        }

        if ($ttl >= 86400) {
            return round($ttl / 86400, 2) . 'd';
        } elseif ($ttl >= 3600) {
            return round($ttl / 3600, 2) . 'h';
        } elseif ($ttl >= 60) {
            return round($ttl / 60, 2) . 'm';
        } elseif ($ttl > 0) {
            return $ttl . 's';
        } else {
            return 'null';
        }
    }
}
