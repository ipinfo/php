<?php

namespace jhtimmins\ipinfo;
use jhtimmins\ipinfo\Details;

class DetailsTest extends \PHPUnit\Framework\TestCase
{
    public function testLookupAll()
    {
        $raw_details = ['country' => 'United States'];
        $details = new Details($raw_details);

        $this->assertSame($raw_details, $details->all);
    }

    public function testLookupSpecificExists()
    {
      $raw_details = ['country' => 'United States', 'country_code' => 'US'];
      $details = new Details($raw_details);

      $this->assertTrue(property_exists($details, 'country'));
      $this->assertTrue(property_exists($details, 'country_code'));
      $this->assertSame($raw_details['country'], $details->country);
      $this->assertSame($raw_details['country_code'], $details->country_code);
    }

    public function testLookupSpecificDoesNotExist()
    {
      $raw_details = [];
      $details = new Details($raw_details);

      $this->assertFalse(property_exists($details, 'country'));
    }
}
