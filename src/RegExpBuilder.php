<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 12.02.15
 * Time: 17:06
 */

namespace Gherkins\RegExpBuilderPHP;

class RegExpBuilder
{

    public $_flags      = "";
    public $_literal    = [];
    public $_groupsUsed = 0;
    public $_min;
    public $_max;
    public $_of;
    public $_ofAny;
    public $_ofGroup;
    public $_from;
    public $_notFrom;
    public $_like;
    public $_either;
    public $_reluctant;
    public $_capture;


    public function __construct()
    {
        $this->_clear();
    }

    public function _clear()
    {
        $this->_min       = -1;
        $this->_max       = -1;
        $this->_of        = "";
        $this->_ofAny     = false;
        $this->_ofGroup   = -1;
        $this->_from      = "";
        $this->_notFrom   = "";
        $this->_like      = "";
        $this->_either    = "";
        $this->_reluctant = false;
        $this->_capture   = false;
    }

    public function _flushState()
    {
        if ($this->_of != "" || $this->_ofAny || $this->_ofGroup > 0 || $this->_from != "" || $this->_notFrom != "" || $this->_like != "") {
            $captureLiteral   = $this->_capture ? "" : "?:";
            $quantityLiteral  = $this->_getQuantityLiteral();
            $characterLiteral = $this->_getCharacterLiteral();
            $reluctantLiteral = $this->_reluctant ? "?" : "";
            $this->_literal[] = ("(" . $captureLiteral . "(?:" . $characterLiteral . ")" . $quantityLiteral . $reluctantLiteral . ")");
            $this->_clear();
        }
    }

    public function _getQuantityLiteral()
    {
        if ($this->_min != -1) {
            if ($this->_max != -1) {
                return "{" . $this->_min . "," . $this->_max . "}";
            }

            return "{" . $this->_min . ",}";
        }

        return "{0," . $this->_max . "}";
    }

    public function _getCharacterLiteral()
    {
        if ($this->_of != "") {
            return $this->_of;
        }
        if ($this->_ofAny) {
            return ".";
        }
        if ($this->_ofGroup > 0) {
            return "\\" . $this->_ofGroup;
        }
        if ($this->_from != "") {
            return "[" . $this->_from . "]";
        }
        if ($this->_notFrom != "") {
            return "[^" . $this->_notFrom . "]";
        }
        if ($this->_like != "") {
            return $this->_like;
        }

        return null;
    }

    public function getLiteral()
    {
        $this->_flushState();

        return join("", $this->_literal);
    }

    public function _combineGroupNumberingAndGetLiteral(RegExpBuilder $r)
    {
        $literal = $this->_incrementGroupNumbering($r->getLiteral(), $this->_groupsUsed);
        $this->_groupsUsed .= $r->_groupsUsed;

        return $literal;
    }


    public function _incrementGroupNumbering($literal, $increment)
    {

        if ($increment > 0) {
            //fixme: port js replace
//        $literal = literal . replace(/[^\\]\\\d +/g, function (groupReference) {
//            $groupNumber = parseInt(groupReference . substring(2)) + increment;
//
//            return groupReference . substring(0, 2) + groupNumber;
//        });
        }

        return $literal;
    }

    public function getRegExp()
    {
        $this->_flushState();

        return new RegExp(join("", $this->_literal) , $this->_flags);
    }

    public function _addFlag($flag)
    {
        if (strpos($this->_flags, $flag) === false) {
            $this->_flags .= $flag;
        }

        return $this;
    }


    public function ignoreCase()
    {
        return $this->_addFlag("i");
    }


    public function multiLine()
    {
        return $this->_addFlag("m");
    }

    public function globalMatch()
    {
        return $this->_addFlag("g");
    }

    public function startOfInput()
    {
        $this->_literal[] = "(?:^)";

        return $this;
    }

    public function startOfLine()
    {
        $this->multiLine();

        return $this->startOfInput();
    }

    public function endOfInput()
    {
        $this->_flushState();
        $this->_literal[] = "(?:$)";

        return $this;
    }

    public function endOfLine()
    {
        $this->multiLine();

        return $this->endOfInput();
    }

    public function either($r)
    {
        if (is_string($r)) {
            $builder = new RegExpBuilder();

            return $this->_eitherLike($builder->exactly(1)->of($r));
        }

        return $this->_eitherLike($r);
    }


    public function _eitherLike($r)
    {
        $this->_flushState();
        $this->_either = $this->_combineGroupNumberingAndGetLiteral($r);

        return $this;
    }

    public function orLike($r)
    {

        if (is_string($r)) {
            $builder = new RegExpBuilder();

            return $this->_orLike($builder->exactly(1)->of($r));
        }

        return $this->_orLike($r);
    }

    public function _orLike($r)
    {
        $either = $this->_either;
        $or     = $this->_combineGroupNumberingAndGetLiteral($r);
        if ($either == "") {
            $lastOr = $this->_literal[count($this->_literal) - 1];

            $lastOr                                     = substr($lastOr, 0, (strlen($lastOr) - 1));
            $this->_literal[count($this->_literal) - 1] = $lastOr;
            $this->_literal[]                           = "|(?:" . $or . "))";
        } else {
            $this->_literal[] = "(?:(?:" . $either . ")|(?:" . $or . "))";
        }
        $this->_clear();

        return $this;
    }


    public function neither($r)
    {

        if (is_string($r)) {
            $builder = new RegExpBuilder();

            return $this->notAhead($builder->exactly(1)->of($r));

        }

        return $this->notAhead($r);
    }

    public function nor($r)
    {
        if ($this->_min == 0 && $this->_ofAny) {
            $this->_min   = -1;
            $this->_ofAny = false;
        }
        $this->neither($r);

        return $this->min(0)->ofAny();
    }

    public function exactly($n)
    {
        $this->_flushState();
        $this->_min = $n;
        $this->_max = $n;

        return $this;
    }

    public function min($n)
    {
        $this->_flushState();
        $this->_min = $n;

        return $this;
    }

    public function max($n)
    {
        $this->_flushState();
        $this->_max = $n;

        return $this;
    }

    public function of($s)
    {
        $this->_of = $this->_sanitize($s);

        return $this;
    }


    public function ofAny()
    {
        $this->_ofAny = true;

        return $this;
    }

    public function ofGroup($n)
    {
        $this->_ofGroup = $n;

        return $this;
    }

    public function from($s)
    {
        $this->_from = $this->_sanitize(join("", $s));

        return $this;
    }

    public function notFrom($s)
    {
        $this->_notFrom = $this->_sanitize(join("", $s));

        return $this;
    }

    public function like($r)
    {
        $this->_like = $this->_combineGroupNumberingAndGetLiteral($r);

        return $this;
    }


    public function reluctantly()
    {
        $this->_reluctant = true;


        return $this;
    }


    public function ahead($r)
    {
        $this->_flushState();
        $this->_literal[] = "(?=" . $this->_combineGroupNumberingAndGetLiteral($r) . ")";

        return $this;
    }


    public function notAhead($r)
    {
        $this->_flushState();
        $this->_literal[] = "(?!" . $this->_combineGroupNumberingAndGetLiteral($r) . ")";

        return $this;
    }

    public function asGroup()
    {
        $this->_capture = true;
        $this->_groupsUsed++;

        return $this;
    }

    public function then($s)
    {
        return $this->exactly(1)->of($s);
    }

    public function find($s)
    {
        return $this->then($s);
    }

    public function some($s)
    {
        return $this->min(1)->from($s);
    }

    public function maybeSome($s)
    {
        return $this->min(0)->from($s);
    }

    public function maybe($s)
    {
        return $this->max(1)->of($s);
    }

    public function anything()
    {
        return $this->min(0)->ofAny();
    }

    public function anythingBut($s)
    {
        if (strlen($s) === 1) {
            return $this->min(0)->notFrom([$s]);
        }
        $builder = new RegExpBuilder();
        $this->notAhead($builder->exactly(1)->of($s));

        return $this->min(0)->ofAny();
    }

    public function any()
    {
        return $this->exactly(1)->ofAny();
    }

    public function lineBreak()
    {
        $this->_flushState();
        $this->_literal[] = "(?:\\r\\n|\\r|\\n)";

        return $this;
    }

    public function lineBreaks()
    {
        $builder = new RegExpBuilder();

        return $this->like($builder->lineBreak());
    }


    public function whitespace()
    {
        if ($this->_min == -1 && $this->_max == -1) {
            $this->_flushState();
            $this->_literal[] = "(?:\\s)";

            return $this;
        }
        $this->_like = "\\s";

        return $this;
    }

    public function notWhitespace()
    {
        if ($this->_min == -1 && $this->_max == -1) {
            $this->_flushState();
            $this->_literal[] = "(?:\\S)";

            return $this;
        }
        $this->_like = "\\S";

        return $this;
    }

    public function tab()
    {
        $this->_flushState();
        $this->_literal[] = "(?:\\t)";

        return $this;
    }

    public function tabs()
    {
        $builder = new RegExpBuilder();

        return $this->like($builder->tab());
    }

    public function digit()
    {
        $this->_flushState();
        $this->_literal[] = "(?:\\d)";

        return $this;
    }


    public function notDigit()
    {
        $this->_flushState();
        $this->_literal[] = "(?:\\D)";

        return $this;
    }

    public function digits()
    {

        $builder = new RegExpBuilder();

        return $this->like($builder->digit());
    }

    public function notDigits()
    {
        $builder = new RegExpBuilder();

        return $this->like($builder->notDigit());
    }

    public function letter()
    {
        $this->exactly(1);
        $this->_from = "A-Za-z";

        return $this;
    }

    public function notLetter()
    {
        $this->exactly(1);
        $this->_notFrom = "A-Za-z";

        return $this;
    }

    public function letters()
    {
        $this->_from = "A-Za-z";

        return $this;
    }

    public function notLetters()
    {
        $this->_notFrom = "A-Za-z";

        return $this;
    }

    public function lowerCaseLetter()
    {
        $this->exactly(1);
        $this->_from = "a-z";

        return $this;
    }

    public function lowerCaseLetters()
    {
        $this->_from = "a-z";

        return $this;
    }

    public function upperCaseLetter()
    {
        $this->exactly(1);
        $this->_from = "A-Z";

        return $this;
    }

    public function upperCaseLetters()
    {
        $this->_from = "A-Z";

        return $this;
    }

    public function append($r)
    {
        $this->exactly(1);
        $this->_like = $this->_combineGroupNumberingAndGetLiteral($r);

        return $this;
    }

    public function optional($r)
    {
        $this->max(1);
        $this->_like = $this->_combineGroupNumberingAndGetLiteral($r);

        return $this;
    }

    public function _sanitize($s)
    {

        //fixme: port js replace
        $matches = array();
        preg_match('/([.*+?^=!:${}()|\[\]\/\\\\])/', $s, $matches);

        return $s;
    }

    public function another(){
        $that = clone $this;

        $that->_flags      = "";
        $that->_literal    = [];
        $that->_groupsUsed = 0;
        $that->_clear();
        return $that;
    }

}
