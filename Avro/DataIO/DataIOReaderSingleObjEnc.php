<?php

namespace Apache\Avro\DataIO;

use Avro\Datum\IOBinaryDecoder;
use Avro\Datum\IODatumReader;
use Avro\Exception\DataIoException;
use Avro\IO\IO;
use Avro\Registry\SchemaRegistry;
use Avro\Schema\Schema;
use Avro\Util\Util;

/**
 * Reads Avro data from an IO source using an AvroSchema.
 */
class DataIOReaderSingleObjEnc
{
    private $io;
    private $decoder;
    private $datumReader;
    private $oSchemaRegistry;
    private $blockCount;

    /**
     * @var array
     */
    private $metadata;

    /**
     * @var string
     */
    private $syncMarker;

    public function __construct(IO $io, IODatumReader $datumReader, SchemaRegistry $oSchemaRegistry)
    {
        $this->io = $io;
        $this->decoder = new IOBinaryDecoder($this->io);
        $this->datumReader = $datumReader;
        $this->oSchemaRegistry = $oSchemaRegistry;
        $this->blockCount = 0;
        $this->readHeader();

        $codec = Util::arrayValue($this->metadata, DataIO::METADATA_CODEC_ATTR);
        if ($codec && !DataIO::isValidCodec($codec)) {
            throw new DataIoException(sprintf('Uknown codec: %s', $codec));
        }

        // @todo Seems unsanitary to set writers_schema here. Can't constructor take it as an argument?
        $this->datumReader->setWritersSchema(Schema::parse($this->metadata[DataIO::METADATA_SCHEMA_ATTR]));
    }

    public function data(): iterable
    {
        $data = [];
        while (true) {
            if ($this->isEof()) {
                break;
            }
            $data[] = $this->datumReader->read($this->decoder);
        }

        return $data;
    }

    public function close(): bool
    {
        return $this->io->close();
    }

    public function getSyncMarker(): string
    {
        return $this->syncMarker;
    }

    public function getMetaDataFor(string $key)
    {
        return $this->metadata[$key];
    }

    private function readHeader(): void
    {
        $this->seek(0, IO::SEEK_SET);

        $sHeader = $this->read($this->oSchemaRegistry::HEADER_LEN);
        [
            'schema_id' => $iSchemaId,
            'version_num' => $iVersionNum
        ] = $this->oSchemaRegistry->parsePacketHeader($sHeader);

        $oSheme = $this->oSchemaRegistry->getByIdVerNum($iSchemaId, $iVersionNum);

        $this->metadata[DataIO::METADATA_CODEC_ATTR] = DataIO::NULL_CODEC;
        $this->metadata[DataIO::METADATA_SCHEMA_ATTR] = (string) $oSheme;
    }

    private function seek(int $offset, int $whence): bool
    {
        return $this->io->seek($offset, $whence);
    }

    private function read(int $length): string
    {
        return $this->io->read($length);
    }

    private function isEof(): bool
    {
        return $this->io->isEof();
    }

    private function skipSync(): bool
    {
        $proposed_sync_marker = $this->read(DataIO::SYNC_SIZE);
        if ($proposed_sync_marker !== $this->syncMarker) {
            $this->seek(-DataIO::SYNC_SIZE, IO::SEEK_CUR);

            return false;
        }

        return true;
    }

    /**
     * Reads the block header (which includes the count of items in the block and the length in bytes of the block).
     */
    private function readBlockHeader(): int
    {
        $this->blockCount = $this->decoder->readLong();

        return $this->decoder->readLong();
    }
}
