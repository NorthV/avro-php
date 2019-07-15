<?php /* й */

namespace Apache\Avro\Service;

use Apache\Avro\DataIO\DataIO;
use Apache\Avro\DataIO\DataIOReader;
use Apache\Avro\DataIO\DataIOReaderSingleObjEnc;
use Apache\Avro\DataIO\DataIOWriter;
use Apache\Avro\DataIO\DataIOWriterSingleObjEnc;
use Apache\Avro\Datum\IODatumReader;
use Apache\Avro\Datum\IODatumWriter;
use Apache\Avro\IO\StringIO;
use Apache\Avro\Schema\Schema;
use Apache\Avro\Registry\SchemaRegistry;

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