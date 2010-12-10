<?php
/* 
 * converter.php - "Client" code XML converter from MyPasswordSafe to KeePassX XML format
 * 
 * @author Greg Rundlett <greg@freephile.com>
 * @copyright 2009 - 2010 GNU Public License v3.0 or later
 * 
 * @version 1.0
 * 
 * This file is part of MyPasswordSafe2KeypassX
 *
 *  MyPasswordSafe2KeypassX is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  MyPasswordSafe2KeypassX is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with MyPasswordSafe2KeypassX.  See the file named 'COPYING'
 *  If not, see <http://www.gnu.org/licenses/>.
 *
 * 
 * REQUIREMENTS
 * You'll need XML_Beautifier to pretty print the xml
 * @see http://pear.php.net/manual/en/package.xml.xml-beautifier.php
 * 
 * USAGE
 * #1 export your MyPasswordSafe to XML, saving the output as 'passwords.xml'
 * #2 Using the command-line interpreter for PHP ("the cli"), invoke the converter
 * and redirecting output to a file of your choice
 * e.g. php -f converter.php > converted.passwords.xml
 * #3 Now open KeyPassX and import the file from #2.  Save and you're done.
 *
 * CONFIGURATION
 *
 *  You simply need to tell the script where your password data file is 
 *  (the output from step #1) 
 */

// in PHP 5.3, we can use __DIR__ but for more compatibility, we'll do it the old way
$myPasswordSafeFile = dirname(__FILE__) . '/passwords.xml';
// $myPasswordSafeFile = dirname(__FILE__) . '/passwords.2010.12.09.xml';

/////////////////  DO NOT NEED TO EDIT BELOW THIS LINE /////////////////////////////


/**
 * Here is what those schemas look like:
<!DOCTYPE MyPasswordSafe>
<safe password="secret" >
 <group name="work" >
 <item>
  <uuid>8042a8d0-d5cf-11db-bb7e-000019000000</uuid>
  <name>gmail</name>
  <user>admin</user>
  <password>secret</password>
  <notes>
   <line>Hi.  I'm just a comment</line>
  </notes>
  <created>2007-03-19T00:08:32</created>
  <modified>2008-04-07T12:42:09</modified>
  <accessed>2008-04-07T12:41:56</accessed>
  <lifetime>18:59:59</lifetime>
 </item>
 </group>

 <item>
    <uuid>fbb3677a-7ea8-11dc-87bf-000019000000</uuid>
    <name>Savings Bank of My Town</name>
    <user>john q. public</user>
    <password>rl86-oTu\</password>
    <notes>
     <line>Visa Debit </line>
     <line>4090000000000004</line>
     <line>111</line>
     <line>exp 02/10</line>
     <line>for online transaction verification, other secret</line>
    </notes>
    <created>2007-10-19T21:08:35</created>
    <modified>2009-11-14T09:05:44</modified>
    <accessed>2009-12-20T09:12:54</accessed>
    <lifetime>18:59:59</lifetime>
   </item>

 </safe>

<!DOCTYPE KEEPASSX_DATABASE>
<database>
 <group>
  <title>Internet</title>
  <icon>1</icon>
  <entry>
   <title>this is a title</title>
   <username>greg</username>
   <password>penguin</password>
   <url>http://rundlett.com</url>
   <comment>Hi i'm a first line<br/>and a second line comment</comment>
   <icon>0</icon>
   <creation>2009-12-20T10:19:21</creation>
   <lastaccess>2009-12-20T10:20:13</lastaccess>
   <lastmod>2009-12-20T10:20:13</lastmod>
   <expire>Never</expire>
  </entry>
 </group>
 <group>
  <title>eMail</title>
  <icon>19</icon>
 </group>
</database>

 *
 * In this implementation, we will use null to set the expiration because it doesn't seem like
 * MyPasswordSafe is using that value (it's always the same, and it's a time?)
 * The default on conversion is to set the value 'Never'
 *
 * @todo create a web interface that allows you to select the icons for your groups?
 * @fixme the <br /> tags in comments end up with spaces around the angle brackets
 *
 *
 * I did try, but abandonded an alternative method of getting the contents of a DOMElement
 * 

function getTextFromNode($Node, $Text = "") {
    if ($Node->tagName == null)
        return $Text.$Node->textContent;

    $Node = $Node->firstChild;
    if ($Node != null)
        $Text = getTextFromNode($Node, $Text);

    while($Node->nextSibling != null) {
        $Text = getTextFromNode($Node->nextSibling, $Text);
        $Node = $Node->nextSibling;
    }
    return $Text;
}

function getTextFromDocument($DOMDoc) {
    return getTextFromNode($DOMDoc->documentElement);
}

 * This is an example invocation of that approach
 * 
  $Doc = new DOMDocument();
  $Doc->loadHTMLFile("Test.html");
  echo getTextFromDocument($Doc)."\n"; 
 *
 *
 */




// add our library
require ("KeepassxImport.php");

// load our data, may want to try the LIBXML_NOCDATA if there is CDATA in the document
//if( !$xml = simplexml_load_file( $myPasswordSafeFile, 'SimpleXMLElement', LIBXML_NOCDATA )) {
if( !$xml = simplexml_load_file( $myPasswordSafeFile ) ){
    trigger_error( 'Error reading XML file', E_USER_ERROR );
}


/**
 * inspects an item for notes, and returns an xhtml representation of the notes.
 * Because KeePassX uses a single element to store notes, multi-line notes in KeePassX
 * have a <br /> tag in them, whereas in MyPasswordSafe, a multiline note is represented
 * by multiple <line> elements.
 * @param SimpleXMLElement $item
 * @return xhtml string representation of children elements (lines of comments)
 * @todo check if we could just use print() to invoke the __tostring() method?
 */
function getComments ($item) {
  $return = '';
  // $path is an xpath expression which allows us to find the right child element
  $path = 'notes/line';
  foreach ($item->xpath($path) as $line) {
    $return .= "$line<br />\n";
    /*if (!$item->xpath($path[position() = last()])) {
      $return .= "<br />\n";
    }*/
  }
  return $return;
}

// do a little output so the user can see what's going to be converted
// here, xml->item would only see the first level (not those nested in groups, so
// use xpath

$countPasswords = count($xml->xpath('//item'));
$countGroups = count($xml->xpath('//group'));

/*
print <<<HERE
reviewing the source file....<br />
$countPasswords passwords found in $countGroups groups<br /><br />
HERE;
*/

// initialize
$kpx = new KeepassXDbDoc();
$counter = 0;
$prevGroup = null;
// using xpath, we can match item anywhere in the tree by starting with two slashes

/**
 * We have a bunch of items, and each has a unique ID, so we'll use that to
 * keep track of which ones have been converted to the new XML format.
 *
 * We also have some that may not be in a group, whereas the new XML format requires
 * all password entries to have a parent group
 */
foreach ($xml->xpath('//item') as $item) {
  $counter++;
  // echo "item $counter: ". $item->uuid . " : " . $item->name . "<br />\n";
  $items[] = $item->uuid;
}

foreach ($xml->xpath('//group') as $group) {
  $groupName = $group['name'];
  // echo $groupName . " has " . count($group) . " items<br />\n";
  // showMe ($group);
  $groupElement = $kpx->addGroup($groupName);
  foreach ($group->xpath('item') as $item) {
    $processed[] = $item;
    $key = array_search($item->uuid, $items);
    if($key === false) {
      die ('error - we processed a group item that was not in the global items list');
    } else {
      // reduce our inventory of items needing to be processed.
      unset ($items[$key]);
    }
    $comments = getComments($item);
    
    /**
     * This is where the fun happens.  Add a password entry
     */
    $groupElement->addEntry(
      $item->name,                //$title,
      $item->user,                //$username,
      $item->password,            //$password,
      null,                       // $url = null,
      $comments,                  //$comment = null,
      strtotime($item->created),  //$creation = null,
      strtotime($item->accessed), //$last_access = null,
      strtotime($item->modified), //$last_mod = null,
      null                        //$expire = null
      );
  }
}

// echo "we have seen " . count($processed) . " items in groups and still have ".  count($items) . " to add to 'uncategorized' ";

// take all the first-level password entries found in MyPasswordSafe
// and put them into a group called 'uncategorized' 
$groupElement = $kpx->addGroup('uncategorized');
foreach ($xml->xpath('item') as $item) {
  if (in_array($item->uuid, $items)) {
    $key = array_search ($item->uuid, $items);
    unset ($items[$key]);
    $processed[] = $item;
    $comments = getComments($item);
    $groupElement->addEntry(
      $item->name,                //$title,
      $item->user,                //$username,
      $item->password,            //$password,
      null,                       // $url = null,
      $comments,                  //$comment = null,
      strtotime($item->created),  //$creation = null,
      strtotime($item->accessed), //$last_access = null,
      strtotime($item->modified), //$last_mod = null,
      null                        //$expire = null
    );
  }
}

// echo "we have seen " . count($processed) . " items in groups and still have " . count($items) . " to add to 'uncategorized' ";

function showMe ($x) {
  echo '<pre>'; print_r($x); echo '</pre>';
}
// showMe($xml);


/**
 * Now we move on to show some output which can be easily redirected in the shell
 * e.g. php -f conversion2.php > convertedPasswords.2010.12.09.xml
 */
// this require() autoloads from the cli but not when using the debugger in eclipse?!?
// even though the php.ini is configured in eclipse
// require_once 'XML/Beautifier.php';
// so we'll supplement our path
$path1 = '/usr/lib/pear';
$path2 = '/usr/share/php';
set_include_path(get_include_path() . PATH_SEPARATOR . $path1);
set_include_path(get_include_path() . PATH_SEPARATOR . $path2);

require_once ('XML/Beautifier.php');

$xb = new XML_Beautifier();
$newxml =  $xb->formatString($kpx->saveXML());
echo ($newxml);

/**
 * If we want to show a source-highlighted version on screen

require_once "Text/Highlighter.php";

$hlXML =& Text_Highlighter::factory("XML");
echo '<html><head><link rel="stylesheet" type="text/css" href="sample.css" /></head>';
echo '<body>' . $hlXML->highlight($newxml) . '</body></html>';
 */

/**
 * could use the internal PHP highlight functions if it were PHP code
function echo2Web($source) {
   echo '<div style="border: solid 1px orange; padding: 20px; margin: 20px">';
   highlight_string($source);
   echo '</div>';
}
// echo2Web ($newxml);
 */
