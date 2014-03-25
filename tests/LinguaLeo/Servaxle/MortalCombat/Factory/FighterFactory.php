<?php

namespace LinguaLeo\Servaxle\MortalCombat\Factory;

use LinguaLeo\Servaxle\MortalCombat\Fighter;
use LinguaLeo\Servaxle\MortalCombat\DebugFighter;

class FighterFactory
{
    private $isDebug;
    private $name;

    public function __construct($isDebug, $name)
    {
        $this->isDebug = $isDebug;
        $this->name = $name;
    }

    public function __invoke()
    {
        if ($this->isDebug) {
            return new DebugFighter($this->name);
        } else {
            return new Fighter($this->name);
        }
    }
}