<?php /* й */

namespace App\Controller;

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

class AvroSerDeController extends \Apache\AvroBundle\Controller\AvroSerDeController
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
    public function index(AvroSerDe $oAvroSerDe, SchemaRegistry $oSchemaRegistry, Request $oRequest)
    {
    	return parent::index($oAvroSerDe, $oSchemaRegistry, $oRequest);
    }






























}