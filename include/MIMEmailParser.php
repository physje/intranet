<?php
/**
 * MIME Mail Parser
 *
 * @version    0.10 (2017-04-08 10:31:00 GMT)
 * @author     Peter Kahl <peter.kahl@colossalmind.com>
 * @since      2015-08-29
 * @copyright  2015-2017 Peter Kahl
 * @license    Apache License, Version 2.0
 *
 * Copyright 2015-2016 Peter Kahl <peter.kahl@colossalmind.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      <http://www.apache.org/licenses/LICENSE-2.0>
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

//namespace peterkahl\MIMEmailParser;

class MIMEmailParser {

  /**
   * Version
   * @var string
   */
  const VERSION = '0.10';

  /**
   * The topmost headers of message
   * @var array
   */
  public $headers;

  /**
   * All of the content parsed from the message.
   * Body strings are decoded.
   * @var array
   */
  public $content;

  /**
   * Key of the content array
   * @var integer
   */
  private $contKey;

  /**
   * Type of message, all content types found
   * @var array
   */
  public $messageType;

  /**
   * Character set used for output
   * @var string ... lower case
   */
  const CHARSET = 'utf-8';

  #===================================================================

  public function parse_messageString($str) {
    $this->contKey = 0;
    $this->headers = array();
    $this->content = array();
    $this->messageType = array();
    #----
    if (empty(trim($str))) {
      return false;
    }
    #----
    $this->headers = $this->getHeaders($str);
    $bodyStr = $this->removeHeaders($str);
    $this->parseSegment($bodyStr, $this->headers);
  }

  #===================================================================
  /*
  http://kb.mozillazine.org/Mail_content_types
  RFC2045 ... http://www.ietf.org/rfc/rfc2045.txt

  Various multipart subtype examples:
  -----------------------------------
  multipart/alternative
  multipart/related
  multipart/mixed
  multipart/digest
  multipart/parallel
  multipart/encrypted
  Content-Type: multipart/signed; boundary=Apple-Mail-A2AE48CE4F5EA040DCBFB09E39D452C8F30; protocol="application/pkcs7-signature"; micalg=sha1
  Content-Type: multipart/report; report-type=delivery-status; boundary="D9919E84B25EE7A3F212891998691CA871401A"

  Various content header examples:
  --------------------------------
  Content-Type: application/pkcs7-signature; name=smime.p7s
  Content-Disposition: attachment; filename=smime.p7s
  Content-Transfer-Encoding: base64
  ---
  Content-Type: application/pkcs7-mime; name=smime.p7m; smime-type=enveloped-data
  Content-Transfer-Encoding: base64
  Mime-Version: 1.0 (1.0)
  Content-Disposition: attachment; filename=smime.p7m
  ---
  Content-Type: image/png; name="image004.png"
  Content-Transfer-Encoding: base64
  Content-ID: <image004.png@02A223C4.175039C0>
  */

  private function parseSegment($bodyStr, $mailHdr) {
    if (empty(trim($bodyStr))) {
      return;
    }
    $type = $this->getHdrContentType($mailHdr);
    #----
    if (empty($type)) {
      # Missing type declaration. Assume it's text/plain and us-ascii
      $type = 'text/plain';
      $this->content[$this->contKey]['content-type'] = 'text/plain; charset=us-ascii';
    }
    #====
    array_push($this->messageType, $type);
    #=========================================================
    if (substr($type, 0, 10) == 'multipart/') {
      $boundary = $this->getBoundary($mailHdr);
      $bodyStr  = $this->resetexplode('--'.$boundary.'--', $bodyStr); # String at end is '--'
      $multi    = explode('--'.$boundary.PHP_EOL, $bodyStr);
      unset($multi[0]);
      #####################################
      foreach ($multi as $part) {
        $partHdr = $this->getHeaders($part);
        $partStr = $this->removeHeaders($part);
        $this->parseSegment($partStr, $partHdr);
      }
      #####################################
    }
    #=========================================================
    else {
      #----
      foreach ($mailHdr as $hdrKey => $hdrVal) {
        $this->content[$this->contKey][$hdrKey] = $hdrVal;
      }
      $encoding = $this->getHdrContentTransferEncoding($mailHdr);
      $charset  = $this->getHdrCharset($mailHdr);
      $this->content[$this->contKey]['content'] = $this->decodeBodyStr($bodyStr, $encoding, $charset);
      #----
      $this->contKey++;
      #----
    }
    #=========================================================
  }

  #===================================================================

  private function getBoundary($hdr) {
    if (!empty($hdr['content-type'])) {
      $pos = strpos($hdr['content-type'], 'boundary=');
      if ($pos !== false) {
        $set = substr($hdr['content-type'], $pos);
        $set = $this->resetexplode(';', $set);
        $set = $this->endexplode('oundary=', $set);
        return trim(trim($set, '"'), "'");
      }
    }
    return false;
  }

  #===================================================================

  private function getHdrContentType($hdr) {
    if (!empty($hdr['content-type'])) {
      return $this->resetexplode(';', $hdr['content-type']);
    }
    return false;
  }

  #===================================================================
  /*
  Content-Type: text/html; charset=utf-8
  */

  private function getHdrCharset($hdr) {
    if (!empty($hdr['content-type'])) {
      $pos = strpos($hdr['content-type'], 'charset=');
      if ($pos !== false) {
        $set = substr($hdr['content-type'], $pos);
        $set = $this->resetexplode(';', $set);
        $set = $this->endexplode('arset=', $set);
        return trim(trim($set, '"'), "'");
      }
    }
    return false;
  }

  #===================================================================
  /*
  Content-Transfer-Encoding: 7bit
  */

  private function getHdrContentTransferEncoding($hdr) {
    if (!empty($hdr['content-transfer-encoding'])) {
      return $hdr['content-transfer-encoding'];
    }
    return false;
  }

  #===================================================================

  private function decodeBodyStr($str, $enc, $charset = '') {
    if ($enc == 'base64') {
      $str = base64_decode($str);
    }
    elseif ($enc == 'quoted-printable') {
      $str = quoted_printable_decode($str);
    }
    #----
    if (empty($charset)) {
      return $str;
    }
    #----
    $charset = strtolower($charset);
    if (!in_array($charset, array(self::CHARSET, 'us-ascii')) && substr($charset, 0, 9) !== 'ansi_x3.4') {
      $str = iconv($charset, self::CHARSET.'//IGNORE', $str);
    }
    #----
    return $str;
  }

  #===================================================================

  /**
   * Decodes MIME-encoded segments from a string (header).
   *
   */
  public function decodeRFC2047($input) {
    # Remove white space between encoded-words
    $input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);
    # For each encoded-word...
    while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {
      $encoded = $matches[1];
      $charset = strtolower($matches[2]);
      $type    = strtolower($matches[3]);
      $text    = $matches[4];
      switch ($type) {
        case 'b':
          $text = base64_decode($text);
          break;
        case 'q':
          $text = str_replace('_', ' ', $text);
          preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
          foreach ($matches[1] as $value) {
            $text = str_replace('='.$value, chr(hexdec($value)), $text);
          }
          break;
      }
      if ($charset != self::CHARSET) {
        $text = iconv($charset, self::CHARSET.'//IGNORE', $text);
      }
      $input = str_replace($encoded, $text, $input);
    }
    return $input;
  }

  #===================================================================

  private function getHeaders($str) {
    $arr = explode("\n\n", $str);
    if (empty($arr[0])) {
      return array();
    }
    return $this->unfoldLines($arr[0]);
  }

  #===================================================================

  private function removeHeaders($str) {
    $arr = explode("\n\n", $str);
    unset($arr[0]);
    return implode("\n\n", $arr);
  }

  #===================================================================

  private function unfoldLines($str) {
    $arr = explode(PHP_EOL, $str);
    $n = count($arr);
    # backwards
    for ($k = $n-1; $k >= 0; ) {
      for ($w = 0; $w < $n; $w++) {
        if (preg_match('#^(\t|\s)#', $arr[$k-$w])) {
          $arr[$k-$w-1] .= ' '.substr($arr[$k-$w], 1);
          unset($arr[$k-$w]);
        }
        else {
          $k = $k-$w-1;
          break;
        }
      }
    }
    # Re-build array
    $new = array();
    $s = 1;
    $arr = array_reverse($arr); # We want the headers in the same order as they were added!
    foreach ($arr as $line) {
      $pos = strpos($line, ': ');
      if ($pos !== false) {
        $key = strtolower(substr($line, 0, $pos));
        if (!isset($new[$key])) {
          $new[$key] = preg_replace('/\s+/', ' ', trim(substr($line, $pos+1)));
        }
        else {
          $new[$key.'-'.$s] = preg_replace('/\s+/', ' ', trim(substr($line, $pos+1)));
          $s++;
        }
      }
    }
    return $new;
  }

  #===================================================================

  private function endexplode($glue, $str) {
    if (strpos($str, $glue) === false) {
      return $str;
    }
    $str = explode($glue, $str);
    $str = end($str);
    return $str;
  }

  #===================================================================

  private function resetexplode($glue, $str) {
    if (strpos($str, $glue) === false) {
      return $str;
    }
    $str = explode($glue, $str);
    $str = reset($str);
    return $str;
  }

  #===================================================================

}

