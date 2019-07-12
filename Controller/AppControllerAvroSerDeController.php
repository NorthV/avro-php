<?php /* й */

namespace App\Controller;

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
    	return \Acme\AvroBundle\Controller\AvroSerDeController::index($oAvroSerDe, $oSchemaRegistry, $oRequest);
    }






























}