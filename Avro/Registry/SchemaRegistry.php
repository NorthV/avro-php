<?php /* й */

namespace Avro\Registry;

use Avro\DataIO\DataIO;
use Avro\DataIO\DataIOReader;
use Avro\DataIO\DataIOWriter;
use Avro\Datum\IODatumReader;
use Avro\Datum\IODatumWriter;
use Avro\IO\StringIO;
use Avro\Schema\Schema;

class SchemaRegistry
{
	private $sLink, $sLinkGetList, $sLinkGetLastVersion, $sLinkGetByVerId;
	private $aSchemas = [];

	public function __construct($sLink, $sLinkGetList, $sLinkGetLastVersion, $sLinkGetByVerId)
    {
    	$this->sLink = $sLink;
    	$this->sLinkGetList = $sLinkGetList;
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
    public function generatePacketHeader(array $aMetadata): string
    {
        $res = chr(1);
        $res .= $this->encodeInt2bin($aMetadata['schemaMetadataId'], 16);
        $res .= $this->encodeInt2bin($aMetadata['version'], 8);
        return $res;
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
     * @param string $name
     * @return string
     */
    public function getPacketHeader(string $name): string
    {
        return $this->generatePacketHeader(
            $this->getCachedSchemaMetadata($name)
        );
    }
















}