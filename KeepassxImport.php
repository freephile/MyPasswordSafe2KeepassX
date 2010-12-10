<?php
/**
 * Exports as keepassx doc
 *
 * @project keepassx
 */

/**
http://forums.lastpass.com/viewtopic.php?f=12&t=213
**/
/**
<!DOCTYPE KEEPASSX_DATABASE>
<database>
<group>
  <title>General</title>
  <icon>48</icon>
  <group>
   <title>group title</title>
   <icon>3</icon>
   <group>
    <title>group title</title>
    <icon>48</icon>
    <entry>
     <title>title</title>
     <username>username</username>
     <password>password</password>
     <url></url>
     <comment></comment>
     <icon>0</icon>
     <creation>2009-03-04T18:03:12</creation>
     <lastaccess>2009-03-04T18:03:40</lastaccess>
     <lastmod>2009-03-04T18:03:40</lastmod>
     <expire>Never</expire>
    </entry>
   </group>
  </group>
</group>
</database>
**/

/**
 * keepassx dom document
 *
 * @project keepassx
 *
 * @todo add correct doctype to document
 */
class KeepassXDbDoc extends DOMDocument
{
	/**
	 * @var DOMElement
	 */
	protected $db;

	public function __construct()
	{
		parent::__construct();
		$this->registerNodeClass('DOMElement', 'KeepassXGroup');
//		$this->doctype = DOMImplementation::createDocumentType('KEEPASSX_DATABASE');

		$db = $this->createElement('database');
		$this->appendChild($db);
		$this->db = $db;
	}

	public function addGroup($title)
	{
		$element = $this->createElement('group');
		$this->db->appendChild($element);
		$element->setTitle($title);

		return $element;
	}
} // end KeepassXDbDoc

/**
 * keepassx dom element for adding a group
 *
 * @project keepassx
 */
class KeepassXGroup extends DOMElement
{
	public function setTitle($title)
	{
		$element = new DOMElement('title', $title);
		$this->appendChild($element);
	}

	public function addGroup($title)
	{
		$class = __CLASS__;
		$element = new $class('group');
		$this->appendChild($element);
		$element->setTitle($title);

		return $element;
	}

	public function addEntry($title, $username, $password,
		$url = null, $comment = null,
		$creation = null, $last_access = null, $last_mod = null,
		$expire = null)
	{
		$entry = new KeepassXEntry('entry');
		$this->appendChild($entry);

		$entry->setTitle($title);
		$entry->setUsername($username);
		$entry->setPassword($password);
		$entry->setUrl($url);
		$entry->setComment($comment);
		$entry->setCreation($creation);
		$entry->setLastAccess($last_access);
		$entry->setLastMod($last_mod);
		$entry->setExpire($expire);

		return $entry;
	}
}

/**
 * keepassx dom element for adding an entry
 *
 * @project keepassx
 */
class KeepassXEntry extends DOMElement
{
	public function setTitle($title)
	{
		$element = new DOMElement('title', $this->entitySafe($title));
		$this->appendChild($element);
		return $element;
	}

	public function setUsername($username)
	{
		$element = new DOMElement('username', $this->entitySafe($username));
		$this->appendChild($element);
		return $element;
	}

	public function setPassword($password)
	{
		$element = new DOMElement('password', $this->entitySafe($password));
		$this->appendChild($element);
		return $element;
	}

	public function setUrl($url = null)
	{
		$element = new DOMElement('url', $this->entitySafe($url));
		$this->appendChild($element);
		return $element;
	}

	public function setComment($comment = null)
	{
		$element = new DOMElement('comment', $this->entitySafe($comment));
		$this->appendChild($element);
		return $element;
	}

	public function setCreation($timestamp = null)
	{
		$element = new DOMElement('creation',
			$this->formatDate($timestamp));
		$this->appendChild($element);
		return $element;
	}

	public function setLastAccess($timestamp = null)
	{
		$element = new DOMElement('lastaccess',
			$this->formatDate($timestamp));
		$this->appendChild($element);
		return $element;
	}

	public function setLastMod($timestamp = null)
	{
		$element = new DOMElement('lastmod',
			$this->formatDate($timestamp));
		$this->appendChild($element);
		return $element;
	}

	public function setExpire($timestamp = null)
	{
		$element = new DOMElement('expire',
			$this->formatDate($timestamp));
		$this->appendChild($element);
		return $element;
	}

	/**
	 * Replaces ampersands with html entity, making it safe for XML
	 * use lookahead assertion that the ampersand is not followed by an optional hash, one or more
	 * word characters and a semicolon.  In other words, it WILL match if it's a bare ampersand,
	 * such as in the string "H&R Block" but
	 * will leave friendly entities like &quot; or &#213; alone
	 * @see http://www.php.net/manual/en/regexp.reference.assertions.php
	 * @param string $message
	 * @return string
	 */
	protected function entitySafe($message)
	{
		return preg_replace('/&(?!#?\w+;)/', '&amp;', $message);
	}
  
	/**
	 * A function to format the dates the way that KeyPassX expects them
	 * 
	 * @param $timestamp a timestamp such as 2007-03-19T00:08:32
	 * @return an ISO 8601 date (format specifier added in PHP 5)  
	 * e.g. 2004-02-12T15:19:21+00:00 
	 * or the word "Never" which KeyPassX understands 
	 * as a password which does not expire
	 * Actually MyPasswordSafe doesn't seem to set limits b/c the limit is always the same
	 * and it a time?!
	 */
	protected function formatDate($timestamp = null)
	{
		$return_val = 'Never';
		if (isset($timestamp)) {
			$return_val = date('c', $timestamp);
		}

		return $return_val;
	}
}


/**
 Converts a CSV file exported from pwmanager to keepassx format

$fh = fopen('php://stdin', 'r');
$prev_group = null;
$kpx = new KeepassXDbDoc();
$group = null;
while (($i = fgetcsv($fh)) !== false) {
	$entry = array(
		'group' => $i[0],
		'title' => $i[2],
		'username' => $i[3],
		'password' => $i[4],
		'url' => $i[5],
		'comment' => $i[7],
	);
	if ($prev_group != $entry['group']) {
		$group = $kpx->addGroup($entry['group']);
		$prev_group = $entry['group'];
	}
	$group->addEntry($entry['title'], $entry['username'], $entry['password'],
		$entry['url'], $entry['comment']);
}

require_once 'XML/Beautifier.php';
$xb = new XML_Beautifier();
echo $xb->formatString($kpx->saveXML());
**/