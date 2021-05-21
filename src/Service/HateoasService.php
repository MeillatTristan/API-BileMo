<?php


namespace App\Service;


use Hateoas\HateoasBuilder;
use JMS\Serializer\SerializationContext;

class HateoasService
{
    public function serializeHypermedia($data, $groups)
    {
        $hateoas = HateoasBuilder::create()->build();

        $json = $hateoas->serialize($data, 'json', SerializationContext::create()->setGroups(array($groups)));

        return $json;
    }

}