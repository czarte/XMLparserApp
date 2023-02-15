<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;
use App\Services as Services;

class ApiController extends AbstractController
{
    #[Route('/xmlparser', name: 'parser_api')]
    public function index(): Response
    {
        $requestHeaders = Request::createFromGlobals()->getScheme();
        $requestData = Request::createFromGlobals()->getContent();
        $request = Request::createFromGlobals();
        $client = HttpClient::create();
        $fetchService = new Services\FetchService($client);
        $backUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];
        
        if (!empty($request->query->get('url'))) {
            $xmlFileConent = $fetchService->fetchXMLfFile($request->query->get('url'), $request->query->get('update'));
            if (!$xmlFileConent['success'])
                return new Response($xmlFileConent["message"]." <a href='$backUrl'>zpět</a>");
        } else {
            return new Response("Zadejte url na zip soubor <a href='$backUrl'>zpět</a>");
        } 

        $response = $this->sortResponse(
            $request->query->get('showprompt'), 
            $xmlFileConent[$xmlFileConent['entryName']], 
            $xmlFileConent["productCount"],
        );
        $response['file_name'] = $request->query->get('url');
       
        if (!empty($request->query->get('api'))) return new Response(json_encode($response));

        return $this->render('project/parser.html.twig', $response);
    }

    private function sortResponse($showPrompt, $xmlFileConent, $xmlFileCount) {
        $response = array();
        switch ($showPrompt) {
            case 'onlycount':
                $response['products_count'] = $xmlFileCount;
                break;
            case 'onlylist':
                foreach ($xmlFileConent as $key => $entry) {
                    if (isset($entry["parts"])) unset($xmlFileConent[$key]["parts"]);
                }
                $response['products'] = $xmlFileConent;
                break;
            case 'showparts':
                $response['products'] = $xmlFileConent;
                break;
            default:
                $response['products_count'] = $xmlFileCount;
                $response['products'] = $xmlFileConent;
                break;
        }
        return $response;
    }
}