Cache Info Bundle
====================


This bundle adds a simple command to dump information about HTTP Cache in a Symfony project.

## Installation
```sh
composer require mapado/cache-info-bundle
```

```php
// AppKernel.php
    $bundles = array(
        ...
        new Mapado\CacheInfoBundle\MapadoCacheInfoBundle(),
    );
```

## Usage 
```sh
php app/console mapado:http-cache:list
```

Dumps a list of informations about your cache settings
```sh
+------------------------------------------------------------------------------+----------------+---------+
| route + pattern                                                              | private        | ttl     |
+------------------------------------------------------------------------------+----------------+---------+
| my_route                                                                     | public         | 6h      |
| /my/route                                                                    |                |         |
+------------------------------------------------------------------------------+----------------+---------+
| another route                                                                | public         | 1d      |
| /another/route                                                               |                |         |
+------------------------------------------------------------------------------+----------------+---------+
| a private route                                                              | private        | null    |
| /private                                                                     |                |         |
+------------------------------------------------------------------------------+----------------+---------+
| a complex cache route                                                        | public|private | 4h|null |
| /complex                                                                     |                |         |
+------------------------------------------------------------------------------+----------------+---------+
```

## Cache time definition
By default, this command leverage the power of Sensio Framework bundle `@Cache` annotation.
It works also fine with the route cache definition (for the `FrameworkBundle:Template:template` routes).

### Complex route cache | Manual cache settings
If you have a complex route cache, or if you manually call `$response->setSMaxAge()` and `$response->setPublic()`, you need to use the `@CacheMayBe` annotation.

#### Example
```php
use Mapado\CacheInfoBundle\Annotation\CacheMayBe;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

class FooController
{
    /**
     * @CacheMayBe(values={@Cache(public=true, smaxage="14400"), @Cache(public=false)})
     */
     public function complexRouteAction()
     {
         $isPublic = // determine if your route is public ...

         $response->setPublic($isPublic);
         if ($isPublic) {
             $response->setSMaxAge(14400);
         }

         return $response;
     }
}
```

If you don't do that, the route will be marked as `private`
