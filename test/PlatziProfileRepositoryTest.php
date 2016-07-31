<?php

require_once '../vendor/autoload.php';
require_once '../vendor/simpletest/simpletest/autorun.php';

class PlatziProfileRepositoryTest extends UnitTestCase {

    function testFind() {
        $client = new \Goutte\Client;
        $platziProfileRepository = new \App\PlatziProfileRepository($client);

        $this->assertFalse($platziProfileRepository->find());
        $this->assertFalse($platziProfileRepository->find('invalidusername'));
        $this->assertTrue($platziProfileRepository->find('freddier'));
    }
}
