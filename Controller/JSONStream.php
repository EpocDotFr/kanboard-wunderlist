<?php

// Fast streaming JSON parser for PHP
// Author: Vitaliy Filippov, 2018+
// License: GNU LGPL 3.0 or MPL (file-level copyleft)

class JSONStreamException extends Exception {}

class JSONStream
{
    const OBJ = 1;
    const ARR = 2;

    protected static $esc = [
        '"' => '"',
        "\\" => "\\",
        '/' => '/',
        'b' => "\b",
        'f' => "\f",
        'n' => "\n",
        'r' => "\r",
        't' => "\t",
    ];

    protected $buffer = '';
    protected $totalOffset = 0;
    protected $offset = 0;
    protected $in = []; // [ [ 0 => extern or eof, 1 => type, 2 => value, 3 => current hash key ] ]
    protected $read;

    public function __construct($read)
    {
        $this->read = $read;
    }

    public function enterObject()
    {
        $this->skipWhitespace();
        if ($this->buffer === '')
        {
            throw new JSONStreamException('unexpected end of stream at offset '.($this->totalOffset + $this->offset));
        }
        if (($c = $this->buffer[$this->offset]) !== '{')
        {
            throw new JSONStreamException('unexpected token at offset '.($this->totalOffset + $this->offset).': '.$c);
        }
        $this->offset++;
        $this->skipWhitespace();
        $st = $this->buffer[$this->offset] === '}' ? 2 : 1;
        $this->in[] = [ $st, self::OBJ, NULL, false ];
    }

    public function enterArray()
    {
        $this->skipWhitespace();
        if ($this->buffer === '')
        {
            throw new JSONStreamException('unexpected end of stream at offset '.($this->totalOffset + $this->offset));
        }
        if (($c = $this->buffer[$this->offset]) !== '[')
        {
            throw new JSONStreamException('unexpected token at offset '.($this->totalOffset + $this->offset).': '.$c);
        }
        $this->offset++;
        $this->skipWhitespace();
        $st = $this->buffer[$this->offset] === ']' ? 2 : 1;
        $this->in[] = [ $st, self::ARR, NULL, false ];
    }

    public function exitObject()
    {
        if (!$this->isEnded())
        {
            throw new JSONStreamException('object not ended yet');
        }
        if ($this->in[count($this->in)-1][1] != self::OBJ)
        {
            throw new JSONStreamException('not inside object');
        }
        array_pop($this->in);
        if ($this->in)
        {
            $this->pushValue($v);
        }
    }

    public function exitArray()
    {
        if (!$this->isEnded())
        {
            throw new JSONStreamException('array not ended yet');
        }
        if ($this->in[count($this->in)-1][1] != self::ARR)
        {
            throw new JSONStreamException('not inside array');
        }
        array_pop($this->in);
        if ($this->in)
        {
            $this->pushValue($v);
        }
    }

    public function isEnded()
    {
        return !$this->in || $this->in[count($this->in)-1][0] == 2;
    }

    public function readValue(&$value)
    {
        if ($this->isEnded())
        {
            // Refuse to read anything until exitObject() / exitArray()
            return false;
        }
        $n = count($this->in);
        do
        {
            $v = $this->readToken();
        } while (count($this->in) > $n);
        $value = $v;
        return ($this->in[count($this->in)-1][0] == 1);
    }

    public function unreadBuffer()
    {
        $this->totalOffset += strlen($this->buffer);
        $s = $this->buffer;
        $this->buffer = '';
        $this->offset = 0;
        return $s;
    }

    public function restart()
    {
        $this->totalOffset = 0;
        $this->buffer = '';
        $this->offset = 0;
        $this->in = [];
    }

    protected function read()
    {
        $r = $this->read;
        return $r();
    }

    protected function skipWhitespace()
    {
        while (true)
        {
            if ($this->offset == strlen($this->buffer))
            {
                $this->totalOffset += strlen($this->buffer);
                $this->buffer = $this->read();
                $this->offset = 0;
            }
            $c = $this->buffer[$this->offset];
            if (ctype_space($c))
            {
                $m = NULL;
                preg_match('/\s+/sA', $this->buffer, $m, 0, $this->offset);
                if (!$m)
                {
                    return;
                }
                $this->offset += strlen($m[0]);
            }
            else
            {
                return;
            }
        }
    }

    protected function readToken()
    {
        $this->skipWhitespace();
        if ($this->buffer === '')
        {
            throw new JSONStreamException('unexpected end of stream at offset '.($this->totalOffset + $this->offset));
        }
        $c = $this->buffer[$this->offset];
        if ($c === '[')
        {
            $this->offset++;
            $this->skipWhitespace();
            if ($this->buffer[$this->offset] === ']')
            {
                $this->offset++;
                $v = [];
            }
            else
            {
                $this->in[] = [ 0, self::ARR, [], NULL ];
                return NULL;
            }
        }
        elseif ($c === '{')
        {
            $this->offset++;
            $this->skipWhitespace();
            if ($this->buffer[$this->offset] === '}')
            {
                $this->offset++;
                $v = [];
            }
            else
            {
                $this->in[] = [ 0, self::OBJ, [], NULL ];
                return NULL;
            }
        }
        elseif ($c === '"')
        {
            $this->offset++;
            $v = '';
            if ($this->offset >= strlen($this->buffer)-6)
            {
                $this->buffer .= $this->read();
            }
            while (preg_match('/(?:[^"\\\\]+|\\\\[\\\\"\/bfnrt]|(?:\\\\u[0-9a-fA-F]{4})+)/As', $this->buffer, $m, 0, $this->offset))
            {
                $this->offset += strlen($m[0]);
                if ($m[0][0] == "\\")
                {
                    if ($m[0] == 'u')
                        $v .= mb_convert_encoding(pack('H*', str_replace('\\u', '', $m[0])), 'UTF-8', 'UTF-16BE');
                    else
                    {
                        $v .= self::$esc[$m[0][1]];
                    }
                }
                else
                {
                    $v .= $m[0];
                }
                if ($this->offset >= strlen($this->buffer)-6)
                {
                    $this->buffer .= $this->read();
                }
            }
            if (($m = substr($this->buffer, $this->offset, 1)) != '"')
            {
                if ($m)
                    throw new JSONStreamException('unexpected token at offset '.($this->totalOffset + $this->offset).': '.$m);
                else
                    throw new JSONStreamException('unexpected end of stream at offset '.($this->totalOffset + $this->offset));
            }
            else
            {
                $this->offset++;
            }
        }
        elseif ($c === 't')
        {
            if ($this->offset >= strlen($this->buffer)-5)
            {
                $this->buffer .= $this->read();
            }
            if (substr($this->buffer, $this->offset, 4) !== 'true')
            {
                throw new JSONStreamException('unexpected token at offset '.($this->totalOffset + $this->offset).': '.$c);
            }
            $this->offset += 4;
            $v = true;
        }
        elseif ($c === 'f')
        {
            if ($this->offset >= strlen($this->buffer)-6)
            {
                $this->buffer .= $this->read();
            }
            if (substr($this->buffer, $this->offset, 5) !== 'false')
            {
                throw new JSONStreamException('unexpected token at offset '.($this->totalOffset + $this->offset).': '.$c);
            }
            $this->offset += 5;
            $v = false;
        }
        elseif ($c === 'n')
        {
            if ($this->offset >= strlen($this->buffer)-5)
            {
                $this->buffer .= $this->read();
            }
            if (substr($this->buffer, $this->offset, 4) !== 'null')
            {
                throw new JSONStreamException('unexpected token at offset '.($this->totalOffset + $this->offset).': '.$c);
            }
            $this->offset += 4;
            $v = NULL;
        }
        elseif (ctype_digit($c) || $c == '-')
        {
            if ($this->offset >= strlen($this->buffer)-64)
            {
                $this->buffer .= $this->read();
            }
            if (!preg_match('/-?(?:\d+)(?:\.\d+)?(?:[Ee][\+\-]?\d+)?/As', $this->buffer, $m, 0, $this->offset))
            {
                throw new JSONStreamException('unexpected token at offset '.($this->totalOffset + $this->offset).': '.$c);
            }
            $this->offset += strlen($m[0]);
            $v = 0+$m[0];
        }
        else
        {
            throw new JSONStreamException('unexpected token at offset '.($this->totalOffset + $this->offset).': '.$c);
        }
        $this->pushValue($v);
        return $v;
    }

    protected function pushValue(&$v)
    {
    redo:
        $last = &$this->in[count($this->in)-1];
        if ($last[1] == self::ARR)
        {
            if (!$last[0])
            {
                $last[2][] = $v;
            }
            $this->skipWhitespace();
            if ($this->buffer[$this->offset] == ',')
            {
                $this->offset++;
            }
            $this->skipWhitespace();
            if ($this->buffer[$this->offset] == ']')
            {
                $this->offset++;
                if ($last[0] == 0)
                {
                    $v = $last[2];
                    array_pop($this->in);
                    if ($this->in)
                    {
                        goto redo;
                    }
                }
                else
                {
                    $last[0] = 2; // end of value, caller must call exitObject / exitArray
                }
            }
        }
        elseif ($last[1] == self::OBJ)
        {
            if (!$last[3])
            {
                if (!is_string($v))
                {
                    if (is_array($v))
                    {
                        throw new JSONStreamException('object key must be a string, but it is '.json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
                    }
                    elseif ($v === true)
                    {
                        $v = 'true';
                    }
                    elseif ($v === false)
                    {
                        $v = 'false';
                    }
                    elseif ($v === NULL)
                    {
                        $v = 'null';
                    }
                }
                if ($last[0])
                {
                    $last[3] = true;
                }
                else
                {
                    $last[3] = $v;
                }
                $this->skipWhitespace();
                if (($c = $this->buffer[$this->offset]) != ':')
                {
                    throw new JSONStreamException('unexpected token at offset '.($this->totalOffset + $this->offset).': '.$c);
                }
                $this->offset++;
            }
            else
            {
                if (!$last[0])
                {
                    $last[2][$last[3]] = $v;
                }
                $last[3] = NULL;
                $this->skipWhitespace();
                if ($this->buffer[$this->offset] == ',')
                {
                    $this->offset++;
                }
                $this->skipWhitespace();
                if ($this->buffer[$this->offset] == '}')
                {
                    $this->offset++;
                    if ($last[0] == 0)
                    {
                        $v = $last[2];
                        array_pop($this->in);
                        if ($this->in)
                        {
                            goto redo;
                        }
                    }
                    else
                    {
                        $last[0] = 2; // end of value, caller must call exitObject / exitArray
                    }
                }
            }
        }
    }
}
