<?php

namespace App\Http\Controllers\Api;

use App\htmltopdf_queue;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class htmltopdfController extends Controller
{
    public function get($hash)
    {
        if ($item = htmltopdf_queue::where('hash',$hash)->first()) {
            $item = $item->toArray();

            unset($item['id']);
            $json = [
                'return' => '1',
                'msg' => 'Item found',
            ];
            $json = $json + $item;
            return response()->json($json);

        } else {
            return response()->json([
                'return' => '0',
                'msg' => 'Item not found'
            ]);
        }
    }

    public function download($hash)
    {
        if ($item = htmltopdf_queue::where('hash',$hash)->first()) {
            $pathToFile = storage_path('app/htmltopdf/'.$item->hash.'/'.$item->hash.'-pdf.zip');
            return response()->download($pathToFile, $item->file_name);

        } else {
            return response()->json([
                'return' => '0',
                'msg' => 'Item not found'
            ]);
        }
    }

    public function upload(Request $request)
    {
        $file = $request->file('zip');

        if (empty($file)
//            or $file->getMimeType() != 'application/zip'
            or $file->getClientOriginalExtension() != 'zip') {
            return response()->json([
                'return' => 0,
                'msg' => 'File not found or not in right format'
            ]);
        }

        do {
            $hash = str_random(20);
            if (htmltopdf_queue::where(['hash'=> $hash])->first())
                $hash = false;
            $i = isset($i) ? ++$i : 0;
        }while(!$hash and $i < 50);
        if(!$hash)
            return response()->json([
                'return' => 0,
                'msg' => 'Internal error, try again'
            ]);

        // Save File
        $destinationPath = storage_path('app/htmltopdf/'.$hash);
        mkdir($destinationPath);
        if(!rename($file->getRealPath(),$destinationPath.'/'.$hash.'.zip')) {
//        if(!$file->move($destinationPath.'/'.$hash.'.zip',$file->getClientOriginalName())) {
            rmdir($destinationPath);
            return response()->json([
                'return' => 0,
                'msg' => 'Falha ao salvar o arquivo'
            ]);
        }

        // Save DB
        $item = new htmltopdf_queue;
        $item->hash = $hash;
        $item->status = -1;
        $item->file_name = $file->getClientOriginalName();
        $item->user_id = Auth::id();
        var_dump($item->save());

        // Return hash info
        $item = htmltopdf_queue::where('hash',$hash)->first();
        $item = $item->toArray();

        unset($item['id']);
        $json = [
            'return' => '1',
            'msg' => 'Item inserted on queue, check in a few moments if it\'s done',
        ];
        $json = $json + $item;
        return response()->json($json);
    }
}
