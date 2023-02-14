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

        $entryKey = $xmlFileConent['entryName'];
        $response = array();

        $response['file_name'] = $request->query->get('url');

        switch ($request->query->get('showprompt')) {
            case 'onlycount':
                $response['products_count'] = $xmlFileConent["productCount"];
                break;
            case 'onlylist':
                foreach ($xmlFileConent[$entryKey] as $key => $entry) {
                    if (isset($entry["parts"])) unset($xmlFileConent[$entryKey][$key]["parts"]);
                }
                $response['products'] = $xmlFileConent[$entryKey];
                break;
            case 'showparts':
                $response['products'] = $xmlFileConent[$entryKey];
                break;
            default:
                $response['products_count'] = $xmlFileConent["productCount"];
                $response['products'] = $xmlFileConent[$entryKey];
                break;
        }

        if (!empty($request->query->get('api'))) return new Response(json_encode($response));

        return $this->render('project/parser.html.twig', $response);
    }
}