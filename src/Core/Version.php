<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Core;

class Version
{
    /**
     * @var string
     */
    private $number = '4.5.0-dev.1';

    /**
     * @var string
     */
    private $name = 'Symphoniae-Quattuor';

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}