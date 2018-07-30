<?php

namespace vTechSolution\Bundle\QuickBookBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;


    /**
	 * @Route("/api/v1/quickbook")
	 */

class QuickBookController extends Controller
{
  private $responseArray;
  private $request;
  private $quickbookService;

  private function initAction(){
    $this->responseArray = array();
    $this->request = $this->getRequest();

    $this->quickbookService = $this->get('v_tech_solution_quick_book.start');
  }
  /**
   * @ApiDoc(
   *  resource="QuickBook call Access Token",
   *  section="QuickBook Call",
   *  description="This API Is Use For Refresh Access Token",
   * statusCodes={
     *         200="Returned when successful",
     *         403="Returned when the user is not authorized to say hello",
     *         404={
     *           "Returned when something else is not found"
     *         }
     *     }
   * )
   * @Route("/start", name="vtech_solution_bundle_quickbook_start")
   * @Method({"GET"})
   */
  public function startAction()
  {
      $this->initAction();

      $this->responseArray = $this->quickbookService->startQuickBookProcess();
      
      return new JsonResponse($this->responseArray);
  }
/**
   * @ApiDoc(
   *  resource="HRM Data call code",
   *  section="QuickBook Call",
   *  description="This API Is Use For Fatch data from HRM",
   *  filters={
   *      {"name"="employee_id", "dataType"="integer"},
   *      {"name"="timesheet_id", "dataType"="integer"},
   *  },
   * statusCodes={
     *         200="Returned when successful",
     *         403="Returned when the user is not authorized to say hello",
     *         404={
     *           "Returned when something else is not found"
     *         }
     *     }
   * )
   * @Route("/timesheet", name="vtech_solution_bundle_quickbook_timesheet")
   * @Method({"GET"})
   */
  public function timesheetAction()
  {
    $this->initAction();
    $response = new Response();

    $this->responseArray['message'] = $this->quickbookService->timesheetProcess();

    $response->setContent(json_encode($this->responseArray));
    $response->headers->set('Content-Type', 'application/json');
    $response->headers->set('Access-Control-Allow-Origin', '*');

    return $response;
  }
}
