<?php

namespace LinguaLeo\Servaxle\MortalCombat\Arena;

use LinguaLeo\Servaxle\MortalCombat\ArenaInterface;

class LiveForest implements ArenaInterface
{
    private $season;

    public function __construct($season)
    {
        $this->season = $season;
    }

    public function getSeason()
    {
        return $this->season;
    }
}