<?php
  namespace elpho\lang;

  //Renamed from Text to Text
  class Text{
    private $value = "";

    public function __construct($value=""){
      $this->value .= $value;
    }

    public function concat($value){
      return new Text($this->value.$value);
    }

    public function length(){
      return strlen($this->value);
    }

    public function isEmpty(){
      return ($this->length() == 0);
    }

    public function charAt($index){
      return new Text(substr($this->value,$index,1));
    }

    public function substring($start,$end=null){
      if(!$end)
        $end = $this->length() -1;

      if($start < 0)
        $start = $this->length() + $start;

      if($end < 0)
        $end = $this->length() + $end;

      $length = $end - $start +1;
      return new Text(substr($this->value,$start,$length));
    }

    public function substr($start,$length=null){
      if($start < 0){
        $start = $this->length() + $start;
        $length = abs($start);
      }

      if(!$length)
        $length = $this->length()-$start;

      return new Text(substr($this->value,$start,$length));
    }

    public function split($delimiter="",$limit=false){
      if(!$delimiter)
        return ArrayList::create(str_split($this->value));
      $limit = $limit ? $limit : strlen($this->value) + 1;
      $primitive = explode($delimiter,$this->value,$limit);
      $list = array();
      foreach($primitive as $str){
        $list[] = new Text($str);
      }

      return ArrayList::create($list);
    }

    public function reverse(){
      return new Text(strrev($this->value));
    }

    public function contains($sequence){
      return (strpos($this->value,$sequence) !== false);
    }

    public function contentEquals($string){
      return ($this->value == $string);
    }

    public function equals($object){
      return (strcmp($this, $object) === 0);
    }

    public function equalsIgnoreCase($object){
      return (strcasecmp($this, $object) === 0);
    }

    public function indexOf($char,$fromIndex=0){
      if(is_object($char)) $char = $char->toString();
      if(is_integer($char)) $char = chr($char);
      return strpos($this->value,$char,$fromIndex);
    }

    public function lastIndexOf($char,$fromIndex=0){
      if(is_object($char)) $char = $char->toString();
      if(is_integer($char)) $char = chr($char);
      return strrpos($this->value,$char,$fromIndex);
    }

    public function count($char){
      $offset = 0;
      $count = 0;

      while(($found = $this->indexOf($char,$offset)) !== false){
        $offset = $found+1;
        $count++;
      }
      return $count;
    }

    public function matches($regex){
      return preg_match($regex,$this->value);
    }

    public function toLowerCase($charset='UTF-8'){
      $string = $this->value;
      $string = htmlentities($string,ENT_COMPAT,$charset);
      $string = strtolower($string);
      $string = html_entity_decode($string,ENT_COMPAT,$charset);
      return new Text($string);
    }

    public function toUpperCase($charset='UTF-8'){
      $string = $this->value;
      $string = htmlentities($string,ENT_COMPAT,$charset);
      $string = strtoupper($string);
      $string = Text::fixUpperCaseEntities($string);
      $string = html_entity_decode($string,ENT_COMPAT,$charset);
      return new Text($string);
    }

    public function capitalize($charset='UTF-8'){
      $strings = $this->split(' ');
      $result = new ArrayList();
      foreach($strings as $word){
        $primitive = $word->toString();
        $string = $primitive[0];
        $string = htmlentities($string,ENT_COMPAT,$charset);
        $string = strtoupper($string);
        $string = Text::fixUpperCaseEntities($string);
        $string = html_entity_decode($string,ENT_COMPAT,$charset);
        $primitive[0] = $string;

        $result->push($primitive);
      }
      return $result->join(' ');
    }

    public static function fixUpperCaseEntities($string){
      $lastIndex = 0;
      while(true){
        $i = strpos($string,"&",$lastIndex);
        if($i === false) break;
        $lastIndex = $i+2;
        $end = strpos($string,";",$lastIndex) - ($i+1);
        $string = substr($string,0,$lastIndex).strtolower(substr($string,$lastIndex,$end)).substr($string,$lastIndex+$end);
      }
      return new Text($string);
    }

    public function startsWith($sequence){
      return (substr($this->value, 0, strlen($sequence)) == $sequence);
    }
    public function endsWith($sequence){
      return (substr($this->value,-strlen($sequence)) == $sequence);
    }

    public function trim(){
      return new Text(trim($this->value));
    }

    public function replace($procura,$substituto,$limite=false){
      if($procura == "")
        return new Text($this->value);

      $limite = $limite?$limite+1:($this->length()===0?1:$this->length());
      return $this->split($procura,$limite)->join($substituto);
    }

    public function replaceExpression($regex,$substituto){
      return new Text(preg_replace($regex,$substituto,$this->value));
    }

    public static function format($formatString,$args=false){
      $args = func_get_args();
      return new Text(apply("sprintf",$args));
    }

    public function hashCode(){
      $n = $this->length();
      $hash = 0;
      for($i=0;$i<$n;$i++){
        $hash += ord($this->charAt($i))*31^($n-($i+1));
      }
      return $hash+ord($this->charAt($n-1));
    }

    public function toObject(){
      $work = new Text($this);
      $work = $work->replace("(","new ProtoObject(array(");
      $work = $work->replace(")","))");
      $obj = eval("return ".$work->toString().";");
      return $obj;
    }

    public function toString(){
      return $this->value;
    }
    public function __toString(){
      return $this->toString();
    }
  }
