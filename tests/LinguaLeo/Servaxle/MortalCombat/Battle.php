<?php

namespace LinguaLeo\Servaxle\MortalCombat;

class Battle
{
    private $fighter1;
    private $fighter2;
    private $arena;

    public function __construct(Fighter $fighter1, Fighter $fighter2, ArenaInterface $arena)
    {
        $this->fighter1 = $fighter1;
        $this->fighter2 = $fighter2;
        $this->arena = $arena;
    }
}