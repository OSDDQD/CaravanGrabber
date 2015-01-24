<?php
namespace Core\GrabberBundle\Caravan;

class CaravanGrabber
{
    public $caravan;

    public function  __construct (Caravan $caravan)
    {
        $this->caravan = $caravan;
    }

    public function grab()
    {
      return $this->caravan->setData();
    }
}
