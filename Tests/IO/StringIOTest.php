<?php

namespace Apache\Avro\IO;

use Apache\Avro\DataIO\DataIOReader;
use Apache\Avro\DataIO\DataIOWriter;
use Apache\Avro\Datum\IODatumReader;
use Apache\Avro\Datum\IODatumWriter;
use Apache\Avro\Debug\Debug;
use Apache\Avro\Exception\IOException;
use Apache\Avro\Schema\Schema;
use PHPUnit\Framework\TestCase;

class StringIOTest extends TestCase
{
    public function testWrite(): void
    {
        $stringIO = new StringIO();
        $this->assertSame(0, $stringIO->tell());
        $this->assertSame(3, $stringIO->write('foo'));
        $this->assertSame(3, $stringIO->tell());
    }

    public function testStringReadWrite(): void
    {
        $writersSchema = Schema::parse('"null"');
        $IODatumWriter = new IODatumWriter($writersSchema);
        $stringIO = new StringIO();
        $this->assertSame('', $stringIO->string());
        $dataIOWriter = new DataIOWriter($stringIO, $IODatumWriter, $writersSchema);
        $dataIOWriter->close();

        $this->assertSame(57, $stringIO->length(), Debug::asciiString($stringIO->string()));

        $readStringIO = new StringIO($stringIO->string());

        $IODatumReader = new IODatumReader();
        $dataIOReader = new DataIOReader($readStringIO, $IODatumReader);
        $readData = (array) $dataIOReader->data();
        $datumCount = count($readData);
        $this->assertSame(0, $datumCount);

        $this->assertTrue($readStringIO->truncate());
        $this->assertSame(0, $readStringIO->length(), Debug::asciiString($readStringIO->string()));

        $readStringIO->close();
        try {
            $readStringIO->write('foo');
        } catch (IOException $e) {
            $this->assertFalse(false);
        }
    }
}
