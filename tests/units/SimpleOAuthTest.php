<?php

class SimpleOAuthTest extends PHPUnit_Framework_TestCase
{

    public $oauth;

    public function setUp()
    {
        $this->oauth = new \Perecedero\SimpleOAuth\SimpleOAuth();
    }

    public function tearDown()
    {
        $this->oauth = NULL;
    }

}
