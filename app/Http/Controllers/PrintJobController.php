<?php

namespace App\Http\Controllers;

use Exception;
use Http\Client\Socket\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\JobManager;
use Smalot\Cups\Manager\PrinterManager;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Transport\Client as TransportClient;
use Smalot\Cups\Transport\ResponseParser;

class PrintJobController extends Controller
{
    public function printDocument(Request $request)
    {
        //validate the request
        $request->validate([
            'printer_name' => 'required|string',
            'document' => 'required|file|mimes:pdf,doc,docx,txt',
        ]);

        $printerName = $request->input('printer_name');

        $client = new TransportClient();
        $builder = new Builder();
        $responseParser = new ResponseParser();

        $manager = new PrinterManager($builder, $client, $responseParser);
        $printers = $manager->getList();

        foreach ($printers as $printer) {
            //echo $printer->getName().' ('.$printer->getUri().')'.PHP_EOL;
            if ($printer->getName() === $printerName) {
                $printer = $printer;
                break;
            }
        }

        // Get the uploaded document
        $documentPath = $request->file('document')->getRealPath();

        // Create and configure the print job
        $jobManager = new JobManager($builder, $client, $responseParser);
        $job = new Job();
        $job->setName('Print Job');
        $job->setUsername('demo');
        $job->setCopies(1);
        $job->setPageRanges('1');
        $job->addFile($documentPath);
        $job->addAttribute('media', 'A4');
        $job->addAttribute('fit-to-page', true);

        // Send the print job
        try {
            $result = $jobManager->send($printer, $job);
            return response()->json(
                [
                    'success' => 'Print job submitted', 
                    'result' => $result
                ], Response::HTTP_OK
            );
        } catch (Exception $printException) {
            return response()->json(
                [
                    'error' => 'Failed to print document: ' . $printException->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
       
    }
}
