<?php

namespace vTechSolution\Bundle\QuickBookBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

	/**
	 * @Route("/api/v1/oauth")
	 */
class OauthRequiestController extends Controller
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
   *  resource="Oauth call Athorization Code",
   *  section="Oauth Call",
   *  description="This API Is Use For Generate Athorization code for access token.",
   *  statusCodes={
     *         200="Returned when successful",
     *         403="Returned when the user is not authorized to say hello",
     *         404={
     *           "Returned when something else is not found"
     *         }
     *     }
   * )
   * @Route("/oauthcode", name="vtech_solution_bundle_quickbook_oauthcode")
   * @Method({"GET"})
   */
	public function callOauthCodeAction(){
	      $this->initAction();

	      $this->responseArray = $this->quickbookService->getAccessTokencode();

        return new JsonResponse($this->responseArray);
	      
	}

	/**
   * @ApiDoc(
   *  resource="Oauth call Access Token",
   *  section="Oauth Call",
   *  description="This API Is Use For Generate Access token.",
   *  filters={
   *      {"name"="grant_type", "dataType"="string"},
   *      {"name"="code", "dataType"="string"},
   *      {"name"="redirect_uri", "dataType"="string"}
   *  },
   *  statusCodes={
     *         200="Returned when successful",
     *         403="Returned when the user is not authorized to say hello",
     *         404={
     *           "Returned when something else is not found"
     *         }
     *     }
   * )
   * @Route("/redirect-url", name="vtech_solution_bundle_quickbook_redirect_url")
   */
	public function callOauthAccessAction(){
	     $this->initAction();

        $this->responseArray = $this->quickbookService->getAccessTokencode();

        return $this->render('vTechSolutionQuickBookBundle:Default:index.html.twig',array(
        ));        
	}
}
