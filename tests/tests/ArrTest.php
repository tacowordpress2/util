<?php

class ArrTest extends PHPUnit\Framework\TestCase
{
  public function testIterable() {
    $this->assertEquals(true,  Arr::iterable([1,2,3]));
    $this->assertEquals(false, Arr::iterable([]));
    $this->assertEquals(false, Arr::iterable(null));
    $this->assertEquals(false, Arr::iterable(new stdClass()));
  }

  public function testMean() {
    $this->assertEquals(2,      Arr::mean([1,2,3]));
    $this->assertEquals(2.5,    Arr::mean([2,3]));
    $this->assertEquals(0,      Arr::mean([1, 0, -1]));
    $this->assertEquals(false,  Arr::mean([]));
  }

  public function testMedian() {
    $this->assertEquals(2,      Arr::median([1,2,3]));
    $this->assertEquals(1,      Arr::median([1,18,-4,80,-10]));
    $this->assertEquals(2.5,    Arr::median([1,2,3,4]));
    $this->assertEquals(4.5,    Arr::median([9,18,-4,80,-10,0]));
    $this->assertEquals(false,  Arr::median([]));
  }

  public function testMode() {
    $this->assertEquals(3,      Arr::mode([1,2,3,3]));
    $this->assertEquals(-10,    Arr::mode([1,18,-4,80,-10]));
    $this->assertEquals(1,      Arr::mode([1]));
    $this->assertEquals(1,      Arr::mode([2,2,1,1]));
    $this->assertEquals(false,  Arr::mode([]));
  }

  public function testWithKeys() {
    $this->assertEquals(3,      Arr::mode([1,2,3,3]));
    $this->assertEquals(-10,    Arr::mode([1,18,-4,80,-10]));
    $this->assertEquals(1,      Arr::mode([1]));
    $this->assertEquals(1,      Arr::mode([2,2,1,1]));
    $this->assertEquals(false,  Arr::mode([]));
  }
}
