<?php

/**
 * decodeSession 
 * 
 * Decode data from a user's session, without using the functions to load it into our current session.
 * Session data looks like this:
 *    wsUserID|i:30;wsUserName|s:7:"Mercury";wsLoginToken|N;wsToken|s:32:"750992346bb4c53ef65a93d265d6d62"
 *
 * @author Neil Kandalgaonkar <neilk@brevity.org>
 * @license GPL v2 or greater
 * 
 * @param String serialized session data
 * @return Array: key-value pairs of parsed session data
 */ 
function decodeSession( $sessionData ) {
  // session_decode() doesn't do what we want (tied to current session?), have to do this in PHP...
  $decoded = array();    

  // Session data is in key-value pairs, each with trailing semicolon
  preg_match_all( '/(.*?)(?<!\\\\);/', $sessionData, $items );

  foreach ($items[1] as $item) {

    // split on pipe, when not preceded by backslash
    $keyData = preg_split( '/(?<!\\\\)\|/', $item, 2 );
    $keyEscaped = $keyData[0];
    // unescape pipes, if key contained pipes
    $key = preg_replace( '/^\\\\([|])/', '$1', $keyEscaped );

    $data = $keyData[1];
    // split data on unbackslashed colons
    $props = preg_split( '/(?<!\\\\):/', $data );
    switch ( $props[0] ) {
      case 'i':
        // integer
        // we assume the integer is all number characters, nothing to unescape
        $val = (int)( $props[1] );
        break;
      case 's':
        // string  
        // curiously, the format includes the string length not including quotes,
        // then the string, in quotes?!
        $length = (int)( $props[1] );
        $valEscapedQuoted = $props[2];
        // remove leading quote, remove trailing quote when not preceded by backslash
        $valEscaped = preg_replace( '/^"|(?<!\\\\)"$/', '', $valEscapedQuoted );
        // unescape any backslashed colons (other characters?)
        $val = preg_replace( '/^\\\\([:])/', '$1', $valEscaped );
        if ( strlen( $val ) != $length ) {
          // the string length isn't what we expected; give up
          die( "Expected string of length $length, found ".
              "'" . preg_replace( "/'/", "\\'", $val) . "'"
              . " with length " . strlen($val) );
        }
        break;
      case 'N':
        $val = null;  
        break;
      default:
        // raise warning? I don't know what other types are possible
        $val = $props[1];
        break;
    }  
    $decoded[$key] = $val;      
      
  }
  return $decoded;
}


