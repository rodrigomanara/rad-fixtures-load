<?php

namespace spec\Knp\Rad\FixturesLoad\FixturesFactory;

use Doctrine\Common\Persistence\ObjectManager;
use PhpSpec\ObjectBehavior;

class PersisterFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Knp\Rad\FixturesLoad\FixturesFactory');
    }

    function it_returns_a_fixtures_instance(ObjectManager $om)
    {
        if (false === interface_exists('Nelmio\Alice\PersisterInterface')) {
            return;
        }

        $this->create($om)->shouldHaveType('Nelmio\Alice\Fixtures');
        $this->create($om, 'fr')->shouldHaveType('Nelmio\Alice\Fixtures');
    }
}
