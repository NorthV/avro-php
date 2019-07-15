<?php /* й */

namespace Apache\Avro\Registry;

use Apache\Avro\DataIO\DataIO;
use Apache\Avro\DataIO\DataIOReader;
use Apache\Avro\DataIO\DataIOWriter;
use Apache\Avro\Datum\IODatumReader;
use Apache\Avro\Datum\IODatumWriter;
use Apache\Avro\Exception\BadRequestStatusCodeException;
use Apache\Avro\Exception\DataIoException;
use Apache\Avro\Exception\SchemaParseException;
use Apache\Avro\IO\StringIO;
use Apache\Avro\Schema\Schema;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Http\Message\StreamInterface;


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
     * @param $sLink
     * @return StreamInterface
     * @throws BadRequestStatusCodeException
     * @throws GuzzleException
     */
    public function file_get($sLink) {
        $oClient = new Client();
        $res = $oClient->request('GET', $sLink);
        $iCode = $res->getStatusCode();
        if ($iCode !== 200) {
            throw new BadRequestStatusCodeException($sLink, $iCode);
        }
	    return $res->getBody();
    }

    /**
     *
     * @return array
     * @throws BadRequestStatusCodeException
     * @throws GuzzleException
     */
    public function getList(): array
    {
        $sListJson = $this->file_get($this->sLinkGetList);
        $aList = json_decode($sListJson, true, 512,  JSON_THROW_ON_ERROR);
        return $aList['entities'] ?? [];
	}

    /**
     * @param int $id
     * @return array
     * @throws BadRequestStatusCodeException
     * @throws GuzzleException
     */
    public function getSchemaMetaById(int $id): array
    {
        $sJson = $this->file_get($this->sLinkGetMetaById . "/{$id}");
        $aSchemaMeta = json_decode($sJson, true, 512,  JSON_THROW_ON_ERROR);
        return $aSchemaMeta;
	}


    /**
     * @param string $name
     * @param int $version_num
     * @return array
     * @throws BadRequestStatusCodeException
     * @throws GuzzleException
     */
    public function getSchemaVersionByNum(string $name, int $version_num): array
    {
        $sJson = $this->file_get(str_replace('{{schema_name}}', $name, $this->sLinkGetVersion) . "/{$version_num}");
        $aSchemaVersion = json_decode($sJson, true,512,  JSON_THROW_ON_ERROR);
        return $aSchemaVersion;
	}

    /**
     * @param string $name
     * @return array
     * @throws BadRequestStatusCodeException
     * @throws GuzzleException
     */
    public function getSchemaLastVersion(string $name): array
    {
        $sSchemaJson = $this->file_get(str_replace('{{schema_name}}', $name, $this->sLinkGetLastVersion));
        $aSchemaVersion = json_decode($sSchemaJson, true, 512,  JSON_THROW_ON_ERROR);
        return $aSchemaVersion;
	}


    /**
     * @param int $id
     * @return Schema
     * @throws BadRequestStatusCodeException
     * @throws GuzzleException
     * @throws SchemaParseException
     */
    public function getByVerId(int $id): Schema
    {
        $sJson = $this->file_get($this->sLinkGetByVerId . "/{$id}");
        $o = json_decode($sJson, false, 512,  JSON_THROW_ON_ERROR);
        $oSchema = Schema::parse($o->schema);
        return $oSchema;
    }


    /**
     * @param string $name
     * @return Schema
     * @throws BadRequestStatusCodeException
     * @throws GuzzleException
     * @throws SchemaParseException
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
     * @throws BadRequestStatusCodeException
     * @throws GuzzleException
     * @throws SchemaParseException
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
     * @throws BadRequestStatusCodeException
     * @throws GuzzleException
     * @throws JsonException
     * @throws SchemaParseException
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
     * @param int $iSchemaId
     * @param int $iVersionNum
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
     * @param string $sHeader
     * @return array
     * @throws DataIoException
     */
    public function parsePacketHeader(string $sHeader): array
    {
        $pos = 0;
        $sMagic = substr($sHeader, $pos, $len = strlen($this->magic));
        if ($sMagic !== $this->magic) {
            throw new DataIoException(sprintf('Not an Avro data file: %s does not match %s', bin2hex($sMagic), bin2hex($this->magic)));
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
     * @param string $bin
     * @return int
     */
    public function encodeBin2int(string $bin): int
    {
        $hex = bin2hex($bin);
        return hexdec($hex);
    }



















}