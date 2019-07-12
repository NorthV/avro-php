<?php /* й */

namespace Acme\AvroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Avro\Service\AvroSerDe;
use Avro\Registry\SchemaRegistry;

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
     */
    public function index(AvroSerDe $oAvroSerDe, SchemaRegistry $oSchemaRegistry, Request $oRequest): array
    {
        $Q = $oRequest->query;
        $sSchemaName = (string) $Q->get('schema_name');
        $sDataJson = (string) $Q->get('data_json');

        if ($sSchemaName) {
            $res = $oAvroSerDe->serialize($sDataJson, $oSchemaRegistry, $sSchemaName);
            $aData = $oAvroSerDe->deserialize($res, $oSchemaRegistry);
            $oSchema = $oSchemaRegistry->getByName($sSchemaName);
        }

        $aList = $oSchemaRegistry->getList();
        return [
            'list'      => $aList,
            'schema_name' => $sSchemaName,
            'schema_json' => $oSchema ?? '',
            'data_json' => $sDataJson,
            'result_json'    => $res ?? '',
            'result_bin'    => $res ?? '',
        ];
    }

    /**
     * @Route("/list", name="list")
     *
     * @Template("AvroSerDe/pre.html.twig")
     * @param SchemaRegistry $oSchemaRegistry
     * @return array
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
     */
    public function getSchema($id, SchemaRegistry $oSchemaRegistry): array
    {

        $res = $oSchemaRegistry->getByVerId($id);
        return [
            'result' => var_export((string) $res, true),
        ];
    }





























}