<?php

namespace App\Console\Commands;

use App\Modules\Wechat\WxVerifyFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class WxVerifyFileToDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wx_verify_file:migrate_to_database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $files = File::allFiles(public_path());
        foreach ($files as $file){
            $filename = $file->getFilename();
            if($filename == 'robots.txt') continue;
            if(preg_match('/(.+)\.txt/', $filename, $matches)){
                $content = $file->getContents();
                $name = $matches[1];
                if(!WxVerifyFile::find($name)){
                    WxVerifyFile::create([
                        'name' => $name,
                        'content' => $content,
                    ]);
                }
            }

        }
    }
}
