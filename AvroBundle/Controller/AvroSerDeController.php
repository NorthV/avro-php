<?php /* й */

namespace Apache\AvroBundle\Controller;

use Apache\Avro\DataIO\DataIO;
use Apache\Avro\Exception\BadRequestStatusCodeException;
use Apache\Avro\Exception\DataIoException;
use Apache\Avro\Exception\IOException;
use Apache\Avro\Exception\NoCachedMetaException;
use Apache\Avro\Exception\SchemaParseException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Apache\Avro\Service\AvroSerDe;
use Apache\Avro\Registry\SchemaRegistry;

class AvroSerDeController extends AbstractController
{
    /**
     * @Route("/", name="index")
     *
     * @Template("AvroSerDe/index.html.twig")
     * @param AvroSerDe $oAvroSerDe
     * @param SchemaRegistry $oSchemaRegistry
     * @param Request $oRequest
     * @return array
     * @throws BadRequestStatusCodeException
     * @throws DataIoException
     * @throws IOException
     * @throws NoCachedMetaException
     * @throws SchemaParseException
     * @throws GuzzleException
     */
    public function index(AvroSerDe $oAvroSerDe, SchemaRegistry $oSchemaRegistry, Request $oRequest): array
    {
        $Q = $oRequest->query;
        $sAction = (string) $Q->get('action');
        $sSchemaName = (string) $Q->get('schema_name');
        $sDataJson = (string) $Q->get('data_json');
        $sPacketHex = (string) $Q->get('packet');

        $aList = $oSchemaRegistry->getList();

        $res = '';
        if ($sAction === 'serialize') {
            if ($sSchemaName) {
                $res = $oAvroSerDe->serialize($sDataJson, $oSchemaRegistry, $sSchemaName);
                $oSchema = $oSchemaRegistry->getByName($sSchemaName);
            }
        }
        elseif ($sAction === 'deserialize') {
            if ($sPacketHex) {
                $sPacketHex = preg_replace('/\s+/', '', $sPacketHex);
                [
                    'data'          => $aData,
                    'schema'        => $oUnpackedSchema,
                    'schema_id'     => $iSchemaId,
                    'version_num'   => $iVersionNum,
                ] = $oAvroSerDe->deserialize(hex2bin($sPacketHex), $oSchemaRegistry);
                $sUnpackedDataJson = json_encode($aData);
            }
        }


        return [
            'list'      => $aList,
            'schema_name' => $sSchemaName,
            'schema_json' => $oSchema ?? '',
            'data_json' => $sDataJson,
            'result'    => bin2hex($res),
            'packet'    => $sPacketHex,
            'unpacked_schema_json' => $oUnpackedSchema ?? '',
            'unpacked_schema_id' => $iSchemaId ?? '',
            'unpacked_schema_ver_num' => $iVersionNum ?? '',
            'unpacked_data_json' => $sUnpackedDataJson ?? '',
        ];
    }

    /**
     * @Route("/list", name="list")
     *
     * @Template("AvroSerDe/pre.html.twig")
     * @param SchemaRegistry $oSchemaRegistry
     * @return array
     * @throws BadRequestStatusCodeException
     * @throws GuzzleException
     */
    public function getList(SchemaRegistry $oSchemaRegistry): array
    {

        $res = $oSchemaRegistry->getList();
        return [
            'result' => var_export($res, true),
        ];
    }

    /**
     * @Route("/schema/{id}", name="schema")
     *
     * @Template("AvroSerDe/pre.html.twig")
     * @param $id
     * @param SchemaRegistry $oSchemaRegistry
     * @return array
     * @throws BadRequestStatusCodeException
     * @throws SchemaParseException
     * @throws GuzzleException
     */
    public function getSchema($id, SchemaRegistry $oSchemaRegistry): array
    {

        $res = $oSchemaRegistry->getByVerId($id);
        return [
            'result' => var_export((string) $res, true),
        ];
    }





























}