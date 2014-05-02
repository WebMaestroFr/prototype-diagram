<?php

ini_set( 'display_errors', 'On' );
error_reporting(E_ALL);

class JS_Class {

  public function __construct( $name ) {
    $this->name = $name;
    $this->parent = false;
    $this->classes = array();
  }

}

class JS_Class_Diagram {

  public function __construct( $directory ) {
    $this->classes = array();
    if ( $files = self::get_files( $directory ) ) {
      foreach ( $files as $file ) {
        $this->set_classes( file_get_contents( $file ) );
      }
      header( 'Content-Type: text/plain' );
      // echo json_encode( $this->classes );
      echo $this->get_yuml();
    }
  }

  private static function list_files( $directory )
  {
    $files = array_slice( scandir( $directory ), 2 );
    foreach ( $files as $i => $entry ) {
      $files[$i] = $directory . '/' . $entry;
    }
    return $files;
  }

  private static function get_files( $file )
  {
    $files = array();
    if ( file_exists( $file ) ) {
      if ( is_dir( $file ) ) {
        $list = self::list_files( $file );
        foreach ( $list as $entry ) {
          $files = array_merge( $files, self::get_files( $entry ) );
        }
      } else {
        $filename = explode( '.', $file );
        if ( 'js' === strtolower( end( $filename ) ) ) {
          $files[] = $file;
        }
      }
    }
    return $files;
  }

  private function get_class( $path )
  {
    $class = $this;
    while ( count( $path ) > 0 ) {
      $branch = array_shift( $path );
      if ( ! isset( $class->classes[$branch] ) ) {
        $class->classes[$branch] = new JS_Class( $branch );
        // $class->classes[$branch]->super = $class;
      }
      $class = $class->classes[$branch];
    }
    return $class;
  }

  private function set_classes( $content )
  {
    preg_match_all( '/([\w\.]+)\.prototype\s*=(?:\s*Object\.create\(\s*([\w\.]+)\.prototype[^.])?/', $content, $matches, PREG_SET_ORDER );
    foreach ( $matches as $match ) {
      $class = $this->get_class( explode( '.', $match[1] ) );
      if ( isset( $match[2] ) ) {
        $class->parent = $this->get_class( explode( '.', $match[2] ) );
        // $class->parent->children[] = $class;
      }
    }
  }

  private static function in_tree( $class, $branches ) {
    foreach ( $branches as $branch ) {
      if ( $class === $branch || self::in_tree( $class, $branch->classes ) ) { return true; }
    }
    return false;
  }

  private function get_yuml()
  {
    $yuml = array();
    foreach( $this->classes as $root ) {
      $yuml[] = "[{$root->name}]";
      $yuml = array_merge( $yuml, $this->get_class_yuml( $root ) );
    }
    return implode( ', ', $yuml );
  }

  private function get_class_yuml( $branch )
  {
    $yuml = array();
    foreach( $branch->classes as $class ) {
      if ( $class->parent ) {
        $yuml[] = "[{$class->parent->name}]^-[{$class->name}]";
      }
      if ( ! $class->parent || ( $class->parent !== $branch && ! self::in_tree( $class->parent, $branch->classes ) ) ) {
        $yuml[] = "[{$branch->name}]->[{$class->name}]";
      }
      $yuml = array_merge( $yuml, $this->get_class_yuml( $class ) );
    }
    return $yuml;
  }

}

$diagram = new JS_Class_Diagram( dirname( __FILE__ ) );
