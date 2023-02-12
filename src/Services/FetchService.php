<?php 

namespace App\Services;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use JMS\Serializer\SerializerBuilder;

#[\AllowDynamicProperties]
class FetchService
{
    const KEY_TITLE         = 'name';
    const KEY_ITEM          = 'item';
    const KEY_PARTS         = 'parts';
    const KEY_SUCCESS       = 'success';
    const KEY_DOC_ROOT      = 'DOCUMENT_ROOT';
    const KEY_PROTOCOL      = 'GET';
    const KEY_CONTEN_TYPE   = 'content-type';
    const KEY_COUNT         = 'productCount';
    const KEY_ITEM_NAME     = 'entryName';
    const KEY_P_NAME        = 'productName';
    const STATUS            = 200;

    private $client;
    private $filesystem;
    private $result;
    private $serializer;
    private $rootdir;
    private $response;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->filesystem = new Filesystem();
        $this->result = [self::KEY_SUCCESS => false];
        $this->serializer = new \DOMDocument();
        $this->rootdir = $_SERVER[self::KEY_DOC_ROOT];
    }

    public function fetchXMLfFile($xmlUrl, $update = false): array
    {
        $filename = explode('/', $xmlUrl);
        $filename = $filename[count($filename) - 1];
        $xmlFilename = str_replace('.zip', '', $filename);

        if (!strpos($xmlFilename, '.xml')) $xmlFilename = $xmlFilename.'.xml';

        if (!$this->filesystem->exists([$this->rootdir.'/tmp/'.$filename])) $update = true;
        
        if ($update) {
            $this->response = $this->client->request(
                self::KEY_PROTOCOL,
                $xmlUrl
            );
        }

        if (!empty($this->response) && $this->response->getStatusCode() == self::STATUS || !$update) {
            
            $this->filesystem->mkdir($this->rootdir.'/tmp');

            if ($update) {
                $contentType = $this->response->getHeaders()[self::KEY_CONTEN_TYPE][0];
                $content = $this->response->getContent();
                $this->filesystem->dumpFile($this->rootdir.'/tmp/'.$filename, $content);
            }

            $stream = fopen($this->rootdir.'/tmp/'.$filename, 'rb');

            $zipFile = new \PhpZip\ZipFile();
            $zipFile->openFromStream($stream);
            $zipFile->extractTo($this->rootdir.'/tmp/');
            
            if ($this->filesystem->exists([$this->rootdir.'/tmp/'.$xmlFilename]))
                self::getXmlContent([$xmlUrl => file_get_contents($this->rootdir.'/tmp/'.$xmlFilename, 'rb')]);
            else
                self::getXmlContent(self::getZipContent($zipFile));   
        }

        return $this->result;
    }

    private static function getNodeContent( \DOMElement $node, $key, $default = '' ) {
		$p = $node->getElementsByTagName($key);

		if ( $p->count() ) {
            $itemArray = array();
            foreach ($p as $part) array_push($itemArray, $part->getAttribute(self::KEY_TITLE));
			return $itemArray;
		} else {
			return null;
		}
	}

    private function getZipContent($zipFile) {
        $xmlContentArray = array();

        foreach($zipFile as $entryName => $xmlContent){
            $xmlContentArray[$entryName] = $xmlContent;
        }

        return $xmlContentArray;
    }

    private function getXmlContent($xmlContentArray) {
        foreach($xmlContentArray as $entryName => $xmlContent) {
            $this->serializer->loadXml($xmlContent);
            $this->filename = $entryName;
            $this->result[self::KEY_SUCCESS] = true;
            $this->result[self::KEY_COUNT] = 0;

            $productNodes = $this->serializer->getElementsByTagName(self::KEY_ITEM);
            
            foreach ( $productNodes as $key => $p ) {
                if ($p->hasChildNodes()) {
                    $this->result[self::KEY_COUNT] += 1;
                    $this->result[self::KEY_ITEM_NAME] = $entryName;
                    $this->result[$entryName][$key] = array();
                    $this->result[$entryName][$key][self::KEY_P_NAME] = $p->getAttribute(self::KEY_TITLE);
                    
                    foreach ($p->childNodes as $childNode) {
                        if ($childNode->localName == self::KEY_PARTS) {
                            $this->result[$entryName][$key][self::KEY_PARTS] = self::getNodeContent($childNode, self::KEY_ITEM);
                        }
                    }   
                }   
            }
        }
    }
}
