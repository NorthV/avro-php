<?php /* й */

namespace Apache\Avro\Service;

use Apache\Avro\DataIO\DataIO;
use Apache\Avro\DataIO\DataIOReader;
use Apache\Avro\DataIO\DataIOReaderSingleObjEnc;
use Apache\Avro\DataIO\DataIOWriter;
use Apache\Avro\DataIO\DataIOWriterSingleObjEnc;
use Apache\Avro\Datum\IODatumReader;
use Apache\Avro\Datum\IODatumWriter;
use Apache\Avro\Exception\BadRequestStatusCodeException;
use Apache\Avro\Exception\DataIoException;
use Apache\Avro\Exception\IOException;
use Apache\Avro\Exception\NoCachedMetaException;
use Apache\Avro\Exception\SchemaParseException;
use Apache\Avro\IO\StringIO;
use Apache\Avro\Schema\Schema;
use Apache\Avro\Registry\SchemaRegistry;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

class AvroSerDe
{

    /**
     * @param string $sDataJson
     * @param SchemaRegistry $oSchemaRegistry
     * @param string $sSchemaName
     * @return string
     * @throws IOException
     * @throws SchemaParseException
     * @throws BadRequestStatusCodeException
     * @throws NoCachedMetaException
     * @throws GuzzleException
     */
    public function serialize(string $sDataJson, SchemaRegistry $oSchemaRegistry, string $sSchemaName): string
    {
        $aData = json_decode($sDataJson, true, 512,  JSON_THROW_ON_ERROR) ?: [];

        $oSchema = $oSchemaRegistry->getByName($sSchemaName);
        $sPacketHeader = $oSchemaRegistry->getPacketHeaderFromCachedMeta($oSchema);

        $oIO = new StringIO();
        $oDateWriter = new DataIOWriterSingleObjEnc($oIO, new IODatumWriter($oSchema), $oSchema, $sPacketHeader);

        foreach ($aData as $aItemDatum) {
            $oDateWriter->append($aItemDatum);
        }
        $oDateWriter->close();

        return $oIO->string();
	}

    /**
     * @param string $sPacket
     * @param SchemaRegistry $oSchemaRegistry
     * @return array
     * @throws DataIoException
     * @throws IOException
     */
    public function deserialize(string $sPacket, SchemaRegistry $oSchemaRegistry): array
    {
        $oIO = new StringIO($sPacket);
        $oDataReader = new DataIOReaderSingleObjEnc($oIO, new IODatumReader(), $oSchemaRegistry);

        $aData = [];
        foreach ($oDataReader->data() as $item) {
            $aData[] = $item;
        }

        return [
            'data'          => $aData,
            'schema'        => $oDataReader->getMetaDataFor(DataIO::METADATA_SCHEMA_ATTR),
            'schema_id'     => $oDataReader->getMetaDataFor('schema_id'),
            'version_num'   => $oDataReader->getMetaDataFor('version_num'),
        ];
	}

















}