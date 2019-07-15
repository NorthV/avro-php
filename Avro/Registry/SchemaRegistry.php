<?php /* й */

namespace Apache\Avro\Registry;

use Apache\Avro\DataIO\DataIO;
use Apache\Avro\DataIO\DataIOReader;
use Apache\Avro\DataIO\DataIOWriter;
use Apache\Avro\Datum\IODatumReader;
use Apache\Avro\Datum\IODatumWriter;
use Apache\Avro\Exception\DataIoException;
use Apache\Avro\IO\StringIO;
use Apache\Avro\Schema\Schema;

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
    public function getSchemaMetaById(int $id): array
    {
        $sJson = file_get_contents($this->sLinkGetMetaById . "/{$id}");
        $aSchemaMeta = json_decode($sJson, true);
        return $aSchemaMeta;
	}




    /**
     * @param string $name
     * @return array
     */
    public function getSchemaVersionByNum(string $name, int $version_num): array
    {
        $sJson = file_get_contents(str_replace('{{schema_name}}', $name, $this->sLinkGetVersion) . "/{$version_num}");
        $aSchemaVersion = json_decode($sJson, true);
        return $aSchemaVersion;
	}

    /**
     * @param string $name
     * @return array
     */
    public function getSchemaLastVersion(string $name): array
    {
        $sSchemaJson = file_get_contents(str_replace('{{schema_name}}', $name, $this->sLinkGetLastVersion));
        $aSchemaVersion = json_decode($sSchemaJson, true);
        return $aSchemaVersion;
	}




    /**
     * @param int $id
     * @return Schema
     */
    public function getByVerId(int $id): Schema
    {
        $sJson = file_get_contents($this->sLinkGetByVerId . "/{$id}");
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
        $aMeta = $this->getSchemaLastVersion($name);
        $oSchema = $this->getByVerId($aMeta['id']);

        return $this->addCachedSchema($aMeta, $oSchema);
    }

    /**
     * @param string $name
     * @param int $version_num
     * @return Schema
     */
    public function getByNameVerNum(string $name, int $version_num): Schema
    {
        $aMeta = $this->getSchemaVersionByNum($name, $version_num);
        $oSchema = $this->getByVerId($aMeta['id']);

        return $this->addCachedSchema($aMeta, $oSchema);
    }





    /**
     * @param int $id
     * @param int $version_num
     * @return Schema
     */
    public function getByIdVerNum(int $id, int $version_num): Schema
    {
        $aSchemaMeta = $this->getSchemaMetaById($id);
        return $this->getByNameVerNum($aSchemaMeta['schemaMetadata']['name'], $version_num);
    }

    /**
     * @param array $aMeta
     * @param Schema $oSchema
     * @return Schema
     */
    public function addCachedSchema(array $aMeta, Schema $oSchema): Schema
    {
        $key = spl_object_id($oSchema);
        $this->aSchemas[$key]['metadata'] = $aMeta;
        $this->aSchemas[$key]['obj'] = $oSchema;

        return $oSchema;
    }

    /**
     * @param Schema $oSchema
     * @return array
     */
    public function getCachedSchemaMetadata(Schema $oSchema): array
    {
        return $this->aSchemas[spl_object_id($oSchema)]['metadata'];
    }



    /**
     * @param Schema $oSchema
     * @return string
     */
    public function getPacketHeaderFromCachedMeta(Schema $oSchema): string
    {
        if ($aMeta = $this->getCachedSchemaMetadata($oSchema)) {
            return $this->generatePacketHeader(
                $aMeta['schemaMetadataId'],
                $aMeta['version'],
                );
        }
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



















}