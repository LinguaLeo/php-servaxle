<?php

namespace LinguaLeo\Servaxle\MortalCombat\Arena;

use LinguaLeo\Servaxle\MortalCombat\ArenaInterface;

class LiveForest implements ArenaInterface
{
    public function __construct(callable $random)
    {
    }
}