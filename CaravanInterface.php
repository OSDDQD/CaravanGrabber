<?php
namespace Core\GrabberBundle\Caravan;

interface CaravanInterface
{
    public function getSourceURL();
    public function getCaravanInfo();
    public function setCaravanInfo($data);
    public function getMap();
}
