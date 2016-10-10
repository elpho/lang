<?php
  namespace elpho\lang;

  use elpho\event\EventHandler;
  use elpho\event\Event;

  class Object extends EventHandler implements \ArrayAccess, \Iterator{
    private $_prototype;
    private $properties;

    //implementing Iterator
    private $index = 0;
    private $indexes = array();
    public function next(){
      $this->index++;
    }
    public function key(){
      $this->updateAtributeList();
      return $this->indexes[$this->index];
    }
    public function current(){
      $this->updateAtributeList();
      $current = $this->indexes[$this->index];
      if(isset($this->properties[$current])) return $this->properties[$current];

      $prototype = $this->_prototype->reverse();
      foreach($prototype as $proto){
        if(!isset($proto[$current])) continue;
        return $proto[$current];
      }
    }
    public function valid(){
      $this->updateAtributeList();
      return isset($this->indexes[$this->index]);
    }
    public function rewind(){
      $this->index = 0;
    }
    private function updateAtributeList(){
      $this->indexes = new ArrayList();

      foreach($this->properties as $var => $value){
        $this->indexes->push($var);
      }

      foreach($this->_prototype as $proto){
        foreach($proto as $var => $value){
          $this->indexes->push($var);
        }
      }

      $this->indexes->unique();
    }

    //constructor
    public function __construct($properties=array(),$prototype=null,$_=null){
      $prototypeList = $prototype;
      if(!is_a($prototype,"ArrayList")){
        $prototypeList = ArrayList::create(func_get_args());
        $prototypeList->shift();
        if($prototypeList->isEmpty())
          $prototypeList->push(new Dynamic());
      }

      $this->_prototype = $prototypeList;
      $this->properties = new Dynamic();

      foreach($properties as $key => $value){
        if(!is_string($key)){
          $key = $value;
          $value = '';
        }
        $this->properties->{$key} = $value;
      }
    }

    //implementing array access
    public function offsetExists($offset){
      $found = isset($this->properties[$offset]);
      if($found) return true;
      foreach($this->_prototype as $proto){
        if(isset($proto[$offset])) return true;
      }
      return false;
    }
    public function offsetGet($offset){
      return $this->{$offset};
    }
    public function offsetSet($offset,$value){
      $this->{$offset} = $value;
    }
    public function offsetUnset($offset){
      unset($this->properties[$offset]);
    }

    //extending EventHandler
    public function dispatchEvent(Event $event){
      parent::dispatchEvent($event);
    }

    //extend as prototype
    public function __invoke($properties=array()){
      return new Object($properties,$this);
    }

    //extend as prototype for javascript people
    public static function create(Object $object, $_=null){
      return new Object(array(),ArrayList::create(func_get_args()));
    }

    public static function merge(Object $object, Object $_=null){
      $args = func_get_args();
      $new = new Object();

      foreach ($args as $obj) {
        foreach ($obj as $key => $value) {
          $new->{$key} = $value;
        }
      }
      return $new;
    }

    public function __set($property,$value){
      if($property == "prototype")
        throw new \Exception("Cannot access read-only property ".get_class($this).'::$prototype');
      $this->properties->{$property} = $value;
    }
    public function __get($property){
      if($property == "prototype") return $this->_prototype[0];

      if(isset($this->properties->{$property}))
        return $this->properties->{$property};

      foreach($this->_prototype as $proto){
        if(isset($proto[$property]))
          return $proto[$property];
      }

      return null;
    }
    public function __call($key,$params){
      $subject = null;
      if(isset($this->properties->{$key}))
        $subject = $this->properties;

      if($subject === null){
        $prototype = $this->_prototype->reverse();
        foreach($prototype as $proto){
          if(!isset($proto[$key])) continue;
          $subject = $proto;
          break;
        }
      }

      if($subject === null)
        throw new \Exception("Call to undefined method ".get_class($this)."::".$key."()");

      $closure = array($subject, $key);

      call_user_func_array($closure,$params);
    }
    public function duplicate(){
      $new = new Object();
      foreach($this->properties as $name => $value){
        $new->{$name} = $value;
      }
      return $new;
    }
    public function toPrimitive(){
      return $this->properties;
    }
    public function __toString(){
      return '[object '.get_class($this).']';
    }
    public function toJson(){
      $final = new String();
      $propertyList = new ArrayList();

      foreach($this->properties as $name => $value){
        if(is_subclass_of($value,"Object")) $value = $value->toJson();
        $propertyList->push($name.':"'.$value.'"');
      }

      $final->concat("{");
      $final->concat($propertyList->join(","));
      $final->concat("}");

      return $final;
    }
  }
