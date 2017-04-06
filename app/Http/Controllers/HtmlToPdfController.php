<?php

namespace App\Http\Controllers;

use App\Forms\htmltopdfForm;
use App\htmltopdf_queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kris\LaravelFormBuilder\FormBuilder;
use Chumper\Zipper\Zipper;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class HtmlToPdfController extends Controller
{
    private $pdf_options = '-O Landscape';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $queue = htmltopdf_queue::orderBy('id','desc')->paginate(10);
        return view('admin.htmltopdf.index', compact('queue'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $form = \FormBuilder::create(htmltopdfForm::class, [
            'method' => 'POST',
            'url' => route('admin.htmltopdf.store'),
        ]);
        $title = 'Novo Processamento htmlToPdf';
        return view('admin.htmltopdf.save', compact('form', 'title'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(FormBuilder $formBuilder)
    {
        $form = $formBuilder->create(htmltopdfForm::class);

//        htmltopdf_queue::create($form->getFieldValues());

        do {
            $hash = str_random(20);
            if (htmltopdf_queue::where(['hash'=> $hash])->first())
                $hash = false;
            $i = isset($i) ? ++$i : 0;
        }while(!$hash and $i < 50);

        // Save File
        $zipField = $form->getFieldValues()['zip'];
        if ($zipField->getClientOriginalExtension() != 'zip')
            return 'Arquivo invalido, use somente .zip';
        $destinationPath = storage_path('app/htmltopdf/'.$hash);
        mkdir($destinationPath);
        if(!rename($zipField->getRealPath(),$destinationPath.'/'.$hash.'.zip')) {
            rmdir($destinationPath);
            return 'Falha ao salvar o arquivo';
        }

        // Save DB
        $item = new htmltopdf_queue;
        $item->hash = $hash;
        $item->status = -1;
        $item->file_name = $zipField->getClientOriginalName();
        $item->user_id = Auth::id();
        $item->save();

        return redirect()->route('admin.htmltopdf.index');
    }



    /**
     * Download the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\htmltopdf_queue  $htmltopdf_queue
     * @return \Illuminate\Http\Response
     */
    public function download($id)
    {
        $item = htmltopdf_queue::where('id',$id)->first();
        $item = $item->getAttributes();

        if($item['status'] < 100)
            return redirect()->route('admin.htmltopdf.index');

        $pathToFile = storage_path('app/htmltopdf/'.$item['hash'].'/'.$item['hash'].'-pdf.zip');
        $name = str_replace_first('.zip','-pdf.zip',$item['file_name']);
        return response()->download($pathToFile, $name);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\htmltopdf_queue  $htmltopdf_queue
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) //htmltopdf_queue $htmltopdf_queue
    {
        if (is_numeric($id)
            and $item = htmltopdf_queue::where(['id'=>$id])->first()) {
            $item = $item->getAttributes();

            // Remove files (if exist) then remove from db
            if(Storage::disk('local')->exists('htmltopdf/'.$item['hash'].'/'.$item['hash'].'.zip'))
                File::deleteDirectory(storage_path('app/htmltopdf/'.$item['hash']));

            htmltopdf_queue::where(['id'=>$item['id']])->delete();
        }
        return redirect()->route('admin.htmltopdf.index');
    }

    public function cron_job()
    {
        // Is there a job working? Stop now!
        if ($item = htmltopdf_queue::where(['status >=' => 0, 'status <>' => 100])->first()) {
            var_dump($item = $item->getAttributes());
            exit("Another cron already runing! [{$item['id']}/{$item['status']}% -- {$item['updated_at']}".PHP_EOL);
        }

        // Find new jobs
        $jobs = htmltopdf_queue::where(['status'=>-1])->get();
        foreach ($jobs as $k => $job) {
            $job = $job->getAttributes();
            echo "Starting: ".$job['id'].PHP_EOL;
            htmltopdf_queue::where('id',$job['id'])->update(['status'=>0]);
            self::exec_job($job);
            echo "Done: ".$job['id'].PHP_EOL;
            echo "----------------------------".PHP_EOL;
        }
        echo "List done!";
    }

    private function exec_job($job)
    {
        $hash = $job['hash'];
        // Zip file exists
        if(!Storage::disk('local')->exists('htmltopdf/'.$hash.'/'.$hash.'.zip')) {
            echo 'Zip file not found for '.$hash.PHP_EOL;
            htmltopdf_queue::where('id',$job['id'])->update(['status'=>-2]);
            return false;
        }

        // Unzip HTMLs
        $zipper = New Zipper();
        $zipper->make(storage_path('app/htmltopdf/'.$hash.'/'.$hash.'.zip'))->extractMatchingRegex(storage_path('app/htmltopdf/'.$hash.'/html'),'/.*?\.html/',Zipper::WHITELIST);
        mkdir(storage_path('app/htmltopdf/'.$hash.'/pdf'));

        // Exec files
        $origin = storage_path('app/htmltopdf/'.$hash.'/html');
        $dest = storage_path('app/htmltopdf/'.$hash.'/pdf/');
        $files = File::Files($origin);
        foreach ($files as $k => $file) {
            echo "FILE:".$file.PHP_EOL;
            $file_name = explode('/',$file);
            $file_name = $file_name[count($file_name)-1];
            exec(Config::get('services.wkhtmltopdf')['path'].' '.$this->pdf_options.' "'.$file.'" "'.$dest.str_replace_first('.html','.pdf',$file_name).'"');
            $done = number_format(((($k+1)/count($files))*100),0);
            if(htmltopdf_queue::where('id',$job['id'])->first()) {
                htmltopdf_queue::where('id',$job['id'])->update(['status'=>$done]);
            } else {
                File::deleteDirectory(storage_path('app/htmltopdf/'.$hash));
                echo "Canceled item: ".$job['id'].PHP_EOL;
                break;
            }
        }

        // Zip pdf files
        $files = glob(storage_path('app/htmltopdf/'.$hash.'/pdf/*'));
        $zipper->make('storage/app/htmltopdf/'.$hash.'/'.$hash.'-pdf.zip')->add($files)->close();

        // Remove html and pdf folders
        File::deleteDirectory(storage_path('app/htmltopdf/'.$hash.'/html'));
        File::deleteDirectory(storage_path('app/htmltopdf/'.$hash.'/pdf'));
    }
}
