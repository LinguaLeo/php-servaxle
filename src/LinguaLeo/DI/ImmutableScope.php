<?php

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 LinguaLeo
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace LinguaLeo\DI;

class ImmutableScope extends Scope
{
    /**
     * Returns a value without tokenization.
     *
     * @param mixed $id
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getValue($id)
    {
        if (!isset($this->values[$id])) {
            throw new \InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }
        $value = $this->values[$id];
        $isFactory = is_object($value) && method_exists($value, '__invoke');
        return $isFactory ? $value($this, $id) : $value;
    }

    /**
     * Compiles values into php script.
     *
     * @param array $values
     * @return string
     */
    public function compile()
    {
        $script = '['.PHP_EOL;
        foreach ($this->values as $id => $value) {
            if (class_exists($id) || interface_exists($id)) {
                continue;
            }
            $script .= "'$id' => ".$this->tokenize($id)->getBinding().', // '.$value.PHP_EOL;
        }
        return $script.']';
    }
}