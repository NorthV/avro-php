<?php /* й */

namespace Avro\Registry;

use Avro\DataIO\DataIO;
use Avro\DataIO\DataIOReader;
use Avro\DataIO\DataIOWriter;
use Avro\Datum\IODatumReader;
use Avro\Datum\IODatumWriter;
use Avro\Exception\DataIoException;
use Avro\IO\StringIO;
use Avro\Schema\Schema;

class SchemaRegistry
{
    const HEADER_LEN = 13;
    const HEADER_SCHEMA_ID_LEN = 8;
    const HEADER_VERSION_NUM_LEN = 4;

    private $magic;

    private $sLink, $sLinkGetList, $sLinkGetMetaById, $sLinkGetVersion, $sLinkGetLastVersion, $sLinkGetByVerId;
    private $aSchemas = [];

	public function __construct($sLink, $sLinkGetList, $sLinkGetMetaById, $sLinkGetVersion, $sLinkGetLastVersion, $sLinkGetByVerId)
    {
        $this->magic = chr(1);
    	$this->sLink = $sLink;
    	$this->sLinkGetList = $sLinkGetList;
        $this->sLinkGetMetaById = $sLinkGetMetaById;
        $this->sLinkGetVersion = $sLinkGetVersion;
        $this->sLinkGetLastVersion = $sLinkGetLastVersion;
    	$this->sLinkGetByVerId = $sLinkGetByVerId;
    }

	/**
     *
     */
    public function getList(): array
    {
        $sListJson = file_get_contents($this->sLinkGetList);
        $aList = json_decode($sListJson, true);
        return $aList['entities'] ?? [];
	}

    /**
     * @param int $id
     * @return array
     */
    public function getSchemeMetaById(int $id): array
    {
        $sJson = file_get_contents($this->sLinkGetMetaById . "/{$id}");
        $aSchemaMeta = json_decode($sJson, true);
        return $aSchemaMeta;
	}

    /**
     * @param string $name
     * @return array
     */
    public function getSchemeVersionByNum(string $name, int $version_num): array
    {
        $sJson = file_get_contents(str_replace('{{schema_name}}', $name, $this->sLinkGetVersion) . "/{$version_num}");
        $aSchemaVersion = json_decode($sJson, true);
        return $aSchemaVersion;
	}

    /**
     * @param string $name
     * @return array
     */
    public function getSchemeLastVersion(string $name): array
    {
        $sSchemaJson = file_get_contents(str_replace('{{schema_name}}', $name, $this->sLinkGetLastVersion));
        $aSchemaVersion = json_decode($sSchemaJson, true);
        return $aSchemaVersion;
	}

    /**
     * @param int $id
     * @return string
     */
    public function getSchemaJsonByVerId(int $id): string
    {
        $sSchemaJson = file_get_contents($this->sLinkGetByVerId . "/{$id}");
        return $sSchemaJson;
	}

    /**
     * @param int $id
     * @return Schema
     */
    public function getByVerId(int $id): Schema
    {
        $sJson = $this->getSchemaJsonByVerId($id);
        $o = json_decode($sJson);
        $oSchema = Schema::parse($o->schema);
        return $oSchema;
    }

    /**
     * @param string $name
     * @return Schema
     */
    public function getByName(string $name): Schema
    {
        $this->aSchemas[$name]['metadata'] = $this->getSchemeLastVersion($name);
        $this->aSchemas[$name]['obj'] = $this->getByVerId($this->aSchemas[$name]['metadata']['id']);

        return $this->aSchemas[$name]['obj'];
    }

    /**
     * @param string $name
     * @param int $version_num
     * @return Schema
     */
    public function getByNameVerNum(string $name, int $version_num): Schema
    {
        $aSchemeVersion = $this->getSchemeVersionByNum($name, $version_num);
        return $this->getByVerId($aSchemeVersion['id']);
    }

    /**
     * @param int $id
     * @param int $version_num
     * @return Schema
     */
    public function getByIdVerNum(int $id, int $version_num): Schema
    {
        $aSchemeMeta = $this->getSchemeMetaById($id);
        return $this->getByNameVerNum($aSchemeMeta['schemaMetadata']['name'], $version_num);
    }

    /**
     * @param string $name
     * @return array
     */
    public function getCachedSchemaMetadata(string $name): array
    {
        return $this->aSchemas[$name]['metadata'];
    }

    /**
     * @param array $aMetadata
     * @return string
     */
    public function generatePacketHeader(int $iSchemaId, int $iVersionNum): string
    {
        $res = chr(1);
        $res .= $this->encodeInt2bin($iSchemaId, 16);
        $res .= $this->encodeInt2bin($iVersionNum, 8);
        return $res;
    }

    /**
     */
    public function parsePacketHeader(string $sHeader): array
    {
        $pos = 0;
        $sMagic = substr($sHeader, $pos, $len = strlen($this->magic));
        if ($sMagic !== $this->magic) {
            throw new DataIoException(sprintf('Not an Avro data file: %s does not match %s', bin2hex($magic), bin2hex($this->magic)));
        }

        $pos += $len;
        $sSchemaId = substr($sHeader, $pos, $len = $this::HEADER_SCHEMA_ID_LEN);
        $iSchemaId = $this->encodeBin2int($sSchemaId);

        $pos += $len;
        $sVersionNum = substr($sHeader, $pos, $len = $this::HEADER_VERSION_NUM_LEN);
        $iVersionNum = $this->encodeBin2int($sVersionNum);

        return [
            'schema_id' => $iSchemaId,
            'version_num' => $iVersionNum,
        ];
    }

    /**
     * @param int $int
     * @param int $len
     * @return string
     */
    public function encodeInt2bin(int $int, int $len): string
    {
        $hex = dechex($int);
        $hex = str_pad($hex, (int) $len, '0', STR_PAD_LEFT);
        return hex2bin($hex);
    }

    /**
     */
    public function encodeBin2int(string $bin): int
    {
        $hex = bin2hex($bin);
        return hexdec($hex);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getPacketHeaderByName(string $name): string
    {
        if (!$this->getCachedSchemaMetadata($name)) {
            $this->getByName($name);
        }
        ['schemaMetadataId' => $iSchemaId, 'version' => $iVersionNum] = $this->getCachedSchemaMetadata($name);
        return $this->generatePacketHeader($iSchemaId, $iVersionNum);
    }


















}