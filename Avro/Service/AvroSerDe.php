<?php /* й */

namespace Avro\Service;

use Avro\DataIO\DataIO;
use Avro\DataIO\DataIOReader;
use Avro\DataIO\DataIOReaderSingleObjEnc;
use Avro\DataIO\DataIOWriter;
use Avro\DataIO\DataIOWriterSingleObjEnc;
use Avro\Datum\IODatumReader;
use Avro\Datum\IODatumWriter;
use Avro\IO\StringIO;
use Avro\Schema\Schema;
use Avro\Registry\SchemaRegistry;

class AvroSerDe
{

    public function serialize(string $sDataJson, SchemaRegistry $oSchemaRegistry, string $sSchemaName): string
    {
        $aData = json_decode($sDataJson, true) ?: [];

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

    public function deserialize(string $sPacket, SchemaRegistry $oSchemaRegistry): array
    {
        $oIO = new StringIO($sPacket);
        $oDataReader = new DataIOReaderSingleObjEnc($oIO, new IODatumReader(), $oSchemaRegistry);

        $aData = [];
        foreach ($oDataReader->data() as $item) {
            $aData[] = $item;
        }

        return $aData;
	}

















}